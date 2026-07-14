<?php

namespace TweakPHP\Client;

use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Psy\Configuration;
use Psy\ExecutionLoopClosure;
use Psy\Shell;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use TweakPHP\Client\Database\QueryCollector;
use TweakPHP\Client\Output\StreamingOutput;
use TweakPHP\Client\OutputModifiers\OutputModifier;

class Tinker
{
    protected OutputInterface $output;

    protected Shell $shell;

    protected OutputModifier $outputModifier;

    public static array $statements = [];

    public static int $current = 0;

    public function __construct(OutputModifier $outputModifier, Configuration $config)
    {
        $this->output = new BufferedOutput;

        $this->shell = $this->createShell($this->output, $config);

        $this->outputModifier = $outputModifier;
    }

    public function execute(string $rawPHPCode): array
    {
        self::$statements = [];

        if (strpos($rawPHPCode, '<?php') === false) {
            $rawPHPCode = "<?php\n".$rawPHPCode;
        }

        $parser = (new ParserFactory)->createForHostVersion();
        $prettyPrinter = new Standard;
        foreach ($parser->parse($rawPHPCode) as $key => $stmt) {
            $code = $prettyPrinter->prettyPrint([$stmt]);
            self::$current = $key;
            self::$statements[] = [
                'line' => $stmt->getStartLine(),
                'code' => $code,
            ];

            QueryCollector::start();
            try {
                $output = $this->doExecute($code);
            } finally {
                $queries = QueryCollector::stop();
            }

            self::$statements[$key]['output'] = $output;
            self::$statements[$key]['queries'] = $queries;
        }

        $allQueries = [];
        foreach (self::$statements as $stmt) {
            if (isset($stmt['queries'])) {
                $allQueries = array_merge($allQueries, $stmt['queries']);
            }
        }

        return [
            'output' => self::$statements,
            'queries' => $allQueries,
        ];
    }

    /**
     * Execute each statement and emit events as output becomes available.
     *
     * @param  callable(array): void  $onEvent
     */
    public function executeStreaming(string $rawPHPCode, callable $onEvent): void
    {
        self::$statements = [];

        if (strpos($rawPHPCode, '<?php') === false) {
            $rawPHPCode = "<?php\n".$rawPHPCode;
        }

        $parser = (new ParserFactory)->createForHostVersion();
        $prettyPrinter = new Standard;

        foreach ($parser->parse($rawPHPCode) as $key => $stmt) {
            $code = $prettyPrinter->prettyPrint([$stmt]);
            self::$current = $key;
            self::$statements[] = [
                'line' => $stmt->getStartLine(),
                'code' => $code,
            ];

            $onEvent([
                'type' => 'statement.started',
                'index' => $key,
                'line' => $stmt->getStartLine(),
                'code' => $code,
            ]);

            QueryCollector::start();
            try {
                $this->doExecuteStreaming($code, $key, $onEvent);
            } finally {
                $queries = QueryCollector::stop();
            }

            self::$statements[$key]['queries'] = $queries;
            $onEvent([
                'type' => 'statement.completed',
                'index' => $key,
                'queries' => $queries,
            ]);
        }

        $onEvent(['type' => 'completed']);
    }

    protected function doExecute(string $code): string
    {
        $this->shell->addInput($code);
        $this->shell->addInput("\necho('TWEAKPHP_END'); exit();");
        $this->output = new BufferedOutput;
        $this->shell->setOutput($this->output);
        $closure = new ExecutionLoopClosure($this->shell);
        $closure->execute();
        $result = $this->outputModifier->modify($this->cleanOutput($this->output->fetch()));

        return trim($result);
    }

    /**
     * @param  callable(array): void  $onEvent
     */
    protected function doExecuteStreaming(string $code, int $index, callable $onEvent): void
    {
        $this->output = new StreamingOutput(function (string $chunk) use ($index, $onEvent): void {
            $chunk = $this->outputModifier->modify($chunk);

            if ($chunk === '') {
                return;
            }

            $onEvent([
                'type' => 'output',
                'index' => $index,
                'data' => $chunk,
            ]);
        });
        $this->shell->setOutput($this->output);
        $this->shell->execute($code, true);
    }

    protected function createShell(OutputInterface $output, Configuration $config): Shell
    {
        $shell = new Shell($config);

        $shell->setOutput($output);

        return $shell;
    }

    protected function cleanOutput(string $output): string
    {
        $output = preg_replace('/(?s)(<aside.*?<\/aside>)|Exit:  Ctrl\+D/ms', '$2', $output);

        $output = preg_replace('/(?s)(<whisper.*?<\/whisper>)|INFO  Ctrl\+D\./ms', '$2', $output);

        return trim($output);
    }

    public function getShell(): Shell
    {
        return $this->shell;
    }
}

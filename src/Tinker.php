<?php

namespace TweakPHP\Client;

use PhpParser\Node\Stmt\Expression;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Psy\CodeCleaner\NoReturnValue;
use Psy\Configuration;
use Psy\Exception\BreakException;
use Psy\Shell;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\VarDumper\VarDumper;
use TweakPHP\Client\Database\QueryCollector;
use TweakPHP\Client\Output\StreamingOutput;
use TweakPHP\Client\OutputModifiers\OutputModifier;

class Tinker
{
    public static bool $dumpOccurred = false;

    protected OutputInterface $output;

    protected Shell $shell;

    protected Configuration $config;

    protected OutputModifier $outputModifier;

    public static array $statements = [];

    public static int $current = 0;

    public function __construct(OutputModifier $outputModifier, Configuration $config)
    {
        $this->output = new BufferedOutput;

        $this->config = $config;

        $this->shell = $this->createShell($this->output, $config);

        $this->outputModifier = $outputModifier;

        $this->registerVarDumperHandler($config);
    }

    public function execute(string $rawPHPCode): array
    {
        self::$statements = [];

        $this->registerVarDumperHandler($this->config);

        $rawPHPCode = $this->normalizePHPCode($rawPHPCode);

        $parserFactory = new ParserFactory;
        $parser = method_exists($parserFactory, 'createForHostVersion')
            ? $parserFactory->createForHostVersion()
            : $parserFactory->create(ParserFactory::PREFER_PHP7);
        $prettyPrinter = new Standard;
        foreach ($parser->parse($rawPHPCode) as $key => $stmt) {
            $code = $prettyPrinter->prettyPrint([$stmt]);
            $executableCode = $stmt instanceof Expression
                ? $prettyPrinter->prettyPrintExpr($stmt->expr)
                : $code;

            self::$current = $key;
            self::$statements[] = [
                'line' => $stmt->getStartLine(),
                'code' => $code,
            ];

            QueryCollector::start();
            try {
                $output = $this->doExecute($executableCode);
            } finally {
                $queries = QueryCollector::stop();
                self::$statements[$key]['queries'] = $queries;
            }

            if ($output !== '') {
                if (isset(self::$statements[$key]['output']) && self::$statements[$key]['output'] !== '' && self::$statements[$key]['output'] !== $output) {
                    self::$statements[$key]['output'] .= "\n".$output;
                } else {
                    self::$statements[$key]['output'] = $output;
                }
            } elseif (! isset(self::$statements[$key]['output'])) {
                self::$statements[$key]['output'] = '';
            }

            $queryErrors = QueryCollector::errors();
            if ($queryErrors !== []) {
                self::$statements[$key]['query_errors'] = $queryErrors;
            }
        }

        $allQueries = [];
        $allQueryErrors = [];
        foreach (self::$statements as $stmt) {
            if (isset($stmt['queries'])) {
                $allQueries = array_merge($allQueries, $stmt['queries']);
            }
            if (isset($stmt['query_errors'])) {
                $allQueryErrors = array_merge($allQueryErrors, $stmt['query_errors']);
            }
        }

        $result = [
            'output' => self::$statements,
            'queries' => $allQueries,
        ];

        if ($allQueryErrors !== []) {
            $result['query_errors'] = $allQueryErrors;
        }

        return $result;
    }

    /**
     * @param  callable(array): void  $onEvent
     */
    public function executeStreaming(string $rawPHPCode, callable $onEvent): void
    {
        self::$statements = [];

        $this->registerVarDumperHandler($this->config);

        $rawPHPCode = $this->normalizePHPCode($rawPHPCode);

        $parserFactory = new ParserFactory;
        $parser = method_exists($parserFactory, 'createForHostVersion')
            ? $parserFactory->createForHostVersion()
            : $parserFactory->create(ParserFactory::PREFER_PHP7);
        $prettyPrinter = new Standard;

        foreach ($parser->parse($rawPHPCode) as $key => $stmt) {
            $code = $prettyPrinter->prettyPrint([$stmt]);
            $executableCode = $stmt instanceof Expression
                ? $prettyPrinter->prettyPrintExpr($stmt->expr)
                : $code;

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

            if (! $this->executeStreamingStatement($executableCode, $key, $onEvent)) {
                return;
            }
        }

        $onEvent(['type' => 'completed']);
    }

    protected function executeStreamingStatement(string $code, int $key, callable $onEvent): bool
    {
        $error = null;

        try {
            QueryCollector::start();
            $this->doExecuteStreaming($code, $key, $onEvent);
        } catch (BreakException $exception) {
            $error = [
                'type' => 'error',
                'index' => $key,
                'error' => [
                    'class' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'exit_code' => $exception->getCode(),
                ],
            ];
        } catch (\Throwable $exception) {
            $error = [
                'type' => 'error',
                'index' => $key,
                'error' => [
                    'class' => get_class($exception),
                    'message' => $exception->getMessage(),
                ],
            ];
        } finally {
            $queries = QueryCollector::stop();
        }

        if ($error !== null) {
            self::$statements[$key]['queries'] = $queries;

            $error['queries'] = $queries;
            $queryErrors = QueryCollector::errors();
            if ($queryErrors !== []) {
                self::$statements[$key]['query_errors'] = $queryErrors;
                $error['query_errors'] = $queryErrors;
            }

            $onEvent($error);

            return false;
        }

        self::$statements[$key]['queries'] = $queries;
        $queryErrors = QueryCollector::errors();
        if ($queryErrors !== []) {
            self::$statements[$key]['query_errors'] = $queryErrors;
        }

        $event = [
            'type' => 'statement.completed',
            'index' => $key,
            'queries' => $queries,
        ];
        if ($queryErrors !== []) {
            $event['query_errors'] = $queryErrors;
        }

        $onEvent($event);

        return true;
    }

    protected function doExecute(string $code): string
    {
        self::$dumpOccurred = false;
        $this->output = new BufferedOutput;
        $this->shell->setOutput($this->output);
        $return = $this->shell->execute($code, true);
        $output = $this->outputModifier->modify($this->cleanOutput($this->output->fetch()));

        if (! self::$dumpOccurred && $return !== null && ! ($return instanceof NoReturnValue)) {
            $presented = $this->config->getPresenter()->present($return);
            if ($output !== '') {
                $output .= "\n".$presented;
            } else {
                $output = $presented;
            }
        }

        return trim($output);
    }

    /**
     * @param  callable(array): void  $onEvent
     */
    protected function doExecuteStreaming(string $code, int $index, callable $onEvent): void
    {
        self::$dumpOccurred = false;
        $this->output = new StreamingOutput(function (string $chunk) use ($index, $onEvent): void {
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
        $return = $this->shell->execute($code, true);

        if (! self::$dumpOccurred && $return !== null && ! ($return instanceof NoReturnValue)) {
            $output = $this->config->getPresenter()->present($return);
            if ($output !== '') {
                $onEvent([
                    'type' => 'output',
                    'index' => $index,
                    'data' => $output,
                ]);
            }
        }
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

    protected function normalizePHPCode(string $rawPHPCode): string
    {
        if (preg_match('/^\s*<\?php(?:\s|$)/', $rawPHPCode) !== 1) {
            return "<?php\n".$rawPHPCode;
        }

        return $rawPHPCode;
    }

    protected function registerVarDumperHandler(Configuration $config): void
    {
        if (! class_exists(VarDumper::class)) {
            return;
        }

        VarDumper::setHandler(function ($var) use ($config) {
            self::$dumpOccurred = true;
            $output = $config->getPresenter()->present($var);
            if (isset(self::$statements[self::$current])) {
                if (isset(self::$statements[self::$current]['output']) && self::$statements[self::$current]['output'] !== '') {
                    self::$statements[self::$current]['output'] .= "\n".$output;
                } else {
                    self::$statements[self::$current]['output'] = $output;
                }
            }

            if ($this->output instanceof StreamingOutput) {
                $this->output->write($output, true);
            }

            return $output;
        });
    }

    public function getShell(): Shell
    {
        return $this->shell;
    }
}

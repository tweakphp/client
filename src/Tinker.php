<?php

namespace TweakPHP\Client;

use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Psy\Configuration;
use Psy\ExecutionLoopClosure;
use Psy\Shell;
use Symfony\Component\Console\Output\BufferedOutput;
use TweakPHP\Client\OutputModifiers\OutputModifier;

class Tinker
{
    protected BufferedOutput $output;

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
            $output = $this->doExecute($code);
            self::$statements[$key]['output'] = $output;
        }

        return [
            'output' => self::$statements,
        ];
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

    protected function createShell(BufferedOutput $output, Configuration $config): Shell
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
}

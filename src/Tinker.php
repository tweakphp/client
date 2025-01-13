<?php

namespace TweakPHP\Client;

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

    public function __construct(OutputModifier $outputModifier, Configuration $config)
    {
        $this->output = new BufferedOutput;

        $this->shell = $this->createShell($this->output, $config);

        $this->outputModifier = $outputModifier;
    }

    public function execute(string $phpCode): string
    {
        $phpCode = $this->removeComments($phpCode);

        $phpCode = explode("\n", $phpCode);

        $output = '';

        foreach ($phpCode as $key => $line) {
            $this->shell->addInput($line);
            $closure = new ExecutionLoopClosure($this->shell);
            $closure->execute();
            $result = $this->outputModifier->modify($this->cleanOutput($this->output->fetch()));
            if (trim($result) !== '' && trim($result) !== 'null') {
                $output .= 'Line '.$key + 1 .' Result:'.PHP_EOL;
                $output .= $result;
                $output .= PHP_EOL;
            }
        }

        return $output;
    }

    protected function createShell(BufferedOutput $output, Configuration $config): Shell
    {
        $shell = new Shell($config);

        $shell->setOutput($output);

        return $shell;
    }

    public function removeComments(string $code): string
    {
        $tokens = token_get_all("<?php\n".$code.'?>');
        $result = '';

        foreach ($tokens as $token) {
            if (is_array($token)) {
                [$id, $text] = $token;

                if (in_array($id, [T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG, T_CLOSE_TAG])) {
                    continue;
                }
                $result .= $text;
            } else {
                $result .= $token;
            }
        }

        return $result;
    }

    protected function cleanOutput(string $output): string
    {
        $output = preg_replace('/(?s)(<aside.*?<\/aside>)|Exit:  Ctrl\+D/ms', '$2', $output);

        $output = preg_replace('/(?s)(<whisper.*?<\/whisper>)|INFO  Ctrl\+D\./ms', '$2', $output);

        return trim($output);
    }
}

<?php

namespace TweakPHP\Client;

use Psy\ExecutionLoopClosure;
use Psy\Shell;
use Symfony\Component\Console\Output\BufferedOutput;
use TweakPHP\Client\OutputModifiers\OutputModifier;

class Tinker
{
    protected BufferedOutput $output;

    protected Shell $shell;

    protected OutputModifier $outputModifier;

    public function __construct(OutputModifier $outputModifier, $config)
    {
        $this->output = new BufferedOutput();

        $this->shell = $this->createShell($this->output, $config);

        $this->outputModifier = $outputModifier;
    }

    public function execute(string $phpCode): string
    {
        $phpCode = $this->removeComments($phpCode);

        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $this->shell->execute($phpCode);
        } else {
            $this->shell->addInput($phpCode);
            $closure = new ExecutionLoopClosure($this->shell);
            $closure->execute();
        }

        $output = $this->cleanOutput($this->output->fetch());

        return $this->outputModifier->modify($output);
    }

    protected function createShell(BufferedOutput $output, $config): Shell
    {
        $config->setHistoryFile(defined('PHP_WINDOWS_VERSION_BUILD') ? 'null' : '/dev/null');

        $shell = new Shell($config);

        $shell->setOutput($output);

        return $shell;
    }

    public function removeComments(string $code): string
    {
        $tokens = token_get_all("<?php\n" . $code . '?>');
        $result = '';

        foreach ($tokens as $token) {
            if (is_array($token)) {
                list($id, $text) = $token;

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

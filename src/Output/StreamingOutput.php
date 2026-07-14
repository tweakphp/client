<?php

namespace TweakPHP\Client\Output;

use Symfony\Component\Console\Output\Output;

class StreamingOutput extends Output
{
    private $onWrite;

    /**
     * @param  callable(string): void  $onWrite
     */
    public function __construct(callable $onWrite)
    {
        $this->onWrite = $onWrite;

        parent::__construct(self::VERBOSITY_NORMAL, false);
    }

    protected function doWrite(string $message, bool $newline): void
    {
        if ($message === '' && $newline) {
            return;
        }

        // PsySH uses this marker for the newline it adds after non-newline output.
        if ($newline && preg_match('/^<whisper>(?:\x{23ce}|\\\\n)<\/whisper>$/u', $message) === 1) {
            return;
        }

        ($this->onWrite)($message.($newline ? PHP_EOL : ''));
    }
}

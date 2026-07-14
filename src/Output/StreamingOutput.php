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
        ($this->onWrite)($message.($newline ? PHP_EOL : ''));
    }
}

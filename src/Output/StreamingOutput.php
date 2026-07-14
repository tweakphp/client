<?php

namespace TweakPHP\Client\Output;

use Symfony\Component\Console\Output\Output;

class StreamingOutput extends Output
{
    /**
     * @param  callable(string): void  $onWrite
     */
    public function __construct(private $onWrite)
    {
        parent::__construct(self::VERBOSITY_NORMAL, false);
    }

    protected function doWrite(string $message, bool $newline): void
    {
        ($this->onWrite)($message.($newline ? PHP_EOL : ''));
    }
}

<?php

namespace TweakPHP\Client;

interface LoaderInterface
{
    /**
     * @return string
     */
    public function name(): string;

    /**
     * @return string
     */
    public function version(): string;

    /**
     * @return void
     */
    public function execute(string $code);
}
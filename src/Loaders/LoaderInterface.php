<?php

namespace TweakPHP\Client\Loaders;

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
    public function initTinker();

    /**
     * @param string $code
     * @return void
     */
    public function execute(string $code);
}
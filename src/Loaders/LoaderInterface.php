<?php

namespace TweakPHP\Client\Loaders;

interface LoaderInterface
{
    /**
     * @param string $path
     * @return bool
     */
    public static function supports(string $path): bool;

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
    public function init();

    /**
     * @param string $code
     * @return void
     */
    public function execute(string $code);
}

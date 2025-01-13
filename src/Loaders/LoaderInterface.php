<?php

namespace TweakPHP\Client\Loaders;

interface LoaderInterface
{
    public static function supports(string $path): bool;

    public function name(): string;

    public function version(): string;

    /**
     * @return void
     */
    public function init();

    /**
     * @return string
     */
    public function execute(string $code);
}

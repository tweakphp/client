<?php

namespace TweakPHP\Client\Loaders;

interface LoaderInterface
{
    public static function supports(string $path): bool;

    public function name(): string;

    public function version(): string;

    public function init(): void;

    public function execute(string $code): array;
}

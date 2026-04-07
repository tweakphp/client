<?php

namespace TweakPHP\Client\Loaders;

class PlainPhpLoader extends BaseLoader
{
    public static function supports(string $path): bool
    {
        return is_dir($path);
    }

    public function __construct(string $path)
    {
        chdir($path);
    }

    public function name(): string
    {
        return 'PHP';
    }

    public function version(): string
    {
        return '';
    }
}

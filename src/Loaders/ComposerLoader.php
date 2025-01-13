<?php

namespace TweakPHP\Client\Loaders;

class ComposerLoader extends BaseLoader
{
    public static function supports(string $path): bool
    {
        return file_exists($path.'/vendor/autoload.php');
    }

    public function __construct(string $path)
    {
        require $path.'/vendor/autoload.php';
    }

    public function name(): string
    {
        return 'Composer Project';
    }

    public function version(): string
    {
        return '';
    }
}

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
        $autoloadPath = $path.'/vendor/autoload.php';

        if (! $this->isAutoloaderLoaded($autoloadPath)) {
            require_once $autoloadPath;
        }
    }

    private function isAutoloaderLoaded(string $autoloadPath): bool
    {
        $autoloadRealPath = dirname($autoloadPath).'/composer/autoload_real.php';

        if (! file_exists($autoloadRealPath)) {
            return false;
        }

        $contents = file_get_contents($autoloadRealPath);

        if ($contents === false || preg_match('/class ([A-Za-z0-9_]+)\s*\{/', $contents, $matches) !== 1) {
            return false;
        }

        return class_exists($matches[1], false);
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

<?php

namespace TweakPHP\Client\Loaders;

use Composer\InstalledVersions;

class PimcoreLoader extends BaseLoader
{
    public function __construct(string $path)
    {
        require_once $path . '/vendor/autoload.php';

        if (class_exists('\Pimcore\Bootstrap')) {
            \Pimcore\Bootstrap::setProjectRoot();
            \Pimcore\Bootstrap::bootstrap();
            \Pimcore\Bootstrap::kernel();
        }
    }

    public function name(): string
    {
        return 'Pimcore';
    }

    public function version(): string
    {
        return InstalledVersions::getPrettyVersion('pimcore/pimcore');
    }

    public static function supports(string $path): bool
    {
        return file_exists($path . '/vendor/autoload.php') && file_exists($path . '/vendor/pimcore/pimcore');
    }
}

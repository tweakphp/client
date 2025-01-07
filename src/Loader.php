<?php

namespace TweakPHP\Client;

use TweakPHP\Client\Loaders\ComposerLoader;
use TweakPHP\Client\Loaders\LaravelLoader;
use TweakPHP\Client\Loaders\LoaderInterface;
use TweakPHP\Client\Loaders\PimcoreLoader;
use TweakPHP\Client\Loaders\SymfonyLoader;
use TweakPHP\Client\Loaders\WordPressLoader;

class Loader
{
    /**
     * @param string $path
     * @return null|LoaderInterface
     */
    public static function load(string $path)
    {
        if (file_exists($path . '/vendor/autoload.php') && file_exists($path . '/bootstrap/app.php')) {
            return new LaravelLoader($path);
        }

        if (file_exists($path . '/vendor/autoload.php') && file_exists($path . '/symfony.lock') && file_exists($path . '/src/Kernel.php')) {
            return new SymfonyLoader($path);
        }

        if (file_exists($path . '/wp-load.php')) {
            return new WordPressLoader($path);
        }

        if (file_exists($path . '/vendor/autoload.php') && file_exists($path . '/vendor/pimcore/pimcore')) {
            return new PimcoreLoader($path);
        }

        if (file_exists($path . '/vendor/autoload.php')) {
            return new ComposerLoader($path);
        }

        return null;
    }
}

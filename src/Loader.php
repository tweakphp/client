<?php

namespace TweakPHP\Client;

use TweakPHP\Client\Loaders\ComposerLoader;
use TweakPHP\Client\Loaders\LaravelLoader;

class Loader
{
    /**
     * @param string $path
     * @return null|\TweakPHP\Client\Loaders\LoaderInterface
     */
    public static function load(string $path)
    {
        if (file_exists($path . '/vendor/autoload.php') && file_exists($path . '/bootstrap/app.php')) {
            return new LaravelLoader($path);
        }

        if (file_exists($path . '/vendor/autoload.php')) {
            return new ComposerLoader($path);
        }

        return null;
    }
}
<?php

namespace TweakPHP\Client;

use TweakPHP\Client\Laravel\LaravelLoader;

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

        return null;
    }
}
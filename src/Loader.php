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
        if (LaravelLoader::supports($path)) {
            return new LaravelLoader($path);
        }

        if (SymfonyLoader::supports($path)) {
            return new SymfonyLoader($path);
        }

        if (WordPressLoader::supports($path)) {
            return new WordPressLoader($path);
        }

        if (PimcoreLoader::supports($path)) {
            return new PimcoreLoader($path);
        }

        if (ComposerLoader::supports($path)) {
            return new ComposerLoader($path);
        }

        return null;
    }
}

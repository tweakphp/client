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
     * @return null|LoaderInterface
     */
    public static function load(string $path, ?string $encodedLoader = null)
    {
        if ($encodedLoader !== null) {
            $loaderClass = self::getLoaderClassFrom($encodedLoader);

            if ($loaderClass !== null) {
                return new $loaderClass($path);
            }
        }

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

    private static function getLoaderClassFrom(string $encodedLoader)
    {
        $declaredClassesBefore = get_declared_classes();
        $loader = base64_decode($encodedLoader);
        eval(str_replace('<?php', '', $loader));
        $declaredClassesAfter = get_declared_classes();
        $newClasses = array_diff($declaredClassesAfter, $declaredClassesBefore);
        if (empty($newClasses)) {
            return null;
        }

        return reset($newClasses);
    }
}

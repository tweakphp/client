<?php

namespace TweakPHP\Client\Loaders;

class ComposerLoader extends BaseLoader
{
    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        require $path . '/vendor/autoload.php';
    }

    public function name(): string
    {
        return 'Composer Project';
    }

    /**
     * @return string
     */
    public function version(): string
    {
        return "";
    }
}
<?php

declare(strict_types=1);

namespace TweakPHP\Client\Loaders;

class PimcoreLoader extends BaseLoader
{
    public function __construct(string $path)
    {
        // Include the Composer autoloader
        require_once $path . '/vendor/autoload.php';

        \Pimcore\Bootstrap::setProjectRoot();
        \Pimcore\Bootstrap::bootstrap();
        \Pimcore\Bootstrap::kernel();
    }


    public function name(): string
    {
        return 'Pimcore';
    }

    public function version(): string
    {
        return \Composer\InstalledVersions::getPrettyVersion('pimcore/pimcore');
    }

}

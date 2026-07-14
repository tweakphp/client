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
        if (! $this->isClientProject($path)) {
            require $path.'/vendor/autoload.php';
        }
    }

    private function isClientProject(string $path): bool
    {
        if (! class_exists('Composer\\InstalledVersions') || ! file_exists($path.'/composer.json')) {
            return false;
        }

        $composer = json_decode(file_get_contents($path.'/composer.json'), true);
        $rootPackage = \Composer\InstalledVersions::getRootPackage();

        return is_array($composer) && ($composer['name'] ?? null) === ($rootPackage['name'] ?? null);
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

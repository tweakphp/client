<?php

namespace TweakPHP\Client\Loaders;

use Throwable;

class LaravelLoader extends ComposerLoader
{
    private $app;

    /**
     * @param string $path
     * @return bool
     */
    public static function supports(string $path): bool
    {
        return file_exists($path . '/vendor/autoload.php') && file_exists($path . '/bootstrap/app.php');
    }

    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        parent::__construct($path);
        $this->app = require_once $path . '/bootstrap/app.php';
        $this->app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        $classAliases = require $path . '/vendor/composer/autoload_classmap.php';
        $vendorPath = dirname($path . '/vendor/composer/autoload_classmap.php', 2);
        foreach ($classAliases as $class => $path) {
            if (!str_contains($class, '\\')) {
                continue;
            }
            if (str_starts_with($path, $vendorPath)) {
                continue;
            }
            try {
                class_alias($class, class_basename($class));
            } catch (Throwable $e) {
            }
        }
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Laravel';
    }

    /**
     * @return string
     */
    public function version(): string
    {
        return $this->app->version();
    }
}

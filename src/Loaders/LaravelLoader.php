<?php

namespace TweakPHP\Client\Loaders;

use Throwable;

class LaravelLoader extends BaseLoader
{
    private $app;

    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        require $path . '/vendor/autoload.php';
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
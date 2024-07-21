<?php

namespace TweakPHP\Client\Laravel;

use TweakPHP\Client\LoaderInterface;
use Spatie\WebTinker\Tinker;
use Throwable;

class LaravelLoader implements LoaderInterface
{
    /**
     * @var string
     */
    private $path;

    private $app;

    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path = $path;
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

    public function execute(string $code)
    {
        $tinker = new Tinker(new CustomOutputModifier());
        $output = $tinker->execute($code);

        echo trim($output);
    }
}
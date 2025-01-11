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

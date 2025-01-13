<?php

namespace TweakPHP\Client\Loaders;

class LaravelLoader extends ComposerLoader
{
    private $app;

    public static function supports(string $path): bool
    {
        return file_exists($path.'/vendor/autoload.php') && file_exists($path.'/bootstrap/app.php');
    }

    public function __construct(string $path)
    {
        parent::__construct($path);
        $this->app = require_once $path.'/bootstrap/app.php';
        $this->app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    }

    public function name(): string
    {
        return 'Laravel';
    }

    public function version(): string
    {
        return $this->app->version();
    }
}

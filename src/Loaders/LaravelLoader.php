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

    public function casters(): array
    {
        $casters = [
            'Illuminate\Support\Collection' => 'Laravel\Tinker\TinkerCaster::castCollection',
            'Illuminate\Support\HtmlString' => 'Laravel\Tinker\TinkerCaster::castHtmlString',
            'Illuminate\Support\Stringable' => 'Laravel\Tinker\TinkerCaster::castStringable',
        ];

        if (class_exists('Illuminate\Database\Eloquent\Model')) {
            $casters['Illuminate\Database\Eloquent\Model'] = 'Laravel\Tinker\TinkerCaster::castModel';
        }

        if (class_exists('Illuminate\Process\ProcessResult')) {
            $casters['Illuminate\Process\ProcessResult'] = 'Laravel\Tinker\TinkerCaster::castProcessResult';
        }

        if (class_exists('Illuminate\Foundation\Application')) {
            $casters['Illuminate\Foundation\Application'] = 'Laravel\Tinker\TinkerCaster::castApplication';
        }

        return $casters;
    }
}

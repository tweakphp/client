<?php

namespace TweakPHP\Client\Loaders;

use Illuminate\Support\Env;
use Laravel\Tinker\ClassAliasAutoloader;

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

    public function init(): void
    {
        parent::init();

        $this->bootAliases();
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

    private function bootAliases(): void
    {
        if (! class_exists(ClassAliasAutoloader::class)) {
            return;
        }

        $config = $this->app->make('config');

        $path = Env::get('COMPOSER_VENDOR_DIR', $this->app->basePath().DIRECTORY_SEPARATOR.'vendor');

        ClassAliasAutoloader::register(
            $this->tinker->getShell(),
            $path.'/composer/autoload_classmap.php',
            $config->get('tinker.alias', []),
            $config->get('tinker.dont_alias', [])
        );
    }
}

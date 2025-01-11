<?php

namespace TweakPHP\Client\Loaders;

class SymfonyLoader extends ComposerLoader
{
    private $kernel;

    public static function supports(string $path): bool
    {
        return file_exists($path . '/vendor/autoload.php') &&
            file_exists($path . '/symfony.lock') &&
            file_exists($path . '/src/Kernel.php');
    }

    public function __construct(string $path)
    {
        parent::__construct($path);

        // Include the Composer autoloader
        require_once $path . '/vendor/autoload.php';

        // Initialize the Symfony Kernel
        $env = $_SERVER['APP_ENV'] ?? 'dev';
        $debug = ($_SERVER['APP_DEBUG'] ?? '1') === '1';

        $kernelClass = $this->findKernelClass($path);
        $this->kernel = new $kernelClass($env, $debug);
        $this->kernel->boot();
    }

    private function findKernelClass(string $path): string
    {
        require_once $path . '/src/Kernel.php';

        return 'App\\Kernel';
    }

    public function name(): string
    {
        return 'Symfony';
    }

    public function version(): string
    {
        if (class_exists('Symfony\Component\HttpKernel\Kernel')) {
            return \Symfony\Component\HttpKernel\Kernel::VERSION;
        }

        return '';
    }
}

<?php

namespace TweakPHP\Client\Loaders;

class SymfonyLoader extends ComposerLoader
{
    private $kernel;

    /**
     * @param string $path
     * @return bool
     */
    public static function supports(string $path): bool
    {
        return file_exists($path . '/vendor/autoload.php') && file_exists($path . '/symfony.lock') && file_exists($path . '/src/Kernel.php');
    }

    /**
     * @param string $path
     */
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

    /**
     * Find the application's Kernel class dynamically.
     *
     * @param string $path
     * @return string
     */
    private function findKernelClass(string $path): string
    {
        $kernelFile = $path . '/src/Kernel.php';
        if (!file_exists($kernelFile)) {
            throw new \RuntimeException('Kernel.php file not found in the src directory.');
        }

        require_once $kernelFile;
        return 'App\\Kernel';
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Symfony';
    }

    /**
     * @return string
     */
    public function version(): string
    {
        return \Symfony\Component\HttpKernel\Kernel::VERSION;
    }
}

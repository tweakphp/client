<?php

namespace TweakPHP\Client\Loaders;

use Symfony\Component\HttpKernel\Kernel;
use TweakPHP\Client\Database\QueryCollector;
use TweakPHP\Client\Database\SymfonyDoctrineQueryProvider;

class SymfonyLoader extends ComposerLoader
{
    private $kernel;

    public static function supports(string $path): bool
    {
        return file_exists($path.'/vendor/autoload.php') &&
            file_exists($path.'/symfony.lock') &&
            file_exists($path.'/src/Kernel.php');
    }

    public function __construct(string $path)
    {
        parent::__construct($path);

        require_once $path.'/vendor/autoload.php';

        $env = $_SERVER['APP_ENV'] ?? 'dev';
        $debug = ($_SERVER['APP_DEBUG'] ?? '1') === '1';

        $kernelClass = $this->findKernelClass($path);
        $this->kernel = new $kernelClass($env, $debug);
        $this->kernel->boot();

        QueryCollector::register(
            new SymfonyDoctrineQueryProvider($this->kernel->getContainer())
        );
    }

    private function findKernelClass(string $path): string
    {
        $kernelFile = $path.'/src/Kernel.php';
        require_once $kernelFile;

        foreach (get_declared_classes() as $class) {
            try {
                $reflection = new \ReflectionClass($class);
            } catch (\ReflectionException $exception) {
                continue;
            }

            $fileName = $reflection->getFileName();
            if ($fileName && realpath($fileName) === realpath($kernelFile) &&
                is_a($class, Kernel::class, true) &&
                $class !== Kernel::class) {
                return $class;
            }
        }

        throw new \RuntimeException('Unable to find a Symfony kernel class in src/Kernel.php.');
    }

    public function name(): string
    {
        return 'Symfony';
    }

    public function version(): string
    {
        if (class_exists('Symfony\Component\HttpKernel\Kernel')) {
            return Kernel::VERSION;
        }

        return '';
    }
}

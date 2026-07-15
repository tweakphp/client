<?php

namespace TweakPHP\Client\Loaders;

class PlainPhpLoader extends BaseLoader
{
    private string $path;

    public static function supports(string $path): bool
    {
        return is_dir($path);
    }

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function execute(string $code): array
    {
        return $this->executeInProjectDirectory(fn (): array => parent::execute($code));
    }

    public function executeStreaming(string $code, callable $onEvent): void
    {
        $this->executeInProjectDirectory(function () use ($code, $onEvent): void {
            parent::executeStreaming($code, $onEvent);
        });
    }

    private function executeInProjectDirectory(callable $callback)
    {
        $previousWorkingDirectory = getcwd();

        if (! chdir($this->path)) {
            throw new \RuntimeException("Unable to change to project directory: {$this->path}");
        }

        try {
            return $callback();
        } finally {
            if ($previousWorkingDirectory !== false) {
                chdir($previousWorkingDirectory);
            }
        }
    }

    public function name(): string
    {
        return 'PHP';
    }

    public function version(): string
    {
        return '';
    }
}

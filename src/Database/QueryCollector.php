<?php

namespace TweakPHP\Client\Database;

class QueryCollector
{
    protected static bool $enabled = false;

    /** @var QueryProviderInterface[] */
    protected static array $providers = [];

    protected static array $queries = [];

    protected static array $allCapturedQueries = [];

    protected static array $errors = [];

    public static function enable(bool $enabled = true): void
    {
        self::$enabled = $enabled;
    }

    public static function isEnabled(): bool
    {
        return self::$enabled;
    }

    public static function register(QueryProviderInterface $provider): void
    {
        if (! self::$enabled) {
            return;
        }

        self::$providers[] = $provider;
    }

    public static function reset(): void
    {
        self::$enabled = false;
        self::$providers = [];
        self::$queries = [];
        self::$allCapturedQueries = [];
        self::$errors = [];
    }

    public static function start(): void
    {
        if (! self::$enabled) {
            return;
        }

        self::$queries = [];
        self::$errors = [];

        foreach (self::$providers as $provider) {
            try {
                $provider->start();
            } catch (\Throwable $exception) {
                self::recordError($exception);
            }
        }
    }

    public static function stop(): array
    {
        if (! self::$enabled) {
            return [];
        }

        self::$queries = [];

        foreach (self::$providers as $provider) {
            try {
                self::$queries = array_merge(self::$queries, $provider->stop());
            } catch (\Throwable $exception) {
                self::recordError($exception);
            }
        }

        self::$allCapturedQueries = array_merge(self::$allCapturedQueries, self::$queries);

        return self::$queries;
    }

    public static function getCapturedQueries(): array
    {
        self::stop();

        return self::$allCapturedQueries;
    }

    public static function errors(): array
    {
        return self::$errors;
    }

    private static function recordError(\Throwable $exception): void
    {
        $error = [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
        ];

        if (! in_array($error, self::$errors, true)) {
            self::$errors[] = $error;
        }
    }
}

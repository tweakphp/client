<?php

namespace TweakPHP\Client\Database;

class QueryCollector
{
    /** @var QueryProviderInterface[] */
    protected static array $providers = [];

    protected static array $queries = [];

    protected static array $errors = [];

    public static function register(QueryProviderInterface $provider): void
    {
        self::$providers[] = $provider;
    }

    public static function reset(): void
    {
        self::$providers = [];
        self::$queries = [];
        self::$errors = [];
    }

    public static function start(): void
    {
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
        foreach (self::$providers as $provider) {
            try {
                self::$queries = array_merge(self::$queries, $provider->stop());
            } catch (\Throwable $exception) {
                self::recordError($exception);
            }
        }

        return self::$queries;
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

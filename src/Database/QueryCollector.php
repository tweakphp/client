<?php

namespace TweakPHP\Client\Database;

use Doctrine\DBAL\Logging\DebugStack;
use Illuminate\Support\Facades\DB;

class QueryCollector
{
    protected static array $queries = [];

    protected static bool $isLogging = false;

    protected static bool $listenerRegistered = false;

    protected static array $initialCounts = [];

    protected static array $errors = [];

    protected static $symfonyContainer = null;

    public static function setSymfonyContainer($container): void
    {
        self::$symfonyContainer = $container;
    }

    public static function start(): void
    {
        self::$queries = [];
        self::$isLogging = true;
        self::$initialCounts = [];
        self::$errors = [];

        if (! self::$listenerRegistered && class_exists(DB::class)) {
            try {
                DB::listen(function ($query) {
                    if (! self::$isLogging) {
                        return;
                    }
                    self::$queries[] = [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time,
                        'connection' => $query->connectionName,
                    ];
                });
                self::$listenerRegistered = true;
            } catch (\Throwable $e) {
                self::recordError($e);
            }
        }

        if (self::$symfonyContainer !== null) {
            try {
                $container = self::$symfonyContainer;
                if ($container->has('doctrine.debug_data_holder')) {
                    $holder = $container->get('doctrine.debug_data_holder');
                    $data = $holder->getData();
                    foreach ($data as $connName => $connQueries) {
                        self::$initialCounts[$connName] = count($connQueries);
                    }
                } elseif ($container->has('doctrine')) {
                    $doctrine = $container->get('doctrine');
                    foreach ($doctrine->getConnections() as $name => $connection) {
                        $logger = $connection->getConfiguration()->getSQLLogger();
                        if ($logger instanceof DebugStack) {
                            self::$initialCounts[$name] = count($logger->queries);
                        }
                    }
                }
            } catch (\Throwable $e) {
                self::recordError($e);
            }
        }
    }

    public static function stop(): array
    {
        self::$isLogging = false;

        if (self::$symfonyContainer !== null) {
            try {
                $container = self::$symfonyContainer;
                if ($container->has('doctrine.debug_data_holder')) {
                    $holder = $container->get('doctrine.debug_data_holder');
                    $data = $holder->getData();
                    foreach ($data as $connName => $connQueries) {
                        $initialCount = self::$initialCounts[$connName] ?? 0;
                        $newQueries = array_slice($connQueries, $initialCount);
                        foreach ($newQueries as $q) {
                            self::$queries[] = [
                                'sql' => $q['sql'] ?? '',
                                'bindings' => $q['params'] ?? [],
                                'time' => $q['executionMS'] ?? 0,
                                'connection' => $connName,
                            ];
                        }
                    }
                } elseif ($container->has('doctrine')) {
                    $doctrine = $container->get('doctrine');
                    foreach ($doctrine->getConnections() as $name => $connection) {
                        $logger = $connection->getConfiguration()->getSQLLogger();
                        if ($logger instanceof DebugStack) {
                            $initialCount = self::$initialCounts[$name] ?? 0;
                            $newQueries = array_slice($logger->queries, $initialCount);
                            foreach ($newQueries as $q) {
                                self::$queries[] = [
                                    'sql' => $q['sql'] ?? '',
                                    'bindings' => $q['params'] ?? [],
                                    'time' => $q['executionMS'] ?? 0,
                                    'connection' => $name,
                                ];
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                self::recordError($e);
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

<?php

namespace TweakPHP\Client\Database;

use Doctrine\DBAL\Logging\DebugStack;

class SymfonyDoctrineQueryProvider implements QueryProviderInterface
{
    private array $initialCounts = [];

    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function start(): void
    {
        $this->initialCounts = [];

        if ($this->container->has('doctrine.debug_data_holder')) {
            $holder = $this->container->get('doctrine.debug_data_holder');
            foreach ($holder->getData() as $name => $queries) {
                $this->initialCounts[$name] = count($queries);
            }

            return;
        }

        if (! $this->container->has('doctrine')) {
            return;
        }

        foreach ($this->container->get('doctrine')->getConnections() as $name => $connection) {
            $queries = $this->getQueries($connection, $name);
            if ($queries !== null) {
                $this->initialCounts[$name] = count($queries);
            }
        }
    }

    public function stop(): array
    {
        $queries = [];

        if ($this->container->has('doctrine.debug_data_holder')) {
            $data = $this->container->get('doctrine.debug_data_holder')->getData();
            foreach ($data as $name => $connectionQueries) {
                $queries = array_merge($queries, $this->normalize(
                    array_slice($connectionQueries, $this->initialCounts[$name] ?? 0),
                    $name
                ));
            }

            return $queries;
        }

        if (! $this->container->has('doctrine')) {
            return $queries;
        }

        foreach ($this->container->get('doctrine')->getConnections() as $name => $connection) {
            $connectionQueries = $this->getQueries($connection, $name);
            if ($connectionQueries !== null) {
                $queries = array_merge($queries, $this->normalize(
                    array_slice($connectionQueries, $this->initialCounts[$name] ?? 0),
                    $name
                ));
            }
        }

        return $queries;
    }

    private function getQueries($connection, string $connectionName): ?array
    {
        $configuration = $connection->getConfiguration();

        if (method_exists($configuration, 'getSQLLogger')) {
            $logger = $configuration->getSQLLogger();
            if ($logger instanceof DebugStack) {
                return $logger->queries;
            }
        }

        if (! method_exists($configuration, 'getMiddlewares')) {
            return null;
        }

        foreach ($configuration->getMiddlewares() as $middleware) {
            if (! property_exists($middleware, 'debugDataHolder')) {
                continue;
            }

            $property = new \ReflectionProperty($middleware, 'debugDataHolder');
            $property->setAccessible(true);
            $data = $property->getValue($middleware)->getData();

            return $data[$connectionName] ?? [];
        }

        return null;
    }

    private function normalize(array $queries, string $connection): array
    {
        return array_map(static function (array $query) use ($connection): array {
            return [
                'sql' => $query['sql'] ?? '',
                'bindings' => $query['params'] ?? [],
                'time' => $query['executionMS'] ?? 0,
                'connection' => $connection,
            ];
        }, $queries);
    }
}

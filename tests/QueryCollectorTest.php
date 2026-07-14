<?php

namespace Illuminate\Support\Facades {
    if (! class_exists(DB::class)) {
        class DB
        {
            public static bool $connectionEnabled = false;

            public static $listener = null;

            public static function connection()
            {
                return new class
                {
                    public function enableQueryLog()
                    {
                        DB::$connectionEnabled = true;
                    }
                };
            }

            public static function listen($callback)
            {
                self::$listener = $callback;
            }

            public static function triggerQuery($query)
            {
                if (self::$listener) {
                    (self::$listener)($query);
                }
            }
        }
    }
}

namespace Symfony\Component\DependencyInjection {
    if (! interface_exists(ContainerInterface::class)) {
        interface ContainerInterface
        {
            public function has(string $id): bool;

            public function get(string $id): ?object;
        }
    }
}

namespace Doctrine\DBAL\Logging {
    if (! class_exists(DebugStack::class)) {
        class DebugStack
        {
            public array $queries = [];
        }
    }
}

namespace TweakPHP\Client\Tests {
    use Doctrine\DBAL\Logging\DebugStack;
    use Illuminate\Support\Facades\DB;
    use PHPUnit\Framework\TestCase;
    use Symfony\Component\DependencyInjection\ContainerInterface;
    use TweakPHP\Client\Database\QueryCollector;

    class QueryCollectorTest extends TestCase
    {
        public function test_query_collector_with_laravel_db()
        {
            QueryCollector::start();

            $this->assertNotNull(DB::$listener);

            $queryObj = new \stdClass;
            $queryObj->sql = 'SELECT * FROM users WHERE id = ?';
            $queryObj->bindings = [1];
            $queryObj->time = 12.34;
            $queryObj->connectionName = 'mysql';

            DB::triggerQuery($queryObj);

            $queries = QueryCollector::stop();

            $this->assertCount(1, $queries);
            $this->assertEquals('SELECT * FROM users WHERE id = ?', $queries[0]['sql']);
            $this->assertEquals([1], $queries[0]['bindings']);
            $this->assertEquals(12.34, $queries[0]['time']);
            $this->assertEquals('mysql', $queries[0]['connection']);
        }

        public function test_query_collector_captures_queries_from_secondary_connections(): void
        {
            QueryCollector::start();

            $queryObj = new \stdClass;
            $queryObj->sql = 'SELECT * FROM reports';
            $queryObj->bindings = [];
            $queryObj->time = 3.1;
            $queryObj->connectionName = 'reporting';

            DB::triggerQuery($queryObj);

            $queries = QueryCollector::stop();

            $this->assertCount(1, $queries);
            $this->assertSame('reporting', $queries[0]['connection']);
        }

        public function test_query_collector_with_symfony_doctrine3()
        {
            $containerMock = $this->createMock(ContainerInterface::class);
            $debugDataHolderMock = $this->getMockBuilder(\stdClass::class)
                ->addMethods(['getData'])
                ->getMock();

            $debugDataHolderMock->method('getData')->willReturnOnConsecutiveCalls(
                [],
                [
                    'default' => [
                        [
                            'sql' => 'SELECT * FROM posts',
                            'params' => [],
                            'executionMS' => 5.2,
                        ],
                    ],
                ]
            );

            $containerMock->method('has')
                ->willReturnMap([
                    ['doctrine.debug_data_holder', true],
                    ['doctrine', false],
                ]);

            $containerMock->method('get')
                ->willReturnMap([
                    ['doctrine.debug_data_holder', $debugDataHolderMock],
                ]);

            QueryCollector::setSymfonyContainer($containerMock);
            QueryCollector::start();
            $queries = QueryCollector::stop();

            $this->assertCount(1, $queries);
            $this->assertEquals('SELECT * FROM posts', $queries[0]['sql']);
            $this->assertEquals([], $queries[0]['bindings']);
            $this->assertEquals(5.2, $queries[0]['time']);
            $this->assertEquals('default', $queries[0]['connection']);

            QueryCollector::setSymfonyContainer(null);
        }

        public function test_query_collector_with_symfony_doctrine2()
        {
            $containerMock = $this->createMock(ContainerInterface::class);
            $doctrineMock = $this->getMockBuilder(\stdClass::class)
                ->addMethods(['getConnections'])
                ->getMock();

            $connMock = $this->getMockBuilder(\stdClass::class)
                ->addMethods(['getConfiguration'])
                ->getMock();

            $configMock = $this->getMockBuilder(\stdClass::class)
                ->addMethods(['getSQLLogger'])
                ->getMock();

            $loggerMock = new DebugStack;
            $loggerMock->queries = [];

            $doctrineMock->method('getConnections')->willReturn([
                'default' => $connMock,
            ]);

            $connMock->method('getConfiguration')->willReturn($configMock);
            $configMock->method('getSQLLogger')->willReturn($loggerMock);

            $containerMock->method('has')
                ->willReturnMap([
                    ['doctrine.debug_data_holder', false],
                    ['doctrine', true],
                ]);

            $containerMock->method('get')
                ->willReturnMap([
                    ['doctrine', $doctrineMock],
                ]);

            QueryCollector::setSymfonyContainer($containerMock);
            QueryCollector::start();

            $loggerMock->queries[] = [
                'sql' => 'SELECT * FROM comments',
                'params' => [1],
                'executionMS' => 1.5,
            ];

            $queries = QueryCollector::stop();

            $this->assertCount(1, $queries);
            $this->assertEquals('SELECT * FROM comments', $queries[0]['sql']);
            $this->assertEquals([1], $queries[0]['bindings']);
            $this->assertEquals(1.5, $queries[0]['time']);
            $this->assertEquals('default', $queries[0]['connection']);

            QueryCollector::setSymfonyContainer(null);
        }

        public function test_query_collector_exposes_instrumentation_errors(): void
        {
            $containerMock = $this->createMock(ContainerInterface::class);
            $containerMock->method('has')->willThrowException(new \RuntimeException('container failed'));

            QueryCollector::setSymfonyContainer($containerMock);
            QueryCollector::start();
            QueryCollector::stop();

            $this->assertSame([
                [
                    'class' => 'RuntimeException',
                    'message' => 'container failed',
                ],
            ], QueryCollector::errors());

            QueryCollector::setSymfonyContainer(null);
        }
    }
}

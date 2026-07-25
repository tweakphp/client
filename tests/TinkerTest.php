<?php

namespace TweakPHP\Client\Tests;

use PhpParser\Error;
use PHPUnit\Framework\TestCase;
use Psy\Configuration as ConfigurationAlias;
use Psy\VersionUpdater\Checker;
use TweakPHP\Client\Database\QueryCollector;
use TweakPHP\Client\Database\QueryProviderInterface;
use TweakPHP\Client\OutputModifiers\CustomOutputModifier;
use TweakPHP\Client\Psy\Configuration;
use TweakPHP\Client\Tinker;

class TinkerTest extends TestCase
{
    public function test_tinker_execute()
    {
        $config = new Configuration([
            'configFile' => null,
        ]);
        $config->setUpdateCheck(Checker::NEVER);
        if (method_exists($config, 'setInteractiveMode')) {
            $config->setInteractiveMode(ConfigurationAlias::INTERACTIVE_MODE_DISABLED);
        }
        if (method_exists($config, 'setColorMode')) {
            $config->setColorMode(ConfigurationAlias::COLOR_MODE_DISABLED);
        }
        $config->setRawOutput(false);
        $config->setTheme([
            'prompt' => '',
        ]);
        $config->setHistoryFile(defined('PHP_WINDOWS_VERSION_BUILD') ? 'null' : '/dev/null');
        $config->setUsePcntl(false);

        $tinker = new Tinker(new CustomOutputModifier, $config);

        $result = $tinker->execute("echo 'Hello World';");

        $this->assertArrayHasKey('output', $result);
        $this->assertArrayHasKey('queries', $result);
        $this->assertNotEmpty($result['output']);
        $this->assertEquals('Hello World', $result['output'][0]['output']);
    }

    public function test_execute_resets_statements_between_calls()
    {
        $config = new Configuration([
            'configFile' => null,
        ]);
        $config->setUpdateCheck(Checker::NEVER);
        if (method_exists($config, 'setInteractiveMode')) {
            $config->setInteractiveMode(ConfigurationAlias::INTERACTIVE_MODE_DISABLED);
        }
        if (method_exists($config, 'setColorMode')) {
            $config->setColorMode(ConfigurationAlias::COLOR_MODE_DISABLED);
        }
        $config->setRawOutput(false);
        $config->setTheme([
            'prompt' => '',
        ]);
        $config->setHistoryFile(defined('PHP_WINDOWS_VERSION_BUILD') ? 'null' : '/dev/null');
        $config->setUsePcntl(false);

        $tinker = new Tinker(new CustomOutputModifier, $config);

        $tinker->execute("echo 'first';");
        $result = $tinker->execute("echo 'second';");

        $this->assertCount(1, $result['output']);
        $this->assertEquals('second', $result['output'][0]['output']);
    }

    public function test_execute_propagates_runtime_exceptions(): void
    {
        $tinker = $this->createTinker();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('boom');

        $tinker->execute('throw new RuntimeException("boom");');
    }

    public function test_execute_preserves_marker_text_and_php_tag_strings(): void
    {
        $tinker = $this->createTinker();

        $result = $tinker->execute('echo "before TWEAKPHP_END after"; echo " <?php";');

        $this->assertSame('before TWEAKPHP_END after', $result['output'][0]['output']);
        $this->assertSame('<?php', $result['output'][1]['output']);
    }

    public function test_query_collection_stops_when_execution_throws()
    {
        $config = new Configuration([
            'configFile' => null,
        ]);
        $config->setUpdateCheck(Checker::NEVER);
        if (method_exists($config, 'setInteractiveMode')) {
            $config->setInteractiveMode(ConfigurationAlias::INTERACTIVE_MODE_DISABLED);
        }
        if (method_exists($config, 'setColorMode')) {
            $config->setColorMode(ConfigurationAlias::COLOR_MODE_DISABLED);
        }
        $config->setRawOutput(false);
        $config->setTheme([
            'prompt' => '',
        ]);
        $config->setHistoryFile(defined('PHP_WINDOWS_VERSION_BUILD') ? 'null' : '/dev/null');
        $config->setUsePcntl(false);

        $tinker = new class(new CustomOutputModifier, $config) extends Tinker
        {
            protected function doExecute(string $code): string
            {
                throw new \RuntimeException('Execution failed');
            }
        };

        try {
            $tinker->execute("echo 'failure';");
            $this->fail('Expected execution to throw.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Execution failed', $exception->getMessage());
        }

        $this->assertSame([], QueryCollector::errors());
    }

    public function test_execute_streaming_emits_output_for_each_statement()
    {
        $config = new Configuration([
            'configFile' => null,
        ]);
        $config->setUpdateCheck(Checker::NEVER);
        if (method_exists($config, 'setInteractiveMode')) {
            $config->setInteractiveMode(ConfigurationAlias::INTERACTIVE_MODE_DISABLED);
        }
        if (method_exists($config, 'setColorMode')) {
            $config->setColorMode(ConfigurationAlias::COLOR_MODE_DISABLED);
        }
        $config->setRawOutput(false);
        $config->setTheme([
            'prompt' => '',
        ]);
        $config->setHistoryFile(defined('PHP_WINDOWS_VERSION_BUILD') ? 'null' : '/dev/null');
        $config->setUsePcntl(false);

        $tinker = new Tinker(new CustomOutputModifier, $config);
        $events = [];

        $tinker->executeStreaming("echo 'first'; echo 'second';", function (array $event) use (&$events): void {
            $events[] = $event;
        });

        $output = array_column(
            array_filter($events, fn (array $event): bool => $event['type'] === 'output'),
            'data'
        );
        $output = implode('', $output);

        $this->assertStringContainsString('first', $output);
        $this->assertStringContainsString('second', $output);
        $this->assertLessThan(strpos($output, 'second'), strpos($output, 'first'));
        $this->assertSame(
            'completed',
            end($events)['type']
        );
        $this->assertStringNotContainsString('<whisper>', $output);
    }

    public function test_execute_streaming_emits_error_when_a_statement_fails()
    {
        $config = new Configuration([
            'configFile' => null,
        ]);
        $config->setUpdateCheck(Checker::NEVER);
        if (method_exists($config, 'setInteractiveMode')) {
            $config->setInteractiveMode(ConfigurationAlias::INTERACTIVE_MODE_DISABLED);
        }
        if (method_exists($config, 'setColorMode')) {
            $config->setColorMode(ConfigurationAlias::COLOR_MODE_DISABLED);
        }
        $config->setRawOutput(false);
        $config->setTheme([
            'prompt' => '',
        ]);
        $config->setHistoryFile(defined('PHP_WINDOWS_VERSION_BUILD') ? 'null' : '/dev/null');
        $config->setUsePcntl(false);

        $tinker = new class(new CustomOutputModifier, $config) extends Tinker
        {
            protected function doExecuteStreaming(string $code, int $index, callable $onEvent): void
            {
                throw new \RuntimeException('Execution failed');
            }
        };
        $events = [];

        try {
            $tinker->executeStreaming("echo 'failure';", function (array $event) use (&$events): void {
                $events[] = $event;
            });
        } finally {
            QueryCollector::reset();
        }

        $this->assertSame('error', end($events)['type']);
        $this->assertSame('Execution failed', end($events)['error']['message']);
    }

    public function test_execute_streaming_includes_query_collection_errors_in_failure_event(): void
    {
        $config = new Configuration([
            'configFile' => null,
        ]);
        $config->setUpdateCheck(Checker::NEVER);
        if (method_exists($config, 'setInteractiveMode')) {
            $config->setInteractiveMode(ConfigurationAlias::INTERACTIVE_MODE_DISABLED);
        }
        if (method_exists($config, 'setColorMode')) {
            $config->setColorMode(ConfigurationAlias::COLOR_MODE_DISABLED);
        }
        $config->setRawOutput(false);
        $config->setTheme([
            'prompt' => '',
        ]);
        $config->setHistoryFile(defined('PHP_WINDOWS_VERSION_BUILD') ? 'null' : '/dev/null');
        $config->setUsePcntl(false);

        $tinker = new class(new CustomOutputModifier, $config) extends Tinker
        {
            protected function doExecuteStreaming(string $code, int $index, callable $onEvent): void
            {
                throw new \RuntimeException('Execution failed');
            }
        };
        $provider = $this->createMock(QueryProviderInterface::class);
        $provider->method('stop')->willThrowException(new \RuntimeException('query collection failed'));
        QueryCollector::register($provider);
        $events = [];

        try {
            $tinker->executeStreaming("echo 'failure';", function (array $event) use (&$events): void {
                $events[] = $event;
            });
        } finally {
            QueryCollector::reset();
        }

        $error = end($events);
        $this->assertSame('error', $error['type']);
        $this->assertSame([
            [
                'class' => 'RuntimeException',
                'message' => 'query collection failed',
            ],
        ], $error['query_errors']);
    }

    public function test_execute_streaming_preserves_user_output_whitespace(): void
    {
        $tinker = $this->createTinker();
        $events = [];

        $tinker->executeStreaming("echo '  first\\nsecond  ';", function (array $event) use (&$events): void {
            $events[] = $event;
        });

        $output = implode('', array_column(
            array_filter($events, fn (array $event): bool => $event['type'] === 'output'),
            'data'
        ));

        $this->assertSame('  first\\nsecond  ', $output);
    }

    public function test_execute_streaming_emits_exit_code_for_exit(): void
    {
        $tinker = $this->createTinker();
        $events = [];

        $tinker->executeStreaming('exit(7);', function (array $event) use (&$events): void {
            $events[] = $event;
        });

        $error = end($events);
        $this->assertSame('error', $error['type']);
        $this->assertSame(7, $error['error']['exit_code']);
    }

    public function test_execute_streaming_propagates_parse_errors(): void
    {
        $this->expectException(Error::class);

        $this->createTinker()->executeStreaming('echo ;', static function (array $event): void {});
    }

    public function test_execute_captures_symfony_var_dumper_output(): void
    {
        $tinker = $this->createTinker();

        ob_start();
        $result = $tinker->execute('dump("hello");');
        $stdout = ob_get_clean();

        $this->assertSame('', $stdout);
        $this->assertNotEmpty($result['output']);
        $this->assertSame('"hello"', $result['output'][0]['output']);
        $this->assertStringContainsString('hello', $result['output'][0]['html']);
    }

    public function test_execute_evaluates_expressions_ending_with_semicolon(): void
    {
        $tinker = $this->createTinker();

        $result = $tinker->execute('"hello";');

        $this->assertNotEmpty($result['output']);
        $this->assertSame('"hello"', $result['output'][0]['output']);
        $this->assertStringContainsString('hello', $result['output'][0]['html']);
    }

    public function test_execute_handles_multiple_dumps_in_single_statement(): void
    {
        $tinker = $this->createTinker();

        ob_start();
        $result = $tinker->execute('foreach (["one", "two"] as $item) { dump($item); }');
        $stdout = ob_get_clean();

        $this->assertSame('', $stdout);
        $this->assertNotEmpty($result['output']);
        $this->assertStringContainsString('"one"', $result['output'][0]['output']);
        $this->assertStringContainsString('"two"', $result['output'][0]['output']);
        $this->assertStringContainsString('one', $result['output'][0]['html']);
        $this->assertStringContainsString('two', $result['output'][0]['html']);
    }

    public function test_execute_handles_mixed_statements_with_dump_and_expressions(): void
    {
        $tinker = $this->createTinker();

        $result = $tinker->execute('$x = 10; dump($x); $x + 5;');

        $this->assertCount(3, $result['output']);
        $this->assertSame('10', $result['output'][0]['output']);
        $this->assertSame('10', $result['output'][1]['output']);
        $this->assertSame('15', $result['output'][2]['output']);
    }

    public function test_execute_streaming_emits_dump_output(): void
    {
        $tinker = $this->createTinker();
        $events = [];

        ob_start();
        $tinker->executeStreaming('dump("stream_test");', function (array $event) use (&$events): void {
            $events[] = $event;
        });
        $stdout = ob_get_clean();

        $this->assertSame('', $stdout);

        $outputEvents = array_filter($events, fn (array $event): bool => $event['type'] === 'output');
        $output = implode('', array_column($outputEvents, 'data'));

        $this->assertStringContainsString('stream_test', $output);
    }

    public function test_execute_streaming_handles_multiple_dumps_and_mixed_outputs(): void
    {
        $tinker = $this->createTinker();
        $events = [];

        ob_start();
        $tinker->executeStreaming('foreach (["foo", "bar"] as $item) { dump($item); }', function (array $event) use (&$events): void {
            $events[] = $event;
        });
        $stdout = ob_get_clean();

        $this->assertSame('', $stdout);

        $outputEvents = array_filter($events, fn (array $event): bool => $event['type'] === 'output');
        $output = implode('', array_column($outputEvents, 'data'));

        $this->assertStringContainsString('foo', $output);
        $this->assertStringContainsString('bar', $output);
    }

    public function test_execute_streaming_emits_expression_output(): void
    {
        $tinker = $this->createTinker();
        $events = [];

        $tinker->executeStreaming('"stream_expr";', function (array $event) use (&$events): void {
            $events[] = $event;
        });

        $outputEvents = array_filter($events, fn (array $event): bool => $event['type'] === 'output');
        $output = implode('', array_column($outputEvents, 'data'));

        $this->assertStringContainsString('stream_expr', $output);
    }

    public function test_execute_handles_non_expression_statements(): void
    {
        $tinker = $this->createTinker();

        $result = $tinker->execute('function myTestFunc() { return 42; } myTestFunc();');

        $this->assertCount(2, $result['output']);
        $this->assertSame('', $result['output'][0]['output']);
        $this->assertSame('42', $result['output'][1]['output']);
    }

    private function createTinker(): Tinker
    {
        $config = new Configuration([
            'configFile' => null,
        ]);
        $config->setUpdateCheck(Checker::NEVER);
        if (method_exists($config, 'setInteractiveMode')) {
            $config->setInteractiveMode(ConfigurationAlias::INTERACTIVE_MODE_DISABLED);
        }
        if (method_exists($config, 'setColorMode')) {
            $config->setColorMode(ConfigurationAlias::COLOR_MODE_DISABLED);
        }
        $config->setRawOutput(false);
        $config->setTheme([
            'prompt' => '',
        ]);
        $config->setHistoryFile(defined('PHP_WINDOWS_VERSION_BUILD') ? 'null' : '/dev/null');
        $config->setUsePcntl(false);

        return new Tinker(new CustomOutputModifier, $config);
    }
}

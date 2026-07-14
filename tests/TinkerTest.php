<?php

namespace TweakPHP\Client\Tests;

use PhpParser\Error;
use PHPUnit\Framework\TestCase;
use Psy\Configuration as ConfigurationAlias;
use Psy\VersionUpdater\Checker;
use TweakPHP\Client\Database\QueryCollector;
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

        $reflection = new \ReflectionClass(QueryCollector::class);
        $logging = $reflection->getProperty('isLogging');
        $logging->setAccessible(true);

        $this->assertFalse($logging->getValue());
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

        $tinker->executeStreaming("echo 'failure';", function (array $event) use (&$events): void {
            $events[] = $event;
        });

        $this->assertSame('error', end($events)['type']);
        $this->assertSame('Execution failed', end($events)['error']['message']);
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

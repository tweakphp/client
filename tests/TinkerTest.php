<?php

namespace TweakPHP\Client\Tests;

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
    }
}

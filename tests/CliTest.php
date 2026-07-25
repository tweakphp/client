<?php

namespace TweakPHP\Client\Tests;

use PHPUnit\Framework\TestCase;
use TweakPHP\Client\Cli;

class CliTest extends TestCase
{
    public function test_get_argument()
    {
        $oldArgv = $_SERVER['argv'] ?? [];

        $_SERVER['argv'] = ['bin/console', '--loader=LaravelLoader', '--path=/some/path'];

        $this->assertEquals('LaravelLoader', Cli::getArgument('loader'));
        $this->assertEquals('/some/path', Cli::getArgument('path'));
        $this->assertEquals('', Cli::getArgument('nonexistent'));

        $_SERVER['argv'] = $oldArgv;
    }

    public function test_execute_reports_runtime_errors(): void
    {
        $code = base64_encode('throw new RuntimeException("boom");');
        $command = sprintf(
            '%s %s %s %s %s',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(__DIR__.'/../index.php'),
            escapeshellarg(__DIR__.'/..'),
            'execute',
            escapeshellarg($code)
        );

        exec($command, $output, $exitCode);

        $this->assertSame(1, $exitCode);
        $this->assertStringStartsWith('TWEAKPHP_ERROR:', implode(PHP_EOL, $output));
    }

    public function test_execute_reports_runtime_errors_with_queries_array(): void
    {
        $code = base64_encode('throw new RuntimeException("boom");');
        $command = sprintf(
            '%s %s %s %s %s',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(__DIR__.'/../index.php'),
            escapeshellarg(__DIR__.'/..'),
            'execute',
            escapeshellarg($code)
        );

        exec($command, $output, $exitCode);

        $this->assertSame(1, $exitCode);
        $rawOutput = implode(PHP_EOL, $output);
        $this->assertStringStartsWith('TWEAKPHP_ERROR:', $rawOutput);

        $jsonStr = substr($rawOutput, strlen('TWEAKPHP_ERROR:'));
        $data = json_decode($jsonStr, true);

        $this->assertIsArray($data);
        $this->assertSame('RuntimeException', $data['class']);
        $this->assertSame('boom', $data['message']);
        $this->assertArrayHasKey('queries', $data);
        $this->assertIsArray($data['queries']);
    }
}

<?php

namespace TweakPHP\Client\Tests;

use PHPUnit\Framework\TestCase;

class ExecuteStreamTest extends TestCase
{
    public function test_started_event_with_pid_is_the_first_stream_line()
    {
        $lines = $this->runClient('execute-stream', 'echo 1;');

        $this->assertStringStartsWith('TWEAKPHP_STREAM:', $lines[0]);

        $event = json_decode(substr($lines[0], strlen('TWEAKPHP_STREAM:')), true);
        $this->assertSame(JSON_ERROR_NONE, json_last_error());
        $this->assertSame('started', $event['type']);
        $this->assertArrayHasKey('pid', $event);
        $this->assertIsInt($event['pid']);
        $this->assertGreaterThan(0, $event['pid']);
    }

    public function test_execute_stream_emits_the_full_event_sequence()
    {
        $lines = $this->runClient('execute-stream', 'echo 1;');

        $types = [];
        foreach ($lines as $line) {
            $this->assertStringStartsWith('TWEAKPHP_STREAM:', $line);

            $event = json_decode(substr($line, strlen('TWEAKPHP_STREAM:')), true);
            $this->assertSame(JSON_ERROR_NONE, json_last_error());
            $types[] = $event['type'];
        }

        $this->assertSame(
            ['started', 'statement.started', 'output', 'statement.completed', 'completed'],
            $types
        );
    }

    public function test_execute_does_not_emit_stream_events()
    {
        $lines = $this->runClient('execute', 'echo 1;');

        $this->assertNotEmpty($lines);
        $this->assertStringStartsWith('TWEAKPHP_RESULT:', $lines[0]);

        foreach ($lines as $line) {
            $this->assertStringNotContainsString('TWEAKPHP_STREAM:', $line);
        }
    }

    private function runClient(string $command, string $code): array
    {
        $projectPath = sys_get_temp_dir().'/tweakphp-client-test-'.uniqid();
        mkdir($projectPath);

        try {
            $executable = sprintf(
                '%s %s %s %s %s 2>/dev/null',
                escapeshellarg(PHP_BINARY),
                escapeshellarg(__DIR__.'/../index.php'),
                escapeshellarg($projectPath),
                escapeshellarg($command),
                escapeshellarg(base64_encode($code))
            );

            exec($executable, $lines, $exitCode);

            $this->assertSame(0, $exitCode);
            $this->assertNotEmpty($lines);

            return $lines;
        } finally {
            @rmdir($projectPath);
        }
    }
}

<?php

namespace TweakPHP\Client\Tests;

use PHPUnit\Framework\TestCase;

class ExecuteStreamTest extends TestCase
{
    public function test_started_event_with_pid_is_the_first_stream_line()
    {
        $projectPath = sys_get_temp_dir().'/tweakphp-client-execute-stream-'.uniqid();
        mkdir($projectPath);

        try {
            $code = base64_encode('echo 1;');

            $command = sprintf(
                '%s %s %s execute-stream %s 2>/dev/null',
                escapeshellarg(PHP_BINARY),
                escapeshellarg(__DIR__.'/../index.php'),
                escapeshellarg($projectPath),
                escapeshellarg($code)
            );

            exec($command, $lines, $exitCode);

            $this->assertSame(0, $exitCode);
            $this->assertNotEmpty($lines);

            $this->assertStringStartsWith('TWEAKPHP_STREAM:', $lines[0]);

            $event = json_decode(substr($lines[0], strlen('TWEAKPHP_STREAM:')), true);
            $this->assertSame(JSON_ERROR_NONE, json_last_error());
            $this->assertSame('started', $event['type']);
            $this->assertArrayHasKey('pid', $event);
            $this->assertIsInt($event['pid']);
            $this->assertGreaterThan(0, $event['pid']);

            foreach ($lines as $line) {
                $this->assertStringStartsWith('TWEAKPHP_STREAM:', $line);
            }

            $types = [];
            foreach ($lines as $line) {
                $event = json_decode(substr($line, strlen('TWEAKPHP_STREAM:')), true);
                $types[] = $event['type'];
            }
            $this->assertSame(
                ['started', 'statement.started', 'output', 'statement.completed', 'completed'],
                $types
            );
        } finally {
            @rmdir($projectPath);
        }
    }
}

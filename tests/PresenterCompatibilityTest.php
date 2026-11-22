<?php

namespace TweakPHP\Client\Tests;

use PHPUnit\Framework\TestCase;

class PresenterCompatibilityTest extends TestCase
{
    public function test_execute_supports_legacy_psysh_presenter_without_raw_constant(): void
    {
        $this->withLegacyPresenterProject(function (string $projectPath): void {
            $result = $this->runExecute(
                $projectPath,
                "defined(\\Psy\\VarDumper\\Presenter::class.'::RAW') ? 'has-raw' : 'no-raw';"
                ."\n['id' => 1, 'email' => 'test@example.com'];"
                ."\ndump('<info>hello</info>');"
            );

            $this->assertCount(3, $result['output']);
            $this->assertSame('"no-raw"', $result['output'][0]['output']);

            $this->assertStringContainsString('=>', $result['output'][1]['output']);
            $this->assertStringNotContainsString('=\>', $result['output'][1]['output']);
            $this->assertArrayHasKey('html', $result['output'][1]);
            $this->assertStringContainsString('test@example.com', $result['output'][1]['html']);

            $this->assertSame('"<info>hello</info>"', $result['output'][2]['output']);
        });
    }

    public function test_execute_stream_supports_legacy_psysh_presenter_without_raw_constant(): void
    {
        $this->withLegacyPresenterProject(function (string $projectPath): void {
            $events = $this->runExecuteStream(
                $projectPath,
                "defined(\\Psy\\VarDumper\\Presenter::class.'::RAW') ? 'has-raw' : 'no-raw';"
                ."\n['id' => 1, 'email' => 'test@example.com'];"
                ."\ndump('<info>hello</info>');"
            );

            $types = array_column($events, 'type');
            $this->assertNotContains('error', $types);
            $this->assertSame('completed', end($types));

            $outputEvents = array_values(array_filter(
                $events,
                fn (array $event): bool => ($event['type'] ?? null) === 'output'
            ));

            $byIndex = [];
            foreach ($outputEvents as $event) {
                $idx = $event['index'];
                $byIndex[$idx]['data'] = ($byIndex[$idx]['data'] ?? '').($event['data'] ?? '');
                if (isset($event['html'])) {
                    $byIndex[$idx]['html'] = ($byIndex[$idx]['html'] ?? '').$event['html'];
                }
            }

            $this->assertSame('"no-raw"', trim($byIndex[0]['data']));

            $this->assertStringContainsString('=>', $byIndex[1]['data']);
            $this->assertStringNotContainsString('=\>', $byIndex[1]['data']);
            $this->assertArrayHasKey('html', $byIndex[1]);
            $this->assertStringContainsString('test@example.com', $byIndex[1]['html']);

            $this->assertSame('"<info>hello</info>"', trim($byIndex[2]['data']));
        });
    }

    private function withLegacyPresenterProject(callable $callback): void
    {
        $projectPath = sys_get_temp_dir().'/tweakphp-legacy-presenter-'.uniqid();
        mkdir($projectPath.'/vendor', 0777, true);

        $legacyPresenter = <<<'PHP'
<?php

namespace Psy\VarDumper;

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\VarDumper\Caster\Caster;

class Presenter
{
    const VERBOSE = 1;

    private $cloner;
    private $dumper;

    public function __construct(OutputFormatter $formatter, $forceArrayIndexes = false)
    {
        $this->dumper = new Dumper($formatter, $forceArrayIndexes);
        $this->cloner = new Cloner();
    }

    public function addCasters(array $casters)
    {
        $this->cloner->addCasters($casters);
    }

    public function presentRef($value): string
    {
        return $this->present($value, 0);
    }

    public function present($value, ?int $depth = null, int $options = 0): string
    {
        $data = $this->cloner->cloneVar($value, ! ($options & self::VERBOSE) ? Caster::EXCLUDE_VERBOSE : 0);

        if ($depth !== null) {
            $data = $data->withMaxDepth($depth);
        }

        $output = '';
        $this->dumper->dump($data, function ($line, $depth) use (&$output) {
            if ($depth >= 0) {
                if ($output !== '') {
                    $output .= \PHP_EOL;
                }
                $output .= \str_repeat('  ', $depth).$line;
            }
        });

        return OutputFormatter::escape($output);
    }
}
PHP;

        $autoloader = <<<'PHP'
<?php

spl_autoload_register(static function (string $class): void {
    if ($class === 'Psy\\VarDumper\\Presenter') {
        require_once __DIR__.'/psy-legacy-presenter.php';
    }
}, true, true);
PHP;

        file_put_contents($projectPath.'/vendor/psy-legacy-presenter.php', $legacyPresenter);
        file_put_contents($projectPath.'/vendor/autoload.php', $autoloader);

        try {
            $callback($projectPath);
        } finally {
            $this->removeDirectory($projectPath);
        }
    }

    private function runExecute(string $projectPath, string $code): array
    {
        $lines = $this->runClient($projectPath, 'execute', $code);

        foreach ($lines as $line) {
            $this->assertStringNotContainsString('TWEAKPHP_ERROR:', $line);
        }

        $this->assertStringStartsWith('TWEAKPHP_RESULT:', $lines[0]);
        $decoded = json_decode(substr($lines[0], strlen('TWEAKPHP_RESULT:')), true);
        $this->assertSame(JSON_ERROR_NONE, json_last_error());

        return $decoded;
    }

    private function runExecuteStream(string $projectPath, string $code): array
    {
        $lines = $this->runClient($projectPath, 'execute-stream', $code);
        $events = [];

        foreach ($lines as $line) {
            $this->assertStringStartsWith('TWEAKPHP_STREAM:', $line);
            $event = json_decode(substr($line, strlen('TWEAKPHP_STREAM:')), true);
            $this->assertSame(JSON_ERROR_NONE, json_last_error());
            $events[] = $event;
        }

        return $events;
    }

    private function runClient(string $projectPath, string $command, string $code): array
    {
        $executable = sprintf(
            '%s %s %s %s %s 2>/dev/null',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(__DIR__.'/../index.php'),
            escapeshellarg($projectPath),
            escapeshellarg($command),
            escapeshellarg(base64_encode($code))
        );

        exec($executable, $lines, $exitCode);

        $this->assertSame(0, $exitCode, 'Client failed with output: '.implode("\n", $lines));
        $this->assertNotEmpty($lines);

        return $lines;
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }
}

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
}

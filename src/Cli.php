<?php

namespace TweakPHP\Client;

class Cli
{
    public static function getArgument(string $argument): string
    {
        $arguments = $_SERVER['argv'] ?? [];

        foreach ($arguments as $arg) {
            if (strpos($arg, "--$argument=") === 0) {
                return substr($arg, strlen("--$argument="));
            }
        }

        return '';
    }
}

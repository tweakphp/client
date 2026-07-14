<?php

use TweakPHP\Client\Cli;
use TweakPHP\Client\Loader;

require __DIR__.'/vendor/autoload.php';

$arguments = $argv;

if (count($arguments) < 3) {
    echo 'Invalid arguments'.PHP_EOL;
    exit(1);
}

$command = $arguments[2];
$supportedCommands = [
    'info',
    'execute',
    'execute-stream',
];

if (! in_array($command, $supportedCommands, true)) {
    echo 'Invalid command'.PHP_EOL;
    exit(1);
}

$writeStreamEvent = static function (array $event): void {
    fwrite(STDOUT, 'TWEAKPHP_STREAM:'.json_encode($event, JSON_INVALID_UTF8_SUBSTITUTE).PHP_EOL);
    fflush(STDOUT);
};

$writeError = static function (Throwable $exception) use ($command, $writeStreamEvent): void {
    $error = [
        'class' => get_class($exception),
        'message' => $exception->getMessage(),
    ];

    if ($command === 'execute-stream') {
        $writeStreamEvent([
            'type' => 'error',
            'error' => $error,
        ]);

        return;
    }

    echo 'TWEAKPHP_ERROR:'.json_encode($error, JSON_INVALID_UTF8_SUBSTITUTE).PHP_EOL;
};

$customLoader = Cli::getArgument('loader');
try {
    $loader = Loader::load($arguments[1], $customLoader ?: null);

    if ($loader === null) {
        throw new RuntimeException('No supported project found. Make sure the path contains a Composer project (vendor/autoload.php).');
    }

    $loader->init();

    if (in_array($command, ['execute', 'execute-stream'], true)) {
        if (count($arguments) < 4) {
            throw new InvalidArgumentException('Missing Base64-encoded PHP code.');
        }

        $code = base64_decode($arguments[3], true);

        if ($code === false) {
            throw new InvalidArgumentException('Invalid Base64-encoded PHP code.');
        }
    }

    switch ($command) {
        case 'info':
            echo json_encode([
                'name' => $loader->name(),
                'version' => $loader->version(),
                'php_version' => phpversion(),
            ], JSON_INVALID_UTF8_SUBSTITUTE).PHP_EOL;
            break;
        case 'execute':
            echo 'TWEAKPHP_RESULT:'.json_encode($loader->execute($code), JSON_INVALID_UTF8_SUBSTITUTE).PHP_EOL;
            break;
        case 'execute-stream':
            $streamFailed = false;
            $loader->executeStreaming($code, static function (array $event) use (&$streamFailed, $writeStreamEvent): void {
                $streamFailed = $streamFailed || ($event['type'] ?? null) === 'error';
                $writeStreamEvent($event);
            });

            if ($streamFailed) {
                exit(1);
            }
            break;
    }
} catch (Throwable $exception) {
    $writeError($exception);
    exit(1);
}

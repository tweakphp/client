<?php

use TweakPHP\Client\Cli;
use TweakPHP\Client\Loader;

require __DIR__.'/vendor/autoload.php';

$arguments = $argv;

if (count($arguments) < 3) {
    echo 'Invalid arguments'.PHP_EOL;
    exit(1);
}

$customLoader = Cli::getArgument('loader');
$loader = Loader::load($arguments[1], $customLoader ?: null);

if ($loader === null) {
    echo 'No supported project found. Make sure the path contains a Composer project (vendor/autoload.php).'.PHP_EOL;
    exit(1);
}

$loader->init();

$supportedCommands = [
    'info',
    'execute',
    'execute-stream',
];

if (! in_array($arguments[2], $supportedCommands)) {
    echo 'Invalid command'.PHP_EOL;
    exit(1);
}

switch ($arguments[2]) {
    case 'info':
        $info = json_encode([
            'name' => $loader->name(),
            'version' => $loader->version(),
            'php_version' => phpversion(),
        ]);
        echo $info.PHP_EOL;
        break;
    case 'execute':
        if (count($arguments) < 4) {
            echo 'Invalid arguments'.PHP_EOL;
            exit(1);
        }
        $output = json_encode($loader->execute(base64_decode($arguments[3])));
        echo 'TWEAKPHP_RESULT:'.$output.PHP_EOL;
        break;
    case 'execute-stream':
        if (count($arguments) < 4) {
            echo 'Invalid arguments'.PHP_EOL;
            exit(1);
        }

        $loader->executeStreaming(base64_decode($arguments[3]), function (array $event): void {
            file_put_contents(
                'php://stdout',
                'TWEAKPHP_STREAM:'.json_encode($event).PHP_EOL,
                FILE_APPEND
            );
        });
        break;
}

<?php

namespace TweakPHP\Client\Loaders;

use Throwable;

class WordPressLoader extends BaseLoader
{
    public static function supports(string $path): bool
    {
        return file_exists($path.'/wp-load.php');
    }

    public function __construct(string $path)
    {
        require_once $path.'/wp-load.php';
        require_once $path.'/wp-admin/includes/admin.php';
        require_once $path.'/wp-includes/pluggable.php';
    }

    public function name(): string
    {
        return 'WordPress';
    }

    public function version(): string
    {
        try {
            if (function_exists('get_bloginfo')) {
                return get_bloginfo('version');
            }
        } catch (Throwable $e) {
            //
        }

        return '';
    }
}

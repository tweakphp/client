<?php

namespace TweakPHP\Client\Loaders;

use Throwable;

class WordPressLoader extends BaseLoader
{
    public static function supports(string $path): bool
    {
        return file_exists($path.'/web/wp/wp-load.php');
    }

    public function __construct(string $path)
    {
        require_once $path.'/web/wp/wp-load.php';
        require_once $path.'/web/wp/wp-admin/includes/admin.php';
        require_once $path.'/web/wp/wp-includes/pluggable.php';
    }

    public function name(): string
    {
        return 'WordPress - Bedrock';
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

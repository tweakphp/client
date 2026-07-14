<?php

namespace Symfony\Component\HttpKernel {
    if (! class_exists(Kernel::class)) {
        class Kernel
        {
            const VERSION = '6.4.0';
        }
    }
}

namespace Pimcore {
    if (! class_exists(Bootstrap::class)) {
        class Bootstrap
        {
            public static function setProjectRoot() {}

            public static function bootstrap() {}

            public static function kernel() {}
        }
    }
}

namespace TweakPHP\Client\Tests {

    use PHPUnit\Framework\TestCase;
    use TweakPHP\Client\Loader;
    use TweakPHP\Client\Loaders\ComposerLoader;
    use TweakPHP\Client\Loaders\LaravelLoader;
    use TweakPHP\Client\Loaders\PimcoreLoader;
    use TweakPHP\Client\Loaders\PlainPhpLoader;
    use TweakPHP\Client\Loaders\SymfonyLoader;
    use TweakPHP\Client\Loaders\WordPressLoader;

    if (! function_exists('\TweakPHP\Client\Tests\get_bloginfo')) {
        function get_bloginfo($show = '')
        {
            return '6.2.2';
        }
    }

    class LoaderTest extends TestCase
    {
        private string $tempDir;

        protected function setUp(): void
        {
            $this->tempDir = sys_get_temp_dir().'/tweakphp_test_'.uniqid();
            mkdir($this->tempDir, 0777, true);
        }

        protected function tearDown(): void
        {
            $this->removeDirectory($this->tempDir);
        }

        private function removeDirectory(string $dir): void
        {
            if (! is_dir($dir)) {
                return;
            }
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                (is_dir("$dir/$file")) ? $this->removeDirectory("$dir/$file") : unlink("$dir/$file");
            }
            rmdir($dir);
        }

        public function test_laravel_loader_detection_and_boot()
        {
            mkdir($this->tempDir.'/vendor', 0777, true);
            mkdir($this->tempDir.'/bootstrap', 0777, true);
            file_put_contents($this->tempDir.'/vendor/autoload.php', '<?php ');

            // Write a mock Laravel app.php bootstrap
            $appMock = '<?php
            return new class {
                public function make($abstract) {
                    return new class {
                        public function bootstrap() {}
                        public function get($key, $default = []) { return []; }
                    };
                }
                public function version() {
                    return "10.11.12";
                }
                public function basePath() {
                    return "'.addslashes($this->tempDir).'";
                }
            };';
            file_put_contents($this->tempDir.'/bootstrap/app.php', $appMock);

            $this->assertTrue(LaravelLoader::supports($this->tempDir));

            $loader = Loader::load($this->tempDir);
            $this->assertInstanceOf(LaravelLoader::class, $loader);
            $this->assertEquals('Laravel', $loader->name());
            $this->assertEquals('10.11.12', $loader->version());

            $casters = $loader->casters();
            $this->assertArrayHasKey('Illuminate\Support\Collection', $casters);
        }

        public function test_symfony_loader_detection_and_boot()
        {
            mkdir($this->tempDir.'/vendor', 0777, true);
            mkdir($this->tempDir.'/src', 0777, true);
            file_put_contents($this->tempDir.'/vendor/autoload.php', '<?php ');
            file_put_contents($this->tempDir.'/symfony.lock', '{}');

            // Write a mock Symfony kernel
            $kernelMock = '<?php
            namespace App;
            class Kernel {
                public function __construct($env, $debug) {}
                public function boot() {}
                public function getContainer() {
                    return new class {
                        public function has($id) { return false; }
                        public function get($id) { return null; }
                    };
                }
            }';
            file_put_contents($this->tempDir.'/src/Kernel.php', $kernelMock);

            $this->assertTrue(SymfonyLoader::supports($this->tempDir));

            $loader = Loader::load($this->tempDir);
            $this->assertInstanceOf(SymfonyLoader::class, $loader);
            $this->assertEquals('Symfony', $loader->name());
            $this->assertEquals('6.4.0', $loader->version());
        }

        public function test_word_press_loader_detection_and_boot()
        {
            mkdir($this->tempDir.'/wp-admin/includes', 0777, true);
            mkdir($this->tempDir.'/wp-includes', 0777, true);
            file_put_contents($this->tempDir.'/wp-load.php', '<?php 
            if (!function_exists("get_bloginfo")) {
                function get_bloginfo($show = "") {
                    return "6.2.2";
                }
            }');
            file_put_contents($this->tempDir.'/wp-admin/includes/admin.php', '<?php ');
            file_put_contents($this->tempDir.'/wp-includes/pluggable.php', '<?php ');

            $this->assertTrue(WordPressLoader::supports($this->tempDir));

            $loader = Loader::load($this->tempDir);
            $this->assertInstanceOf(WordPressLoader::class, $loader);
            $this->assertEquals('WordPress', $loader->name());
            $this->assertEquals('6.2.2', $loader->version());
        }

        public function test_pimcore_loader_detection_and_boot()
        {
            mkdir($this->tempDir.'/vendor/pimcore/pimcore', 0777, true);
            file_put_contents($this->tempDir.'/vendor/autoload.php', '<?php ');

            $this->assertTrue(PimcoreLoader::supports($this->tempDir));

            $loader = Loader::load($this->tempDir);
            $this->assertInstanceOf(PimcoreLoader::class, $loader);
            $this->assertEquals('Pimcore', $loader->name());

            // version() should throw an OutOfBoundsException because pimcore/pimcore is not actually registered with Composer
            $this->expectException(\OutOfBoundsException::class);
            $loader->version();
        }

        public function test_composer_loader_detection()
        {
            mkdir($this->tempDir.'/vendor', 0777, true);
            file_put_contents($this->tempDir.'/vendor/autoload.php', '<?php ');

            $this->assertTrue(ComposerLoader::supports($this->tempDir));

            $loader = Loader::load($this->tempDir);
            $this->assertInstanceOf(ComposerLoader::class, $loader);
            $this->assertEquals('Composer Project', $loader->name());
            $this->assertEquals('', $loader->version());
        }

        public function test_composer_loader_does_not_reload_the_client_autoloader()
        {
            mkdir($this->tempDir.'/vendor', 0777, true);
            file_put_contents($this->tempDir.'/composer.json', json_encode([
                'name' => 'tweakphp/client',
            ]));
            file_put_contents($this->tempDir.'/vendor/autoload.php', '<?php throw new RuntimeException("autoload should not be loaded");');

            $loader = new ComposerLoader($this->tempDir);

            $this->assertEquals('Composer Project', $loader->name());
        }

        public function test_plain_php_loader_detection()
        {
            $this->assertTrue(PlainPhpLoader::supports($this->tempDir));

            $loader = Loader::load($this->tempDir);
            $this->assertInstanceOf(PlainPhpLoader::class, $loader);
            $this->assertEquals('PHP', $loader->name());
            $this->assertEquals('', $loader->version());
        }

        public function test_custom_encoded_loader()
        {
            // We define a base64 encoded custom loader class.
            $customLoaderCode = '<?php
            class CustomEncodedLoaderTestClass extends \TweakPHP\Client\Loaders\PlainPhpLoader {
                public function name(): string {
                    return "CustomEncoded";
                }
            }';

            $encoded = base64_encode($customLoaderCode);

            $loader = Loader::load($this->tempDir, $encoded);
            $this->assertInstanceOf(\CustomEncodedLoaderTestClass::class, $loader);
            $this->assertEquals('CustomEncoded', $loader->name());
        }
    }
}

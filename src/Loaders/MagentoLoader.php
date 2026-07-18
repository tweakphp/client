<?php

namespace TweakPHP\Client\Loaders;

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ProductMetadataInterface;

class MagentoLoader extends ComposerLoader
{
    public static function supports(string $path): bool
    {
        return file_exists($path.'/bin/magento');
    }

    public function __construct(string $path)
    {
        parent::__construct($path);

        require $path.'/app/bootstrap.php';
        if (!in_array('phar', stream_get_wrappers())) {
            stream_wrapper_restore('phar');
        }

        Bootstrap::create($path, $_SERVER);
    }

    public function name(): string
    {
        return 'Magento';
    }

    public function version(): string
    {
        $objectManager = ObjectManager::getInstance();
        $metadata = $objectManager->get(ProductMetadataInterface::class);
        return $metadata->getEdition().' '.$metadata->getVersion();
    }
}

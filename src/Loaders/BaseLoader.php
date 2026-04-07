<?php

namespace TweakPHP\Client\Loaders;

use Psy\Configuration as ConfigurationAlias;
use Psy\VersionUpdater\Checker;
use TweakPHP\Client\OutputModifiers\CustomOutputModifier;
use TweakPHP\Client\Psy\Configuration;
use TweakPHP\Client\Tinker;

abstract class BaseLoader implements LoaderInterface
{
    protected Tinker $tinker;

    public function init(): void
    {
        $config = new Configuration([
            'configFile' => null,
        ]);
        $config->setUpdateCheck(Checker::NEVER);
        if (method_exists($config, 'setInteractiveMode')) {
            $config->setInteractiveMode(ConfigurationAlias::INTERACTIVE_MODE_DISABLED);
        }
        if (method_exists($config, 'setColorMode')) {
            $config->setColorMode(ConfigurationAlias::COLOR_MODE_DISABLED);
        }
        $config->setRawOutput(false);
        $config->setTheme([
            'prompt' => '',
        ]);
        $config->setHistoryFile(defined('PHP_WINDOWS_VERSION_BUILD') ? 'null' : '/dev/null');
        $config->setUsePcntl(false);

        $config->getPresenter()->addCasters($this->casters());

        $this->tinker = new Tinker(new CustomOutputModifier, $config);
    }

    public function execute(string $code): array
    {
        return $this->tinker->execute($code);
    }

    public function executeStreaming(string $code): void
    {
        $this->tinker->executeStreaming($code);
    }

    public function casters(): array
    {
        return [];
    }

    public static function supports(string $path): bool
    {
        return false;
    }
}

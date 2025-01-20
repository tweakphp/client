<?php

namespace TweakPHP\Client\Loaders;

use Psy\Configuration;
use Psy\VersionUpdater\Checker;
use TweakPHP\Client\OutputModifiers\CustomOutputModifier;
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
        $config->setRawOutput(true);
        $config->setInteractiveMode(Configuration::INTERACTIVE_MODE_DISABLED);
        $config->setColorMode(Configuration::COLOR_MODE_DISABLED);
        $config->setTheme([
            'prompt' => '',
        ]);
        $config->setVerbosity(Configuration::VERBOSITY_QUIET);
        $config->setHistoryFile(defined('PHP_WINDOWS_VERSION_BUILD') ? 'null' : '/dev/null');
        $config->setUsePcntl(false);

        if (class_exists('Illuminate\Support\Collection') && class_exists('Laravel\Tinker\TinkerCaster')) {
            $config->getPresenter()->addCasters([
                \Illuminate\Support\Collection::class => 'Laravel\Tinker\TinkerCaster::castCollection',
            ]);
        }
        if (class_exists('Illuminate\Database\Eloquent\Model') && class_exists('Laravel\Tinker\TinkerCaster')) {
            $config->getPresenter()->addCasters([
                \Illuminate\Database\Eloquent\Model::class => 'Laravel\Tinker\TinkerCaster::castModel',
            ]);
        }
        if (class_exists('Illuminate\Foundation\Application') && class_exists('Laravel\Tinker\TinkerCaster')) {
            $config->getPresenter()->addCasters([
                \Illuminate\Foundation\Application::class => 'Laravel\Tinker\TinkerCaster::castApplication',
            ]);
        }

        $this->tinker = new Tinker(new CustomOutputModifier, $config);
    }

    public function execute(string $code): string
    {
        $output = $this->tinker->execute($code);

        return trim($output);
    }
}

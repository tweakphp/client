<?php

namespace TweakPHP\Client\Loaders;

use Psy\Configuration;
use Psy\VersionUpdater\Checker;
use TweakPHP\Client\OutputModifiers\CustomOutputModifier;
use TweakPHP\Client\Tinker;

abstract class BaseLoader implements LoaderInterface
{
    protected Tinker $tinker;

    public function init()
    {
        $config = new Configuration([
            'configFile' => null,
        ]);
        $config->setUpdateCheck(Checker::NEVER);
        $config->setRawOutput(true);
        $config->setInteractiveMode(Configuration::INTERACTIVE_MODE_DISABLED);

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

        $this->tinker = new Tinker(new CustomOutputModifier(), $config);
    }

    public function execute(string $code)
    {
        $output = $this->tinker->execute($code);

        echo trim($output);
    }
}
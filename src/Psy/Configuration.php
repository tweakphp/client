<?php

namespace TweakPHP\Client\Psy;

class Configuration extends \Psy\Configuration
{
    public function getPresenter(): Presenter
    {
        if (! isset($this->presenter)) {
            $this->presenter = new Presenter($this->getOutput()->getFormatter(), $this->forceArrayIndexes());
        }

        return $this->presenter;
    }
}

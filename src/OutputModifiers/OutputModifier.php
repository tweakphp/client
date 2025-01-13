<?php

namespace TweakPHP\Client\OutputModifiers;

interface OutputModifier
{
    public function modify(string $output = ''): string;
}

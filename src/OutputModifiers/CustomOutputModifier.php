<?php

namespace TweakPHP\Client\OutputModifiers;

class CustomOutputModifier implements OutputModifier
{
    public function modify(string $output = ''): string
    {
        return preg_replace('/^ {2}/m', '', $output);
    }
}

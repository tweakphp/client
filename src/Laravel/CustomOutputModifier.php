<?php

namespace TweakPHP\Client\Laravel;

use Spatie\WebTinker\OutputModifiers\OutputModifier;

class CustomOutputModifier implements OutputModifier
{
    public function modify(string $output = ''): string
    {
        // remove only the first tab from each line
        return preg_replace('/^ {2}/m', '', $output);
    }
}
<?php

namespace TweakPHP\Client\OutputModifiers;

class CustomOutputModifier implements OutputModifier
{
    public function modify(string $output = ''): string
    {
        $endMarker = 'TWEAKPHP_END';
        $position = strpos($output, $endMarker);

        if ($position !== false) {
            $output = substr($output, 0, $position);
        }

        // remove only the first tab from each line
        return preg_replace('/^ {2}/m', '', $output);
    }
}

<?php

namespace TweakPHP\Client\Casters;

class LaravelCaster
{
    public static function casters(): array
    {
        $casters = [
            'Illuminate\Support\Collection' => 'Laravel\Tinker\TinkerCaster::castCollection',
            'Illuminate\Support\HtmlString' => 'Laravel\Tinker\TinkerCaster::castHtmlString',
            'Illuminate\Support\Stringable' => 'Laravel\Tinker\TinkerCaster::castStringable',
        ];

        if (class_exists('Illuminate\Database\Eloquent\Model')) {
            $casters['Illuminate\Database\Eloquent\Model'] = 'Laravel\Tinker\TinkerCaster::castModel';
        }

        if (class_exists('Illuminate\Process\ProcessResult')) {
            $casters['Illuminate\Process\ProcessResult'] = 'Laravel\Tinker\TinkerCaster::castProcessResult';
        }

        if (class_exists('Illuminate\Foundation\Application')) {
            $casters['Illuminate\Foundation\Application'] = 'Laravel\Tinker\TinkerCaster::castApplication';
        }

        return $casters;
    }
}

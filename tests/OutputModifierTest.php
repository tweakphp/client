<?php

namespace TweakPHP\Client\Tests;

use PHPUnit\Framework\TestCase;
use TweakPHP\Client\OutputModifiers\CustomOutputModifier;

class OutputModifierTest extends TestCase
{
    public function test_custom_output_modifier()
    {
        $modifier = new CustomOutputModifier;

        $input = "  Hello\n    World\n  TWEAKPHP_END\nignored";
        $expected = "Hello\n  World\n";

        $this->assertEquals($expected, $modifier->modify($input));
    }
}

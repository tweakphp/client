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
        $expected = "Hello\n  World\nTWEAKPHP_END\nignored";

        $this->assertEquals($expected, $modifier->modify($input));
    }

    public function test_custom_output_modifier_preserves_marker_text(): void
    {
        $modifier = new CustomOutputModifier;

        $this->assertSame('before TWEAKPHP_END after', $modifier->modify('before TWEAKPHP_END after'));
    }
}

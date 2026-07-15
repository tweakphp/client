<?php

namespace TweakPHP\Client\Database;

interface QueryProviderInterface
{
    public function start(): void;

    /** @return array<int, array<string, mixed>> */
    public function stop(): array;
}

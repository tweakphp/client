<?php

namespace TweakPHP\Client\Database;

use Illuminate\Support\Facades\DB;

class LaravelQueryProvider implements QueryProviderInterface
{
    private bool $listenerRegistered = false;

    /** @var array<int, array<string, mixed>> */
    private array $queries = [];

    private bool $isLogging = false;

    public function start(): void
    {
        $this->queries = [];
        $this->isLogging = true;

        if ($this->listenerRegistered || ! class_exists(DB::class)) {
            return;
        }

        DB::listen(function ($query): void {
            if (! $this->isLogging) {
                return;
            }

            $this->queries[] = [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
                'connection' => $query->connectionName,
            ];
        });

        $this->listenerRegistered = true;
    }

    public function stop(): array
    {
        $this->isLogging = false;

        return $this->queries;
    }
}

<?php

namespace App\AgentSquad\Providers;

use App\Models\AppTrace;

abstract class AbstractProvider
{
    public static function traceSuccess(string $endpoint, float $before, float $after): AppTrace
    {
        /** @var AppTrace $trace */
        return AppTrace::create([
            'user_id' => auth()?->user()?->id,
            'verb' => 'PROVIDE',
            'endpoint' => "/{$endpoint}",
            'procedure' => null,
            'method' => null,
            'duration_in_ms' => (int)(($after - $before) * 1000),
            'failed' => false,
        ]);
    }

    public static function traceError(string $endpoint, float $before, float $after): AppTrace
    {
        /** @var AppTrace $trace */
        return AppTrace::create([
            'user_id' => auth()?->user()?->id,
            'verb' => 'PROVIDE',
            'endpoint' => "/{$endpoint}",
            'procedure' => null,
            'method' => null,
            'duration_in_ms' => (int)(($after - $before) * 1000),
            'failed' => true,
        ]);
    }
}
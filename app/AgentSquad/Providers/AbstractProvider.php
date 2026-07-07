<?php

namespace App\AgentSquad\Providers;

use App\Models\AppTrace;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

abstract class AbstractProvider
{
    public function provide()
    {
        $before = microtime(true);
        $answer = $this->provide2();
        $after = microtime(true);
        try {
            if ($this->trace()) {
                $name = str(static::class)->afterLast('\\')->before('Provider')->lower()->toString();
                /** @var AppTrace $trace */
                $trace = AppTrace::create([
                    'user_id' => Auth::user()?->id,
                    'verb' => 'GET',
                    'endpoint' => "/agent-squad/provider?name={$name}",
                    'duration_in_ms' => (int)(($after - $before) * 1000),
                    'failed' => false,
                ]);
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
        return $answer;
    }

    protected function trace(): bool
    {
        return false;
    }

    protected abstract function provide2();
}
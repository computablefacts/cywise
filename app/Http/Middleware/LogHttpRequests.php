<?php

namespace App\Http\Middleware;

use App\Models\AppTrace;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LogHttpRequests
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $before = microtime(true);
        $response = $next($request);
        $after = microtime(true);
        try {

            // user
            $user = Auth::user();

            // In
            $verb = $request->method();
            $endpoint = $request->path();

            // Out
            $result = json_decode($response->getContent(), true);
            $failed = isset($result['error']) || $response->status() !== 200;

            // Metrics
            $durationInMs = (int)(($after - $before) * 1000);

            // Update trace
            /** @var AppTrace $trace */
            $trace = AppTrace::create([
                'user_id' => $user?->id,
                'verb' => $verb,
                'endpoint' => "/{$endpoint}",
                'duration_in_ms' => $durationInMs,
                'failed' => $failed,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log HTTP request/response', ['error' => $e->getMessage()]);
        }
        return $response;
    }
}

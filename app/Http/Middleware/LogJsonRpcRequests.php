<?php

namespace App\Http\Middleware;

use App\Models\AppTrace;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogJsonRpcRequests
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
        // 2025-09-23
        // Calling $next($request) before the try catch to store the App Trace
        // allow to collect duration BUT it call the Authenticate middleware first
        // so if authentication failed the JSON RPC call is not recorded in the
        // App Trace
        $before = microtime(true);
        $response = $next($request);
        $after = microtime(true);
        try {

            // user
            $user = Auth::user();

            // In
            $verb = $request->method();
            $endpoint = $request->path();
            $payload = json_decode($request->getContent(), true);
            $id = $payload['id'] ?? null;
            $procedure = Str::before($payload['method'], '@');
            $method = Str::after($payload['method'], '@');
            $params = $payload['params'] ?? null; // json

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
                'procedure' => $procedure,
                'method' => $method,
                'duration_in_ms' => $durationInMs,
                'failed' => $failed,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log JSON-RPC request/response', ['error' => $e->getMessage()]);
        }
        return $response;
    }
}

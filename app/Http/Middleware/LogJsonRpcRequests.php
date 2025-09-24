<?php

namespace App\Http\Middleware;

use App\Models\AppTrace;
use App\Models\User;
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
            /** @var User $user */
            $user = Auth::user();

            // In
            $verb = $request->method();
            $endpoint = $request->path();
            $payload = json_decode($request->getContent(), true);
            $callsIn = is_array($payload) && array_is_list($payload) ? $payload : [$payload];

            // Out
            $result = json_decode($response->getContent(), true);
            $callsOut = is_array($result) && array_is_list($result) ? $result : [$result];

            // Metrics
            $durationInMs = (int)(($after - $before) * 1000);

            // Update trace for each call
            foreach ($callsIn as $call) {

                $id = $call['id'] ?? null;
                $procedure = Str::before($call['method'], '@');
                $method = Str::after($call['method'], '@');
                $result = collect($callsOut)->where('id', $id)->first();
                $failed = isset($result['error']) || $response->status() !== 200;

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
            }
        } catch (\Exception $e) {
            Log::error('Failed to log JSON-RPC request/response', ['error' => $e->getMessage()]);
        }
        return $response;
    }
}

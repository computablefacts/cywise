<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CheckPermissionsJsonRpcRequest
{
    /**
     * Handle an incoming request.
     *
     * This middleware checks that the authenticated user has a permission
     * named "<procedure>.<method>" for each JSON-RPC request contained in the payload.
     * It supports both single and batch JSON-RPC requests.
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var User $user */
        $user = Auth::user();
        $payload = json_decode($request->getContent(), true);
        $calls = is_array($payload) && array_is_list($payload) ? $payload : [$payload];
        $responses = [];

        // Check permissions
        foreach ($calls as $call) {

            $id = $call['id'] ?? null;
            $procedure = Str::lower(Str::before($call['method'], '@'));
            $method = Str::lower(Str::after($call['method'], '@'));
            $permission = "{$procedure}.{$method}";

            if ($user->cannot($permission)) {
                $responses[] = [
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'error' => [
                        'code' => 403,
                        'message' => 'Permission denied.',
                        'data' => [
                            'required_permission' => $permission,
                        ],
                    ],
                ];
            }
        }
        if (!empty($responses)) {
            $response = (is_array($payload) && array_is_list($payload)) ? $responses : $responses[0];
            return response()->json($response);
        }
        return $next($request);
    }
}

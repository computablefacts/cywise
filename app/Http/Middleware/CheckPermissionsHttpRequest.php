<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermissionsHttpRequest
{
    /**
     * Handle an incoming request.
     *
     * This middleware checks that the authenticated user has a permission
     * named "view.<route_name>" for each HTTP GET request.
     */
    public function handle(Request $request, Closure $next)
    {
        $verb = $request->method();

        if ($verb === 'GET') {

            /** @var User $user */
            $user = Auth::user();
            $route = $request->route()?->getName();

            if (!$route) {
                return response()->json([
                    'message' => 'Permission denied.',
                    'data' => [
                        'required_permission' => 'Missing route name.',
                    ],
                ], 403);
            }
            if ($user->cannotView($route)) {
                return response()->json([
                    'message' => 'Permission denied.',
                    'data' => [
                        'required_permission' => "view.{$route}",
                    ],
                ], 403);
            }
        }
        return $next($request);
    }
}

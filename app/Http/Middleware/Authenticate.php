<?php

namespace App\Http\Middleware;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string|null $guard
     * @return mixed
     */
    public function handle($request, $next, ...$guards)
    {
        // Check either the bearer token or the query string (Sanctum)
        $api_token = $request->bearerToken() ?: $request->input('api_token');
        if (!empty($api_token)) {
            /** @var \Laravel\Sanctum\PersonalAccessToken $token */
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($api_token);
            if ($token) { // Ensure the token exists
                if ($token->expires_at === null || $token->expires_at > Carbon::now()) { // Ensure the token hasn't expired'
                    /** @var User $user */
                    $user = $token->tokenable;
                    $user->actAs();
                    return $next($request);
                }
            }

            /** @var \Wave\ApiKey $token */
            $token = \Wave\ApiKey::query()->where('key', '=', $api_token)->first();
            if ($token) { // Ensure the token exists
                /** @var User $user */
                $user = $token->user()->first();
                $user->actAs();
                return $next($request);
            }
        }
        return parent::handle($request, $next, ...$guards);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param \Illuminate\Http\Request $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (!$request->expectsJson()) {
            return route('login');
        }
    }
}

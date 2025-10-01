<?php

namespace App\Http\Middleware;

use App\Models\Role;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RedirectToCyberBuddy
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User $user */
        $user = Auth::user();
        $loginTime = session('login_time');

        if ($request->routeIs('dashboard')
            && $user && $user->hasRole(Role::CYBERBUDDY_ONLY)) {

            if (!$user->canView('iframes.dashboard')
                || ($loginTime && now()->diffInSeconds($loginTime, true) < 30)) {

                Log::debug('RedirectToCyberBuddy', [
                    'user' => $user->email,
                    'loginTime' => $loginTime,
                    'diffInSeconds' => now()->diffInSeconds($loginTime, true),
                ]);
                return redirect()->route('cyberbuddy');
            }
        }

        return $next($request);
    }
}

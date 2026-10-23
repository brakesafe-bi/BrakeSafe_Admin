<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (\Illuminate\Support\Facades\Auth::guard($guard)->check()) {
                // Redirect authenticated users to dashboard, NOT to home route
                $user = \Illuminate\Support\Facades\Auth::guard($guard)->user();
                if ($user->isAdmin()) {
                    return redirect('/');
                }
                // Non-admin users are not allowed on admin pages
                return redirect('/login');
            }
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::user();

                // Redirect based on user role
                if ($user->isSuperAdmin()) {
                    return redirect('/admin/dashboard');
                } elseif ($user->isDean()) {
                    return redirect('/dean/dashboard');
                } elseif ($user->isTeacher()) {
                    return redirect('/teacher/dashboard');
                } elseif ($user->isProgramHead()) {
                    return redirect('/program-head/dashboard');
                }

                // Default fallback
                return redirect(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
}

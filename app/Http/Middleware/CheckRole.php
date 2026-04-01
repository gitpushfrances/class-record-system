<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // Check if user has the required role
        $user = auth()->user();

        // Map role strings to check methods
        $roleCheck = match($role) {
            'super_admin'  => $user->isSuperAdmin(),
            'dean'         => $user->isDean(),
            'teacher'      => $user->isTeacher(),
            'program_head' => $user->isProgramHead(),
            default        => false,
        };

        if (!$roleCheck) {
            abort(403, 'Unauthorized access. You do not have permission to access this page.');
        }

        return $next($request);
    }
}

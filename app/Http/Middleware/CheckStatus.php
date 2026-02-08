<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Check if user status is active
        if (!$user->isActive()) {
            // Logout pending/inactive users
            auth()->logout();

            $message = $user->isPending()
                ? 'Your account is pending approval. Please wait for admin/dean approval.'
                : 'Your account has been deactivated. Please contact the administrator.';

            return redirect()->route('login')->with('error', $message);
        }

        return $next($request);
    }
}

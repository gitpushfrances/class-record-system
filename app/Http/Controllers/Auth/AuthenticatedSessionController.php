<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Role-based redirect after login
        $user = Auth::user();

        // Check if user is active
        if (!$user->isActive()) {
            Auth::logout();

            $message = $user->isPending()
                ? 'Your account is pending approval. Please wait for admin/dean approval.'
                : 'Your account has been deactivated. Please contact the administrator.';

            return redirect()->route('login')->with('error', $message);
        }

        // Redirect based on role
        if ($user->isSuperAdmin()) {
            return redirect()->intended('/admin/dashboard');
        } elseif ($user->isDean()) {
            return redirect()->intended('/dean/dashboard');
        } elseif ($user->isTeacher()) {
            return redirect()->intended('/teacher/dashboard');
        }

        // Default fallback
        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}

<?php

namespace App\Http\Controllers\Platform\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the platform admin login view.
     */
    public function create(): Response
    {
        return Inertia::render('Platform/Auth/Login');
    }

    /**
     * Handle an incoming platform admin authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('platform.offices.index', absolute: false));
    }

    /**
     * Destroy the platform admin session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('platform')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect(route('platform.login'));
    }
}

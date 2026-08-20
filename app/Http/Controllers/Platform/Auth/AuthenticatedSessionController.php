<?php

namespace App\Http\Controllers\Platform\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\Auth\LoginRequest;
use App\Support\GuardedIntendedUrl;
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

        return GuardedIntendedUrl::forPlatform($request, route('platform.offices.index', absolute: false));
    }

    /**
     * Destroy the platform admin session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('platform')->logout();

        // 社労士側(webガード)が同じブラウザで同時ログインしている場合に備え、
        // invalidate()（セッション全体をクリア）ではなく、このガードのみをログアウトした
        // 状態を保ったままセッションIDだけを再発行する（古いセッションは破棄され安全性は同等）。
        $request->session()->regenerate(true);

        return redirect(route('platform.login'));
    }
}

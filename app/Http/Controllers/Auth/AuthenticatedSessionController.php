<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\GuardedIntendedUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * 次回ログイン時に事業所IDを自動入力するためのCookie名。1年間保持する。
     */
    private const REMEMBERED_OFFICE_CODE_COOKIE = 'remembered_office_code';

    /**
     * Display the login view.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'rememberedOfficeCode' => $request->cookie(self::REMEMBERED_OFFICE_CODE_COOKIE),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        Cookie::queue(
            self::REMEMBERED_OFFICE_CODE_COOKIE,
            $request->string('office_code')->lower()->value(),
            60 * 24 * 365,
        );

        return GuardedIntendedUrl::forWeb($request, route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        // 運営者(platformガード)が同じブラウザで同時ログインしている場合に備え、
        // invalidate()（セッション全体をクリア）ではなく、このガードのみをログアウトした
        // 状態を保ったままセッションIDだけを再発行する（古いセッションは破棄され安全性は同等）。
        $request->session()->regenerate(true);

        return redirect()->route('login');
    }
}

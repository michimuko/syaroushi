<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * ログイン中の事務所ユーザー(webガード)が、セッション継続中に利用不可になっていないかを
 * 毎リクエスト確認する。LoginRequest::authenticate()のis_activeチェックはログイン試行の
 * その場でしか効かないため、ログイン中に運営者が事務所を非アクティブ化・ソフト削除した場合
 * （データ削除ポリシーによるOffice::delete()等）に、既存セッションがそのまま使え続けて
 * しまう問題を防ぐ（$user->officeがnullになる、もしくはis_activeがfalseになる）。
 *
 * 必ず`Auth::guard('web')`を明示すること。routes/platform.phpもwebミドルウェアグループ配下
 * にあるため、裸の`Auth::user()`だと運営者(platformガード)のリクエストにも反応しうる
 * （App\Models\Scopes\OfficeScopeと同じ注意点）。
 */
class EnsureOfficeIsUsable
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if ($user !== null && (! $user->office || ! $user->office->is_active)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'ご利用の事務所は現在ご利用いただけません。運営事務局へお問い合わせください。');
        }

        return $next($request);
    }
}

<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * セッションの`url.intended`はガードを問わず単一のキーで共有されるため、
 * 「未ログインで/admin配下にアクセス→社労士側でログイン」のような操作をすると
 * 本来無関係な他ガードの保護URLへ迷い込んでしまう（意図せずログイン画面へ戻る不具合の原因）。
 * ここで対象ガードの管理下にあるURLかを検証し、そうでなければ無視してデフォルト遷移先を使う。
 */
class GuardedIntendedUrl
{
    public static function forWeb(Request $request, string $default): RedirectResponse
    {
        return static::resolve($request, $default, fn (string $path) => ! str_starts_with($path, '/admin'));
    }

    public static function forPlatform(Request $request, string $default): RedirectResponse
    {
        return static::resolve($request, $default, fn (string $path) => str_starts_with($path, '/admin'));
    }

    private static function resolve(Request $request, string $default, \Closure $isAllowed): RedirectResponse
    {
        $intended = $request->session()->pull('url.intended');
        $path = $intended !== null ? (parse_url($intended, PHP_URL_PATH) ?? '') : null;

        return redirect()->to($path !== null && $isAllowed($path) ? $intended : $default);
    }
}

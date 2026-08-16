<?php

namespace App\Http\Controllers;

use App\Services\DesktopAppReleaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * デスクトップ通知アプリ（Tauri、企画書5-C Level2）が使うAPIトークンの発行・失効画面。
 * トークンは本人専用（事務所全体ではなくユーザー単位）で、ownerによる委譲対象にはしない
 * （他人の代わりにトークンを発行できてしまうと、他ユーザー宛の通知を横取りできてしまうため）。
 * 生成直後のみ平文をセッションフラッシュで一度だけ表示し、以降はDBに保存しない
 * （Sanctumの仕様上ハッシュのみ保持されるため再表示は不可能）。
 */
class DesktopAppTokenController extends Controller
{
    private const TOKEN_NAME = 'desktop-app';

    private const ABILITY = 'desktop-notifications:read';

    public function __construct(private readonly DesktopAppReleaseService $releases) {}

    public function index(): Response
    {
        $user = Auth::user();
        $token = $user->tokens()->where('name', self::TOKEN_NAME)->first();
        $release = $this->releases->latestRelease();

        return Inertia::render('Settings/DesktopApp/Index', [
            'token' => $token ? [
                'created_at' => $token->created_at->toDateTimeString(),
                'last_used_at' => $token->last_used_at?->toDateTimeString(),
            ] : null,
            // GitHub API URL等の内部情報は渡さず、画面表示に必要な項目のみ渡す
            // （実際のダウンロードはsettings.desktop-app.downloadがサーバー側でプロキシする）。
            'release' => $release ? [
                'version' => $release['version'],
                'published_at' => $release['published_at'],
                'assets' => array_map(
                    fn (array $asset) => ['name' => $asset['name'], 'size' => $asset['size']],
                    $release['assets'],
                ),
            ] : null,
        ]);
    }

    public function store(): RedirectResponse
    {
        $user = Auth::user();
        $user->tokens()->where('name', self::TOKEN_NAME)->delete();

        $newToken = $user->createToken(self::TOKEN_NAME, [self::ABILITY]);

        return redirect()->route('settings.desktop-app.index')
            ->with('success', 'デスクトップ通知アプリ用のトークンを発行しました。')
            ->with('desktopAppToken', $newToken->plainTextToken);
    }

    public function destroy(): RedirectResponse
    {
        Auth::user()->tokens()->where('name', self::TOKEN_NAME)->delete();

        return redirect()->route('settings.desktop-app.index')
            ->with('success', 'デスクトップ通知アプリ用のトークンを失効しました。');
    }
}

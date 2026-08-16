<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * デスクトップ通知アプリ（desktop-app/）のビルド済みインストーラーを、
 * GitHub Releasesの最新リリースから取得する窓口。
 * リポジトリがprivateのため、一覧取得・ダウンロードのいずれもトークン認証が必要
 * （.github/workflows/desktop-app-release.yml がタグpushでビルド・公開する）。
 * GitHub側の障害時にトークン発行画面自体が壊れないよう、失敗時はnullを返し呼び出し側で握り潰す。
 */
class DesktopAppReleaseService
{
    private const CACHE_KEY = 'desktop-app:latest-release';

    private const CACHE_MINUTES = 10;

    /** アセットのファイル名末尾からOSを判定する対応表（判定を上から順に試す） */
    private const OS_SUFFIXES = [
        'windows' => '.msi',
        'macos' => '.dmg',
        'linux' => '.AppImage',
    ];

    public function latestRelease(): ?array
    {
        if (! config('services.github.token')) {
            return null;
        }

        return Cache::remember(self::CACHE_KEY, now()->addMinutes(self::CACHE_MINUTES), function () {
            return $this->fetchLatestRelease();
        });
    }

    /**
     * ダウンロードプロキシ（DesktopAppDownloadController）が使う、指定OS向けアセットの
     * GitHub API URL・ファイル名・サイズを返す。該当アセットがなければnull。
     */
    public function assetFor(string $os): ?array
    {
        return $this->latestRelease()['assets'][$os] ?? null;
    }

    private function fetchLatestRelease(): ?array
    {
        $repo = config('services.github.desktop_app_repo');

        try {
            $response = Http::withToken(config('services.github.token'))
                ->withHeaders(['Accept' => 'application/vnd.github+json'])
                ->timeout(5)
                ->get("https://api.github.com/repos/{$repo}/releases/latest");
        } catch (\Throwable $e) {
            Log::warning('デスクトップアプリの最新リリース取得に失敗しました。', ['exception' => $e]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('デスクトップアプリの最新リリース取得がGitHub側でエラーになりました。', [
                'status' => $response->status(),
            ]);

            return null;
        }

        $assets = [];
        foreach ($response->json('assets', []) as $asset) {
            $os = $this->osForFilename($asset['name']);
            if ($os !== null && ! isset($assets[$os])) {
                $assets[$os] = [
                    'name' => $asset['name'],
                    'size' => $asset['size'],
                    'api_url' => $asset['url'],
                ];
            }
        }

        if ($assets === []) {
            return null;
        }

        return [
            'version' => $response->json('tag_name'),
            'published_at' => $response->json('published_at'),
            'assets' => $assets,
        ];
    }

    private function osForFilename(string $filename): ?string
    {
        foreach (self::OS_SUFFIXES as $os => $suffix) {
            if (str_ends_with(strtolower($filename), strtolower($suffix))) {
                return $os;
            }
        }

        return null;
    }
}

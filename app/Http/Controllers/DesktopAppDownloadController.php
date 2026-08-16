<?php

namespace App\Http\Controllers;

use App\Services\DesktopAppReleaseService;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Http;

/**
 * デスクトップ通知アプリのインストーラーを、GitHub Releases（private repo）からログイン
 * ユーザーへ中継してダウンロードさせる。事務所に紐づく機能ではないため、テナント分離は
 * 不要（認証済みユーザーであれば事務所を問わずダウンロード可）。
 */
class DesktopAppDownloadController extends Controller
{
    private const ALLOWED_OS = ['windows', 'macos', 'linux'];

    public function __construct(private readonly DesktopAppReleaseService $releases) {}

    public function download(string $os): HttpResponse
    {
        abort_unless(in_array($os, self::ALLOWED_OS, true), 404);

        $asset = $this->releases->assetFor($os);
        abort_unless($asset !== null, 404, 'ダウンロード可能なインストーラーが見つかりませんでした。');

        $upstream = Http::withToken(config('services.github.token'))
            ->withHeaders(['Accept' => 'application/octet-stream'])
            ->timeout(30)
            ->get($asset['api_url']);

        abort_unless($upstream->successful(), 502, 'インストーラーの取得に失敗しました。時間をおいて再度お試しください。');

        return response($upstream->body(), 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.$asset['name'].'"',
            'Content-Length' => (string) $asset['size'],
        ]);
    }
}

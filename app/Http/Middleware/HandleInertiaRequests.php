<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user('web'),
                'enabledModules' => $request->user('web')?->office?->enabledModuleKeys() ?? [],
            ],
            'platformAuth' => [
                'admin' => $request->user('platform'),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'desktopAppToken' => fn () => $request->session()->get('desktopAppToken'),
                // 直前の登録・編集で対象になったレコードのID。一覧系画面はこれを見て
                // 該当行までスクロール・ハイライトする（Composables/useHighlightRow.js参照）。
                'highlightId' => fn () => $request->session()->get('highlightId'),
            ],
            'vapidPublicKey' => config('webpush.vapid.public_key'),
        ];
    }
}

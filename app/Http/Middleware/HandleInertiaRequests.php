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
            ],
            'platformAuth' => [
                'admin' => $request->user('platform'),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'desktopAppToken' => fn () => $request->session()->get('desktopAppToken'),
            ],
            'vapidPublicKey' => config('webpush.vapid.public_key'),
        ];
    }
}

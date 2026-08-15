<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Minishlink\WebPush\ContentEncoding;

class PushSubscriptionController extends Controller
{
    /**
     * ブラウザからのPush購読情報を保存（または更新）する。
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string|max:500',
            'public_key' => 'nullable|string',
            'auth_token' => 'nullable|string',
            'content_encoding' => ['nullable', Rule::enum(ContentEncoding::class)],
        ]);

        Auth::user()->updatePushSubscription(
            $validated['endpoint'],
            $validated['public_key'] ?? null,
            $validated['auth_token'] ?? null,
            $validated['content_encoding'] ?? null,
        );

        return response()->json(['status' => 'subscribed']);
    }

    /**
     * ブラウザのPush購読を解除する。
     */
    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string|max:500',
        ]);

        Auth::user()->deletePushSubscription($validated['endpoint']);

        return response()->json(['status' => 'unsubscribed']);
    }
}

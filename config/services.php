<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'github' => [
        // デスクトップ通知アプリのインストーラーをGitHub Releasesから配布するために使う。
        // リポジトリがprivateのため、一覧取得・ダウンロードともにトークンでの認証が必要。
        // Fine-grained PATを、対象リポジトリの Contents: Read-only 権限のみで発行すること。
        'token' => env('GITHUB_TOKEN'),
        'desktop_app_repo' => env('GITHUB_DESKTOP_APP_REPO', 'michimuko/syaroushi'),
    ],

];

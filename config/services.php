<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
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

    // 現行 felix_total（laravel-admin）の URL。
    'felix_total' => [
        // ブラウザ向け（明細リンクの iframe 先）。ユーザーのブラウザが到達できるホスト。
        'url' => env('FELIX_TOTAL_URL'),
        // サーバ間 HTTP 向け（order_estimate をコンテナから叩く）。コンテナが到達できるホスト。
        // 例: http://host.docker.internal:8070 。未設定なら url にフォールバック。
        'internal_url' => env('FELIX_TOTAL_INTERNAL_URL', env('FELIX_TOTAL_URL')),
        // 見積依頼処理（order_estimate）のパス。新スキーマ画面の見積依頼送信でサーバ間 HTTP で叩く。
        'quote_request_path' => env('FELIX_TOTAL_QUOTE_REQUEST_PATH', '/admin/estimates-custom-detail/order_estimate'),
    ],

];

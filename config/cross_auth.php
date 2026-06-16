<?php

// felix_total との認証連携（同期クッキー cross_auth）の設定。
// 詳細は docs/plans/cross-auth-cookie-plan.md を参照。
return [

    // HMAC 署名鍵。fix と felix_total で「完全に同じ値」にすること。
    'secret' => env('CROSS_AUTH_SECRET'),

    // cross_auth クッキーの Domain。両サブドメインの共通親（例 .felix-japan.local / .felix-japan.co.jp）。
    'domain' => env('CROSS_AUTH_DOMAIN'),

    // 有効期限（秒）。スライディング更新で認証中は延長され続ける。
    'ttl' => (int) env('CROSS_AUTH_TTL', 1800),

    // Secure 属性。本番(HTTPS)は true。ローカル http 検証時のみ false。
    'secure' => (bool) env('CROSS_AUTH_SECURE', true),

];

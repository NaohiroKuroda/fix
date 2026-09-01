<?php

/*
 * 業者への通知メールの設定。
 *
 * その場では送らずに別DBの `mail_queues` へ予約登録する。実際の配信は同テーブルを見る
 */

/** カンマ区切りの env をトリム済み配列へ。空要素は除去する。 */
$mailList = static fn (string $key): array => array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env($key, ''))
)));

return [
    /*
     * メールキューの接続先（config/database.php の `mysql_2`）とテーブル名。
     * 現行 felix_total と同じ DB を指すこと。
     */
    'connection' => env('MAIL_QUEUE_CONNECTION', 'mysql_2'),
    'table' => env('MAIL_QUEUE_TABLE', 'mail_queues'),

    /*
     * 差出人。全メール共通で現行と揃える（`from_name` は件名の【】内にも使う）。
     */
    'from_mail' => env('MAIL_QUEUE_FROM_MAIL', 'info@felix-japan.co.jp'),
    'from_name' => env('MAIL_QUEUE_FROM_NAME', 'フィリックス株式会社'),

    /*
     * 送信予約の猶予（分）。現行は「現在 +10分」で積む。
     */
    'send_delay_minutes' => (int) env('MAIL_QUEUE_SEND_DELAY_MINUTES', 10),

    /*
     * `send_time` を書くときのタイムゾーン。
     *
     * **本アプリの app.timezone（UTC）ではなく、キューDB／送信バッチの時刻に合わせること。**
     * 現行 felix_total は Asia/Tokyo で動いており、キューDB の NOW() も JST。UTC で積むと
     * 9時間前の日時になり、猶予を待たずに送信バッチへ拾われてしまう。
     */
    'timezone' => env('MAIL_QUEUE_TIMEZONE', 'Asia/Tokyo'),

    /*
     * テスト送信用。設定するとすべての宛先をこのアドレスへ差し替える。
     * 本番では必ず空にすること。カンマ区切りで複数指定できる。
     */
    'override_to' => $mailList('MAIL_QUEUE_OVERRIDE_TO'),

    /*
     * 業者マイページの基点 URL。現行 felix_total の APP_URL を指す
     * （リンクは `{base}/estimate/login/{estimate_unit_companies.id}/{access_token}`）。
     */
    'vendor_base_url' => env('MAIL_QUEUE_VENDOR_BASE_URL', env('FELIX_TOTAL_URL')),

    /*
     * ⑧「発注確定のお知らせ」で案内する発注書プレビューの URL テンプレート。
     * `{id}`（estimate_unit_companies.id）と `{token}`（company_tokens.access_token）を置換する。
     *
     * ※ もらいの発注書プレビュー URL は未確定。決まるまでは空にしておき、
     *   空のときは F-2 の送信をスキップする（警告ログのみ）。
     */
    'billing_order_preview_url' => env('MAIL_QUEUE_BILLING_ORDER_PREVIEW_URL', ''),
];

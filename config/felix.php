<?php

return [
    /*
     * 部長承認（manager-approval）／部長取消承認（cancel-approval）メニューを表示する
     * 「建設部部長」ロールの slug（admin_roles.slug）。
     * felix_total の建設選定承認権限（建設承認 = slug:tmp）に対応する。
     * これらの slug を1つでも持つアカウントだけがサイドメニューに上記2画面を出す。
     */
    'manager_role_slugs' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('FELIX_MANAGER_ROLE_SLUGS', 'tmp'))
    ))),

    /*
     * felix_total（現行）実行予算画面の「請求先（業者マイページ）」リンク先の URL テンプレート。
     * `{id}` を見積先ID（estimate_unit_companies.id）に置換する。
     *
     * リンク先は felix_total の auto_login（/admin 配下）にする。これにより
     *   cross_auth で admin ログイン → CompanyToken 発行 → /estimate/login で業者を強制ログイン → estimate 編集ページ
     * と繋がり、web ガード（業者ユーザ　ー）が確立した状態で編集ページが開く。
     * （直接 /estimate/edit/{id} を開くと業者ログインが無く「権限がありません」になるため使わない）
     *
     * 既定では FELIX_TOTAL_URL を基点に組み立てる。cross_auth クッキー送信のため
     * felix_total と同じ親ドメイン（.felix-japan.local 等）のホストである必要がある。
     * 見積依頼画面の見積先名をこの URL のリンクにする（別タブで開く）。空にするとプレーン表示。
     */
    'estimate_edit_url' => env(
        'FELIX_ESTIMATE_EDIT_URL',
        rtrim((string) env('FELIX_TOTAL_URL', 'https://fix.felix-japan.co.jp'), '/')
            . '/admin/estimates-custom-detail/auto_login?estimate_unit_company_id={id}'
    ),

    /*
     * 見積依頼画面「業者追加」リンク（iframe で開く felix_total の見積先追加画面）の URL テンプレート。
     * `{id}` を見積項目ID（estimate_units.id）に置換する。
     *
     * felix_total 実行予算明細（estimate_edit）の「+追加」と同じ URL を使う：
     *   /admin/estimate-unit-companies/create/?iframe=on&estimate_unit_id={項目ID}
     * laravel-admin の作成フォームを iframe モードで開く（admin ログインで動作。auto_login 不要）。
     * cross_auth / FrameAncestors のため、felix_total と同じ親ドメインのホストである必要がある。
     * 空にするとリンク押下時に「URL 未設定」を表示する。
     */
    'add_vendor_url' => env(
        'FELIX_ADD_VENDOR_URL',
        rtrim((string) env('FELIX_TOTAL_URL', 'https://fix.felix-japan.co.jp'), '/')
            . '/admin/estimate-unit-companies/create/?iframe=on&estimate_unit_id={id}'
    ),

    /*
     * 見積先（会社名リンク）から開く「見積先の詳細」（iframe）の URL テンプレート。
     * `{id}` を見積先ID（estimate_unit_companies.id）に置換する。
     *
     * felix_total 実行予算明細の見積先名リンクと同じ URL を使う：
     *   /admin/estimate-unit-companies/{見積先ID}/edit/?iframe=on
     * laravel-admin の編集フォームを iframe モードで開く（admin ログインで動作）。
     * 空にすると会社名はリンクにせずプレーン表示にする。
     */
    'vendor_detail_url' => env(
        'FELIX_VENDOR_DETAIL_URL',
        rtrim((string) env('FELIX_TOTAL_URL', 'https://fix.felix-japan.co.jp'), '/')
            . '/admin/estimate-unit-companies/{id}/edit/?iframe=on'
    ),
];

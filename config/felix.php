<?php

/** カンマ区切りの env をトリム済み slug 配列へ。空要素は除去する。 */
$slugList = static fn (string $key, string $default): array => array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env($key, $default))
)));

/*
 * サイドメニューの表示ロール（admin_roles.slug）。ロール → 表示できるメニューの対応は
 * 下の menu_roles を唯一の正（Single Source of Truth）とする。新メニュー（例: 発注管理）は
 * menu_roles に1行足すだけで表示制御できるようにしてある。
 */
// 「部長」ロール：部長承認 / 部長取消承認、やり取りの発言ロール（manager）、承認判定に使う。
// 建設部部長（engineer_manager）と建設承認（tmp）の両方を部長として扱う。
$managerRoleSlugs = $slugList('FELIX_MANAGER_ROLE_SLUGS', 'engineer_manager,tmp');
// 「担当」ロール：見積依頼 / 業者選定 / 部長取消申請。建設部（engineer）とシステム開発（system）。
$staffRoleSlugs = $slugList('FELIX_STAFF_ROLE_SLUGS', 'engineer,system');
// 「管理者」ロール：全メニューを表示するスーパーユーザー。admin_roles に実在する slug を設定すること
// （現行データには administrator slug が無いため、実運用の slug に合わせて env で上書きする）。
$adminRoleSlugs = $slugList('FELIX_ADMIN_ROLE_SLUGS', 'administrator');

return [
    /*
     * 部長承認（manager-approval）／部長取消承認（cancel-approval）の権限・発言ロール判定に使う
     * 「部長」ロールの slug。建設部部長（engineer_manager）＋建設承認（tmp）。
     */
    'manager_role_slugs' => $managerRoleSlugs,

    /*
     * 見積依頼 / 業者選定 / 部長取消申請を表示する「担当」ロールの slug（建設部・システム開発）。
     */
    'staff_role_slugs' => $staffRoleSlugs,

    /*
     * 全メニューを表示する「管理者」ロールの slug。
     */
    'admin_role_slugs' => $adminRoleSlugs,

    /*
     * メニューキー → 表示を許可するロール slug の一覧。administrator（admin_role_slugs）は
     * 別途すべてのメニューを表示するスーパーユーザー扱いとする（ここには列挙しない）。
     * 発注管理（order-management）は今後実装予定。表示ロールが決まったら slug を設定する。
     */
    'menu_roles' => [
        'quote-request'    => $staffRoleSlugs,
        'vendor-selection' => $staffRoleSlugs,
        'cancel-request'   => $staffRoleSlugs,
        'manager-approval' => $managerRoleSlugs,
        'cancel-approval'  => $managerRoleSlugs,
        'order-management' => [],
    ],

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
     *   /admin/estimate-unit-companies/{見積先ID}/edit/?iframe=on&from=fix
     * laravel-admin の編集フォームを iframe モードで開く（admin ログインで動作）。
     * 空にすると会社名はリンクにせずプレーン表示にする。
     *
     * `from=fix` は「fix から開いた」ことを示す目印。felix_total 自身も同じ編集画面を
     * `?iframe=on` で開くため、fix 経由のときだけ会社名の編集をロックする判定に使う
     * （felix_total 側 `app/Admin/bootstrap.php` が `request('from') === 'fix'` を参照）。
     */
    'vendor_detail_url' => env(
        'FELIX_VENDOR_DETAIL_URL',
        rtrim((string) env('FELIX_TOTAL_URL', 'https://fix.felix-japan.co.jp'), '/')
            . '/admin/estimate-unit-companies/{id}/edit/?iframe=on&from=fix'
    ),
];

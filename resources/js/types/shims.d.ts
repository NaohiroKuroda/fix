declare module '*.vue' {
    import type { DefineComponent } from 'vue';

    const component: DefineComponent<Record<string, unknown>, Record<string, unknown>, unknown>;
    export default component;
}

import type { PageProps as InertiaPageProps } from '@inertiajs/core';

declare module '@inertiajs/core' {
    interface PageProps extends InertiaPageProps {
        // 共有プロップ（HandleInertiaRequests::share）
        auth: {
            user: {
                id: number;
                name: string;
                // 付与ロールの slug 一覧。
                roles: string[];
                // 建設部部長か（部長承認・部長取消承認メニューの表示判定）。
                isEstimateManager: boolean;
            } | null;
        };
        // 現行 felix_total の URL（明細リンクの iframe 先）
        felixTotalUrl: string | null;
        // フラッシュメッセージ（成功 / エラー）。リダイレクト後に1回だけ表示する。
        flash: {
            success: string | null;
            error: string | null;
        };
        // サイドメニューの未処理件数バッヂ（部分リロード時は未送出のため null になりうる）。
        menuBadges: {
            'quote-request': number;
            'vendor-selection': number;
            // 業者選定（差し戻し）：部長承認で否認され業者選定へ戻った件数（2つ目のバッヂ）。
            'vendor-selection-rejected': number;
            'manager-approval': number;
            'cancel-approval': number;
        } | null;
        // サイドメニューの表示可否（ロール別）。メニューキー => 表示するか。
        // config/felix.php（menu_roles）が唯一の正。発注管理など追加時はキーが増える。
        menuPermissions: Record<string, boolean>;
    }
}

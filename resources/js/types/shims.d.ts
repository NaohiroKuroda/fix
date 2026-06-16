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
            } | null;
        };
        // 現行 felix_total の URL（明細リンクの iframe 先）
        felixTotalUrl: string | null;
    }
}

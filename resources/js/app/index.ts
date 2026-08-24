import '../../css/app.css';

import { createApp, h, type DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    // Inertia のページ名 = pages スライスのパス（kebab-case）。
    // スライスの public API（index.ts）を解決する。frontend.md 4.3.8 参照。
    resolve: (name) =>
        resolvePageComponent(
            `../pages/${name}/index.ts`,
            // スライス（pages/<slice>）とスライスグループ配下（pages/<group>/<slice>）の2階層。
            // セグメント内に index.ts を作らない前提（frontend.md 4.4 の注記）。
            import.meta.glob<DefineComponent>(['../pages/*/index.ts', '../pages/*/*/index.ts']),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#c4a35b',
    },
});

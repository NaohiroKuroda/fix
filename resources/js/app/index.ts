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
            // スライス（pages/<slice>）と、スライスグループ配下（pages/<group>/<slice>、
            // pages/<業務>/<区分>/<slice>）の最大3階層。バックエンドの
            // Controllers/<業務>/<区分>/ と同じ分け方に合わせている（frontend.md 4.3.8）。
            // セグメント内に index.ts を作らない前提（frontend.md 4.4 の注記）。
            import.meta.glob<DefineComponent>([
                '../pages/*/index.ts',
                '../pages/*/*/index.ts',
                '../pages/*/*/*/index.ts',
            ]),
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

import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app/index.ts'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
            // 生成物は「業務ロジックを持たないバックエンド連携」なので shared/api 配下に出す
            // （frontend.md 4.6）。→ shared/api/{actions,routes,wayfinder}
            path: 'resources/js/shared/api',
        }),
    ],
    server: {
        // Docker コンテナ内でも待ち受けられるよう全インターフェースで listen。
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        // 公開ポート経由でブラウザ（ホスト側）からアセット/HMR を取得する。
        origin: 'http://localhost:5173',
        // Laravel アプリ（:8090）から :5173 のモジュール/HMR を読み込むため CORS を許可。
        // Vite v6+ はデフォルトで配信元オリジンしか許可しないため、localhost と
        // 開発用サブドメイン（*.felix-japan.local。felix_total 連携の検証で使用）を許可する。
        cors: {
            origin: [
                /^https?:\/\/localhost(:\d+)?$/,
                /^https?:\/\/[\w.-]+\.felix-japan\.(local|test)(:\d+)?$/,
            ],
        },
        hmr: {
            host: 'localhost',
        },
        watch: {
            // Windows のバインドマウントは inotify が効かないため polling を使う
            // （docker-compose で VITE_USE_POLLING=1 を渡す。ホスト直開発では未設定で高速）。
            usePolling: process.env.VITE_USE_POLLING === '1' || process.env.VITE_USE_POLLING === 'true',
            ignored: ['**/storage/framework/views/**'],
        },
    },
});

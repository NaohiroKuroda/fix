import fsd from '@feature-sliced/steiger-plugin';
import { defineConfig } from 'steiger';

// FSD 構造の検証（frontend.md 4.13）。レイヤルートは resources/js。
export default defineConfig([
    ...fsd.configs.recommended,
    {
        // 自動生成物は検査対象外（Wayfinder が出力するため手で整えられない）。
        ignores: [
            '**/shared/api/actions/**',
            '**/shared/api/routes/**',
            '**/shared/api/wayfinder/**',
        ],
    },
]);

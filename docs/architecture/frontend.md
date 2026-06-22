# フロントエンドアーキテクチャ

> 対象: `new_felix_total` のフロントエンド層。
> 実装前に必ず本ドキュメントを確認すること。

## 技術スタック

| 分類 | 採用技術 | バージョン |
| --- | --- | --- |
| 言語 | TypeScript | `^6.0` |
| UI フレームワーク | Vue（`<script setup lang="ts">` / Composition API） | `^3.5` |
| SPA 連携 | Inertia.js（`@inertiajs/vue3`） | `^3.3` |
| UI コンポーネント | shadcn-vue（内部で reka-ui を利用） | reka-ui `^2.9` |
| CSS | Tailwind CSS（`@tailwindcss/vite`） | `^4.0` |
| アイコン | `lucide-vue-next` | `^1.0` |
| ユーティリティ | `@vueuse/core` / `clsx` / `tailwind-merge` / `class-variance-authority` | `^14.3` / `^2.1` / `^3.6` / `^0.7` |
| 日付処理 | `dayjs` | `^1.11` |
| ルーティング型 | Laravel Wayfinder（`@laravel/vite-plugin-wayfinder`） | `^0.1` |
| ビルド | Vite（`laravel-vite-plugin` / `@vitejs/plugin-vue`） | Vite `^8.0` |
| 型チェック | `vue-tsc` | `^3.3` |

> パッケージ単位の詳細は §4.1、各技術の運用方針は §4.2 以降を参照。
> 新規パッケージを追加する場合は、本ドキュメントに追記してから導入する（[`CLAUDE.md`](../../CLAUDE.md)）。

## 4.1 フロントエンド（Vue 3 / Inertia.js 3.x）

* **役割**
    * UI のレンダリングおよびユーザー入力のハンドリング。
    * Inertia フォーム（`useForm`）によるリクエスト送信および SPA 状態の管理。
* **使用パッケージ（composer）**
    * `laravel/wayfinder`
* **使用パッケージ（npm）**
    * `@inertiajs/vue3`（3.0 以上）
    * `dayjs`
    * `reka-ui`
    * `vue`（3.5 以上）
    * `@laravel/vite-plugin-wayfinder`（開発環境のみ）
    * `@tailwindcss/vite`（開発環境のみ）
    * `laravel-vite-plugin`（開発環境のみ）
    * `typescript`（開発環境のみ）
    * `vite`（開発環境のみ）
    * `tailwindcss`（開発環境のみ、4.0 以上）
    * `shadcn-vue`

## 4.2 言語・記法

- すべてのコンポーネント・ロジックは **TypeScript** で記述する。
- Vue コンポーネントは `<script setup lang="ts">` を使用する（Composition API）。
- 型定義は `resources/js/types/` に集約する。サーバから渡る Inertia の props 型もここで定義する。
- `any` は原則禁止。やむを得ない場合はコメントで理由を明記する。
- **SFC（Single File Component）の徹底**: 必ず `<script setup lang="ts">` を使用すること。Options API（`data()`, `methods` 等）は全面禁止。
- **JSX / TSX の全面禁止**: 将来的な Vue 3.6 Vapor モード（仮想DOMに依存しないコンパイル戦略）へのスムーズな移行を見据え、JSX/TSX および `render()` 関数によるコンポーネント記述は全面禁止とする。UIは必ず `<template>` タグを用いたHTMLライクな構文で記述すること。
- **ロジックの排除**: ビジネスロジックや高度なデータ加工は行わず、Props から受け取ったデータをそのまま表示すること（加工は JsonResource 層で行う）。
- **スタイル定義の制限**: `.vue` ファイルの `style` ブロック、およびコンポーネント内要素への `style` 属性（インラインスタイル）の直接指定は全面禁止。
- **リアクティビティの最適化**: `ref()` と `computed()` を適切に使い分けること。高頻度なイベント（マウス移動、キャンバス操作等）が発生する箇所では、オブジェクトの再生成を避け、ガベージコレクション（GC）負荷を最小限に抑えるクリーンなコードを書くこと。
- **Inertia 固有機能の利用**: フォーム送信時は必ず Inertia の `useForm` を使用し、送信状態（`processing`）やエラー（`errors`）を統合管理する。ページ遷移は `Link` コンポーネントを使用し、ルーティングには Ziggy の `route('name')` を使用して URL のハードコーディングを禁止する。

## 4.3 ディレクトリ構成

```
resources/
├── css/
│   └── app.css                 # Tailwind エントリ
└── js/
    ├── app.ts                  # Inertia アプリのエントリポイント
    ├── ssr.ts                  # （任意）SSR エントリ
    ├── pages/                  # Inertia ページコンポーネント（ルートに対応）
    │   └── StatusManagement/
    │       └── Index.vue
    ├── layouts/                # 共通レイアウト
    │   └── AppLayout.vue
    ├── components/             # 再利用コンポーネント
    │   ├── EstimateSection.vue
    │   └── ui/                 # shadcn-vue 生成コンポーネント
    │       ├── button/
    │       ├── card/
    │       ├── input/
    │       └── ...
    ├── composables/            # use 系の再利用ロジック
    ├── lib/                    # ユーティリティ（cn など）
    │   └── utils.ts
    ├── types/                  # 型定義（Inertia props / ドメイン型）
    │   └── index.ts
    └── actions/ , routes/      # Wayfinder 自動生成（編集禁止）
```

規約:

- **`pages/`** … Inertia の「ページ」。サーバの `Inertia::render('StatusManagement/Index', ...)` と 1:1 対応。
- **`components/`** … ページ横断で再利用する部品。状態は props / emits で受け渡す。
- **`components/ui/`** … shadcn-vue が生成する UI プリミティブ。手動編集は最小限にとどめる。
- Wayfinder 生成物（`actions/` `routes/`）は **自動生成のため手で編集しない**。

## 4.4 エントリポイント（Inertia セットアップ）

`resources/js/app.ts`（移行後の想定）:

```ts
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'

createInertiaApp({
  resolve: (name) =>
    resolvePageComponent(
      `./pages/${name}.vue`,
      import.meta.glob<DefineComponent>('./pages/**/*.vue'),
    ),
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el)
  },
})
```

> 現状の `app.js`（`createApp(StatusManagement).mount('#status-management-app')`）は Inertia 化に伴い廃止し、上記 `app.ts` に置き換える。

## 4.5 フォーム / 状態管理

- サーバへの送信は Inertia の **`useForm`** を用いる。`fetch`/`axios` の直接利用は原則禁止（ファイルダウンロード等の例外を除く）。
- 送信先 URL・HTTP メソッドは **Wayfinder の生成アクション** から取得し、文字列ハードコードを避ける。

```ts
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { store } from '@/actions/App/Http/Controllers/EstimateController'

const form = useForm({
  title: '',
  amount: 0,
})

function submit() {
  form.submit(store())   // Wayfinder が { url, method } を返す
}
</script>
```

- ローカル UI 状態は `ref` / `reactive` / `computed` で管理する。
- 横断的な再利用ロジックは `composables/`（`useXxx`）に切り出す。

### フラッシュメッセージ（成功 / エラー通知）

- サーバ側の処理結果は **Inertia 共有プロパティ `flash`**（`{ success, error }`）で配る。Controller は `back()->with('success', ...)` / `->with('error', ...)` で積み、`HandleInertiaRequests::share()` が `flash` として公開する。
- 表示は `components/feedback/` の常設コンポーネントが担う。
    - `FlashMessages.vue` … `usePage().props.flash` を購読し、画面右上にトーストを積んで自動消去する。`AppLayout.vue` に常設し全画面で共用する。
    - `AppToast.vue` … 1件分のトースト（`variant: 'success' | 'error'`）。表示専用。
- フォーム成功時に「メッセージ表示＋一覧の再読込」を行いたい場合は、Controller で `back()` を返す（Inertia が同一ページを再取得＝リロード）。クライアントで個別に再取得処理を書かない。

## 4.6 ルーティング（Laravel Wayfinder）

- ルート/コントローラアクションへの参照は **Wayfinder が生成する型付き関数**を使用する。
- 生成は Vite プラグイン `@laravel/vite-plugin-wayfinder`（開発環境）で行う。
- インポートエイリアスは `@/` = `resources/js/`（`vite.config` / `tsconfig` で設定済み）。

## 4.7 UI / スタイリング

- **Tailwind CSS 4** を使用。`@tailwindcss/vite` プラグイン経由でビルドする。
- UI プリミティブは **shadcn-vue**（内部で **reka-ui** を利用）を採用。新規 UI は原則 shadcn-vue の生成物をベースに構築する。
- クラス結合は `lib/utils.ts` の `cn()`（`clsx` + `tailwind-merge`）を使用する。
- アイコンは `lucide-vue-next` を使用する。

## 4.8 日付処理

- 日付・時刻の整形/演算は **dayjs** を使用する。素の `Date` 操作や `moment` は使用しない。

## 4.9 ビルド設定（Vite）

`vite.config.ts`（移行後の想定）の要点:

- プラグイン: `laravel-vite-plugin` / `@vitejs/plugin-vue` / `@tailwindcss/vite` / `@laravel/vite-plugin-wayfinder`
- エントリ入力: `resources/css/app.css`, `resources/js/app.ts`
- エイリアス: `@` → `resources/js`

```ts
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import wayfinder from '@laravel/vite-plugin-wayfinder'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  resolve: {
    alias: { '@': fileURLToPath(new URL('./resources/js', import.meta.url)) },
  },
  plugins: [
    laravel({ input: ['resources/css/app.css', 'resources/js/app.ts'], refresh: true }),
    vue(),
    tailwindcss(),
    wayfinder(),
  ],
})
```

## 4.10 TypeScript 設定

- `jsconfig.json` を **`tsconfig.json`** に置き換える。
- `paths`: `@/*` → `resources/js/*`。
- `strict: true` を有効にする。

## 4.11 命名規約

| 対象 | 規約 | 例 |
| --- | --- | --- |
| ページコンポーネント | PascalCase | `pages/StatusManagement/Index.vue` |
| 部品コンポーネント | PascalCase | `components/EstimateSection.vue` |
| composable | camelCase + `use` 接頭辞 | `composables/useEstimate.ts` |
| 型 | PascalCase | `types/index.ts` の `Estimate` |
| ユーティリティ | camelCase | `lib/utils.ts` の `cn` |

## 4.12 現状からの移行チェックリスト

- [ ] `@inertiajs/vue3`, `dayjs`, `typescript` 等 不足 npm パッケージを追加
- [ ] `laravel/wayfinder`（composer）, `@laravel/vite-plugin-wayfinder`（npm）を追加
- [ ] `resources/js/app.js` → `app.ts`（Inertia セットアップ）へ置換
- [ ] `jsconfig.json` → `tsconfig.json`（`strict: true`）
- [ ] `vite.config.js` → `vite.config.ts`（wayfinder プラグイン追加）
- [ ] 既存 Vue 部品を `<script setup lang="ts">` へ移行
- [ ] Blade 直接マウント構成を Inertia ページ構成へ移行
- [ ] サーバ側 `Inertia::render(...)` 対応（`backend.md` 参照）

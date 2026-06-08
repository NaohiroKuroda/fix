# new_felix_total アーキテクチャ概要

本ドキュメント群は `new_felix_total` システムの設計指針を定義する。
**実装前に必ず本ドキュメントを確認し、これを唯一の正（Single Source of Truth）として扱うこと。**

## 1. 目的

- Laravel 13 / PHP 8.3 をバックエンド、Vue 3 + Inertia.js + TypeScript をフロントエンドとする SPA 型 Web アプリケーションを構築する。
- サーバサイドレンダリングのルーティング駆動と、SPA のリッチな UX を両立する（Inertia.js による Monolith SPA）。

## 2. 技術スタック

### 2.1 バックエンド

| 項目 | 採用 | バージョン |
| --- | --- | --- |
| 言語 | PHP | `^8.3` |
| フレームワーク | Laravel | `^13.8` |
| REPL | laravel/tinker | `^3.0` |
| ルーティング型生成 | laravel/wayfinder | 最新 |
| Lint/Format | laravel/pint | `^1.27` |
| テスト | phpunit | `^12.5` |

### 2.2 フロントエンド

| 項目 | 採用 | バージョン |
| --- | --- | --- |
| 言語 | TypeScript | 最新 |
| フレームワーク | Vue | `^3.5` |
| SPA アダプタ | @inertiajs/vue3 | `^3.0` |
| UI コンポーネント | shadcn-vue / reka-ui | 最新 |
| CSS | Tailwind CSS | `^4.0` |
| 日付処理 | dayjs | 最新 |
| ビルドツール | Vite | 最新 |

詳細は [`frontend.md`](frontend.md) を参照。

## 3. システム構成（論理）

```
┌─────────────────────────────────────────────────────────┐
│                      Browser (SPA)                       │
│   Vue 3 + Inertia.js + TypeScript + Tailwind / shadcn    │
└───────────────▲───────────────────────┬─────────────────┘
                │  Inertia (XHR/JSON)    │  初回 HTML
                │                        │
┌───────────────┴───────────────────────▼─────────────────┐
│                   Laravel 13 (PHP 8.3)                    │
│  Routes → Controller → (FormRequest) → Action/Service    │
│              → Inertia::render(page, props)               │
│  Wayfinder により FE 向けに型付きルート/アクションを生成 │
└───────────────────────────┬─────────────────────────────┘
                            │
                    ┌───────▼────────┐
                    │   Database     │
                    └────────────────┘
```

## 4. レイヤ別アーキテクチャ

| # | レイヤ | ドキュメント |
| --- | --- | --- |
| 4.1 | フロントエンド（Vue 3 / Inertia.js） | [`frontend.md`](frontend.md) |
| 4.2 | バックエンド（Laravel 13 / PHP 8.3） | [`backend.md`](backend.md) |

## 5. 現状からの移行方針

現状（移行前）:

- Vue コンポーネントを Blade に `createApp().mount()` で直接マウントする構成（`resources/js/app.js`）。
- JavaScript（`.js`）で記述。Inertia / TypeScript / Wayfinder は未導入。
- shadcn-vue / reka-ui / Tailwind 4 は導入済み。

目標（移行後）:

- Inertia.js を導入し、ページ遷移を Inertia ベースに統一する。
- `.js` → `.ts` / `.vue`（`<script setup lang="ts">`）へ移行する。
- Laravel Wayfinder で型付きルート/アクションを生成し、`useForm` から利用する。

移行手順の詳細は各レイヤのドキュメントに記載する。

## 6. ドキュメント運用ルール

- 設計を変更する際は **コードより先に本ドキュメントを更新する**。
- 新規パッケージの追加は、用途を本ドキュメントに追記してから行う。
- ディレクトリ規約・命名規約は各レイヤのドキュメントを正とする。

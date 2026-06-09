# new_felix_total（FELIX 実行予算）

FELIX の実行予算管理を担う Web アプリケーション。既存 `felix_total` の
MySQL（`fix_db`）を参照し、実行予算の一覧・検索・原価/予算の表示を行う。

Laravel 13（PHP 8.3）をバックエンド、Vue 3 + Inertia.js + TypeScript を
フロントエンドとする **Monolith SPA**（Inertia.js）構成。

> 🛑 **実装前に必ず [`CLAUDE.md`](CLAUDE.md) と [`docs/architecture/`](docs/architecture/README.md) を読むこと。**
> アーキテクチャドキュメントを唯一の正（Single Source of Truth）として扱う。

## 技術スタック

| レイヤ | 採用技術 |
| --- | --- |
| 言語 (BE) | PHP 8.3 |
| FW (BE) | Laravel 13 |
| 言語 (FE) | TypeScript |
| FW (FE) | Vue 3.5+ / Inertia.js 3.x |
| UI | shadcn-vue / reka-ui / Tailwind CSS 4 |
| ビルド | Vite |
| ルーティング型 | Laravel Wayfinder |
| テスト | PHPUnit 12 |
| Lint/Format | Laravel Pint |
| CI/CD | GitHub Actions |

## 必要環境

- PHP 8.3 / Composer
- Node.js 22+ / npm
- Docker Desktop（Docker 開発する場合）
- 接続先の MySQL（既存 `felix_total` の `fix_db`。ホスト `3307` 公開を想定）

## セットアップ

### A. Docker で開発（推奨 / Windows・Mac 共通）

事前に `felix_total` を docker 起動し、MySQL をホスト `3307` に公開しておく。

```bash
cp .env.example .env
docker compose up -d --build
# アプリ: http://localhost:8090/   （Vite HMR: http://localhost:5173/）
```

詳細・トラブルシュートは [`docs/docker-development.md`](docs/docker-development.md)。

### B. ホストで直接開発

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
composer run dev   # server / queue / logs / vite を同時起動
```

`.env` の `DB_HOST=127.0.0.1` / `DB_PORT=3307` で `fix_db` に接続する。

## よく使うコマンド

| 目的 | コマンド |
| --- | --- |
| 開発サーバー一括起動 | `composer run dev` |
| 本番ビルド | `npm run build` |
| 型チェック | `npm run type-check` |
| テスト | `php artisan test` |
| コードスタイル検査 / 整形 | `./vendor/bin/pint --test` / `./vendor/bin/pint` |

## ディレクトリ構成（抜粋）

```
app/            … Laravel アプリ（Controllers / Requests / Repositories ...）
config/         … 設定（status_management 等）
resources/js/   … フロントエンド（Vue / Inertia / TypeScript）
  components/    … 再利用コンポーネント（BudgetTable, CostTable ...）
  pages/         … Inertia ページ（Estimate, ExecutionBudget ...）
  composables/ , lib/ , types/
routes/         … ルート定義
docs/           … アーキテクチャ / デプロイ / CI-CD ドキュメント
.github/workflows/ … CI/CD（GitHub Actions）
```

## ブランチ戦略とデプロイ

| ブランチ | 役割 | デプロイ先 | サーバー |
| --- | --- | --- | --- |
| `main` | 本番 | 本番環境 | さくらのレンタルサーバー |
| `staging` | 社内検証 | 検証環境 | 社内 Windows サーバー（Docker） |
| `develop` | 開発 | 開発環境 | 開発サーバー（Docker） |

各ブランチへ **push / マージ** すると GitHub Actions が対応環境へ自動デプロイする。
全 PR では CI（Lint・型チェック・テスト・ビルド）が走る。

詳細・必要な Secrets / Variables・有効化手順は [`docs/cicd.md`](docs/cicd.md)。

## ドキュメント

| ドキュメント | 内容 |
| --- | --- |
| [`CLAUDE.md`](CLAUDE.md) | 開発ガイド / 実装前の必須確認 |
| [`docs/architecture/README.md`](docs/architecture/README.md) | 全体アーキテクチャ・技術スタック |
| [`docs/architecture/frontend.md`](docs/architecture/frontend.md) | フロントエンド（Vue / Inertia / TS） |
| [`docs/architecture/backend.md`](docs/architecture/backend.md) | バックエンド（Laravel / PHP） |
| [`docs/docker-development.md`](docs/docker-development.md) | Docker 開発環境ガイド |
| [`docs/deploy.md`](docs/deploy.md) | 社内サーバー Docker デプロイ手順 |
| [`docs/cicd.md`](docs/cicd.md) | CI/CD（GitHub Actions）ガイド |

## ライセンス

本リポジトリは社内利用を目的とする。フレームワーク（Laravel）は
[MIT License](https://opensource.org/licenses/MIT)。

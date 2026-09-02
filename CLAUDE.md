# new_felix_total — 開発ガイド

## 🛑 実装前の必須確認事項

**このリポジトリでコードを実装・変更する前に、必ず以下のアーキテクチャドキュメントを読み、準拠すること。**
1から順に適応していくこと。
1. [`docs/architecture/ai-architecture-instructions.md`](docs/architecture/ai-architecture-instructions.md) — 全体アーキテクチャ / 北川さん作成
2. [`docs/architecture/README.md`](docs/architecture/README.md) — 全体アーキテクチャ / 技術スタック
3. [`docs/architecture/frontend.md`](docs/architecture/frontend.md) — フロントエンド（Vue 3 / Inertia.js / TypeScript）
4. [`docs/architecture/backend.md`](docs/architecture/backend.md) — バックエンド（Laravel 13 / PHP 8.3）

ドキュメントとコードに差異がある場合、または新しい設計判断が必要な場合は、**先にドキュメントを更新してから実装すること**。ドキュメントを唯一の正（Single Source of Truth）として扱う。

## 技術スタック（要約）

| レイヤ | 採用技術 |
| --- | --- |
| 言語(BE) | PHP 8.3 |
| FW(BE) | Laravel 13 |
| 言語(FE) | TypeScript |
| FW(FE) | Vue 3.5+ / Inertia.js 3.x |
| UI | shadcn-vue / reka-ui / Tailwind CSS 4 |
| ビルド | Vite |
| ルーティング型 | Laravel Wayfinder |

## 実装の進め方

1. 該当アーキテクチャドキュメントを読む。
2. 既存のディレクトリ規約・命名規約に合わせる。
3. 型（TypeScript / PHP の型宣言）を必ず付与する。
4. ドキュメントに記載のないパッケージを追加する場合は、ドキュメントに追記してから導入する。

## 実装スタイル（ponytail）

**コードを書く・直す・リファクタする前に、必ず `ponytail` スキルを使うこと。**
（`ponytail:ponytail` / 既定の強度は full。範囲を絞りたいときは lite、徹底するときは ultra）

判断の順番は次のとおり。

1. **そもそも作る必要があるか**（YAGNI）。要らないなら作らない。
2. **標準ライブラリ・フレームワーク標準機能で足りないか**。足りるなら自作しない。
3. **既存の実装・仕組みを流用できないか**（支払系の作りを請求系へ、など）。
4. それでも書くなら**最小限**で。使われない抽象化・設定項目・ボイラープレートを足さない。
5. 意図的に手を抜いた箇所は `ponytail:` コメントで「何が限界か・将来どう直すか」を残す。

### 上のルールより優先されないもの

ponytail は**コードの量と形**に効かせるもので、次を省略する理由にはしない。

- 実装前のアーキテクチャドキュメント確認と、差異があればドキュメントを先に更新すること
- 型（TypeScript / PHP）の付与
- 「なぜそうしたか」が非自明な箇所の日本語コメント（`ponytail:` コメントを含む）

迷ったらドキュメント側のルールを優先し、その中で最小の実装を選ぶ。

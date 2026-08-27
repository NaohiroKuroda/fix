# フロントエンドアーキテクチャ

> 対象: `new_felix_total` のフロントエンド層。
> 実装前に必ず本ドキュメントを確認すること。

## 技術スタック

| 分類 | 採用技術 | バージョン |
| --- | --- | --- |
| 言語 | TypeScript | `^6.0` |
| UI フレームワーク | Vue（`<script setup lang="ts">` / Composition API） | `^3.5` |
| SPA 連携 | Inertia.js（`@inertiajs/vue3`） | `^3.3` |
| **アーキテクチャ** | **Feature-Sliced Design (FSD) v2.1** | — |
| UI コンポーネント | shadcn-vue（内部で reka-ui を利用） | reka-ui `^2.9` |
| CSS | Tailwind CSS（`@tailwindcss/vite`） | `^4.0` |
| アイコン | `lucide-vue-next` | `^1.0` |
| ユーティリティ | `@vueuse/core` / `clsx` / `tailwind-merge` / `class-variance-authority` | `^14.3` / `^2.1` / `^3.6` / `^0.7` |
| 日付処理 | `dayjs` | `^1.11` |
| ルーティング型 | Laravel Wayfinder（`@laravel/vite-plugin-wayfinder`） | `^0.1` |
| ビルド | Vite（`laravel-vite-plugin` / `@vitejs/plugin-vue`） | Vite `^8.0` |
| 型チェック | `vue-tsc` | `^3.3` |
| アーキテクチャ Lint | Steiger（FSD 公式 linter） | `steiger ^0.6` / `@feature-sliced/steiger-plugin ^0.7` |

> パッケージ単位の詳細は §4.1、各技術の運用方針は §4.2 以降を参照。
> 新規パッケージを追加する場合は、本ドキュメントに追記してから導入する（[`CLAUDE.md`](../../CLAUDE.md)）。
> ディレクトリ構成・コード配置は §4.3（FSD）が唯一の正。判断に迷ったら
> スキル [`.claude/skills/feature-sliced-design`](../../.claude/skills/feature-sliced-design/SKILL.md)（出典: [fsd.how](https://fsd.how)）を参照する。

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
    * `steiger` / `@feature-sliced/steiger-plugin`（開発環境のみ）
    * `@types/node`（開発環境のみ）

## 4.2 言語・記法

- すべてのコンポーネント・ロジックは **TypeScript** で記述する。
- Vue コンポーネントは `<script setup lang="ts">` を使用する（Composition API）。
- 型定義は **所属するスライスの `model/` または `api/` セグメントに置く**（§4.3.3）。レイヤ横断の共通型は `shared/api/` に置く。技術的役割で束ねた `types/` ディレクトリは作らない。
- `any` は原則禁止。やむを得ない場合はコメントで理由を明記する。
- **SFC（Single File Component）の徹底**: 必ず `<script setup lang="ts">` を使用すること。Options API（`data()`, `methods` 等）は全面禁止。
- **JSX / TSX の全面禁止**: 将来的な Vue 3.6 Vapor モード（仮想DOMに依存しないコンパイル戦略）へのスムーズな移行を見据え、JSX/TSX および `render()` 関数によるコンポーネント記述は全面禁止とする。UIは必ず `<template>` タグを用いたHTMLライクな構文で記述すること。
- **ロジックの排除**: ビジネスロジックや高度なデータ加工は行わず、Props から受け取ったデータをそのまま表示すること（加工は JsonResource 層で行う）。
- **スタイル定義の制限**: `.vue` ファイルの `style` ブロック、およびコンポーネント内要素への `style` 属性（インラインスタイル）の直接指定は全面禁止。
- **リアクティビティの最適化**: `ref()` と `computed()` を適切に使い分けること。高頻度なイベント（マウス移動、キャンバス操作等）が発生する箇所では、オブジェクトの再生成を避け、ガベージコレクション（GC）負荷を最小限に抑えるクリーンなコードを書くこと。
- **Inertia 固有機能の利用**: フォーム送信時は必ず Inertia の `useForm` を使用し、送信状態（`processing`）やエラー（`errors`）を統合管理する。ページ遷移は `Link` コンポーネントを使用し、ルーティングには Wayfinder の生成関数を使用して URL のハードコーディングを禁止する。

## 4.3 ディレクトリ構成（Feature-Sliced Design v2.1）

### 4.3.1 採用方針

本プロジェクトは **Feature-Sliced Design (FSD) v2.1** を採用する。レイヤのルートは
`resources/js/`（一般的な FSD プロジェクトの `src/` に相当）。

**基本は `app` / `pages` / `features` / `shared` の 4 レイヤで構成する。**
`widgets` は禁止ではないが**安易に使わない**（詳細は表の下）。

| レイヤ | 役割 | 本プロジェクトでの扱い |
| --- | --- | --- |
| `app/` | アプリ初期化、Inertia セットアップ、グローバル provider | 採用 |
| `pages/` | ルート（Inertia ページ）単位の合成。ページ固有のロジックはここが持つ | 採用 |
| `widgets/` | 再利用 UI ブロック | **安易に使わない**（まず他レイヤへの振り分けを検討し、妥当な理由があれば使用可） |
| `features/` | 複数ページで実際に再利用されるユーザー操作フロー | 採用 |
| `entities/` | 複数箇所で実際に再利用される業務ドメインモデル | **現時点では未使用**（必要になったら作る） |
| `shared/` | 業務ロジックを持たないインフラ（UI キット、ユーティリティ、API クライアント） | 採用 |

**`widgets` を安易に使わない理由**（FSD v2.1 の指針）:
UI ブロックはデータ取得・状態管理・イベント処理といったユーザーフローのロジックを内包しがちで、
ユーザーフローを扱う `features` と責務が重なり境界が曖昧になるため。
まず次の振り分けを検討し、**どれにも当てはまらない場合に限り** `widgets` を使う。

- 特定画面専用の合成 → その `pages` スライス内
- 複数ページで再利用される操作フロー（UI 込み） → `features`
- 業務文脈を持たない UI → `shared/ui`
- アプリ全体のレイアウト → `shared/ui/layouts`（理由は §4.3.6）

複数の `features` を組み合わせた UI ブロックが必要になった場合も、まずは
上位レイヤ（`pages` / `app`）で合成する方法（§4.3.5-3 の Strategy C）を試す。
それでも解決できない場合は `widgets` を使ってよい。その際は
**なぜ他の振り分けでは解決できなかったかをコードコメントまたは本ドキュメントに残すこと。**
`widgets` を使う場合も、スライス／セグメント構成と public API のルールは他レイヤと同じ。

**`entities` を現時点で使っていない理由**（FSD v2.1 の "Start simple" 方針）:
現状のドメインモデル（見積行・発注行の型）は各ドメインの `features` スライスとその配下の
ページからしか参照されておらず、`features/*/model/` で足りているため。
禁止しているわけではないので、**必要になったら作ってよい。**

作るかどうかの目安（すべて満たす必要はない。判断材料として使う）:

- 同じドメインモデルが複数の `features` やページグループから使われている（「将来使いそう」だけを根拠にしない）。
- それらの利用箇所が必ずしも同時に変更されない。
- 境界が安定していて、責務がひとつに定まっている。

`entities` はほぼ全レイヤから参照できる分、変更の影響範囲が広くなる。
迷う段階なら `features/*/model/` に置いたままにして、参照元が増えてから移しても遅くない。

なお CRUD 操作・認証トークン・ログイン DTO は、ドメインというより通信・認証の都合なので
`entities` ではなく `shared/api` / `shared/auth` に置く。

### 4.3.2 ディレクトリツリー（目標構成）

```
resources/
├── css/
│   └── app.css                     # Tailwind エントリ（app レイヤの styles 相当。§4.3.7 の明示的例外）
└── js/                             # ← FSD レイヤルート
    ├── app/                        # app レイヤ（スライスなし・セグメントのみ）
    │   └── index.ts                # createInertiaApp エントリポイント
    │                               # （グローバル provider が必要になったら app/providers/ を足す。
    │                               #   FSD の方針どおり、空のレイヤ／セグメントは先に作らない）
    │
    ├── pages/                      # pages レイヤ
    │   ├── login/                  # スライス
    │   │   ├── ui/
    │   │   │   └── LoginPage.vue
    │   │   └── index.ts            # public API（default export）
    │   ├── quotation-management/   # スライスグループ（セグメント・public API を持たない）
    │   │   ├── quote-request/
    │   │   │   ├── ui/QuoteRequestPage.vue
    │   │   │   └── index.ts
    │   │   ├── vendor-selection/
    │   │   ├── manager-approval/
    │   │   ├── cancel-request/
    │   │   └── cancel-approval/
    │   └── order-delivery/         # スライスグループ
    │       ├── order-execution/
    │       ├── order-approval/
    │       ├── order-acceptance/
    │       ├── delivery-report-submission/
    │       ├── delivery-approval/
    │       ├── invoice-approval/
    │       ├── order-cancel-request/
    │       └── order-cancel-approval/
    │
    ├── features/                   # features レイヤ（複数ページで実際に再利用される操作フロー）
    │   ├── quotation-flow/         # 見積管理5画面（mode で出し分け）
    │   │   ├── ui/
    │   │   │   ├── QuotationManagementScreen.vue
    │   │   │   └── QuotationProjectCard.vue
    │   │   ├── model/
    │   │   │   ├── quotation.ts            # 行・案件・Inertia props の型
    │   │   │   └── quotation-mode.ts       # 画面モード設定（見積依頼／業者選定／承認…）
    │   │   └── index.ts                    # public API
    │   ├── order-delivery-flow/    # 発注〜納品〜請求8画面（mode で出し分け）
    │   │   ├── ui/
    │   │   │   ├── OrderDeliveryScreen.vue
    │   │   │   └── OrderProjectCard.vue
    │   │   ├── model/
    │   │   │   ├── order-delivery.ts
    │   │   │   └── order-delivery-mode.ts
    │   │   └── index.ts
    │   └── billing/                # 請求（もらい）5画面（mode で出し分け）
    │       ├── ui/
    │       │   ├── BillingScreen.vue
    │       │   ├── BillingProjectCard.vue
    │       │   └── BillingQuotationModal.vue   # ③ 見積作成モーダル
    │       ├── model/
    │       │   ├── billing.ts                  # 行・案件・承認ステータスの型
    │       │   └── billing-mode.ts             # 画面モード設定（見積作成／承認／取消…）
    │       └── index.ts
    │
    └── shared/                     # shared レイヤ（スライスなし・セグメントのみ）
        ├── ui/                     # UI キット（業務ロジックを持たない）
        │   ├── button/ card/ input/ label/ badge/ checkbox/ select/ separator/   # shadcn-vue 生成物
        │   ├── layouts/
        │   │   ├── AppLayout.vue
        │   │   ├── GuestLayout.vue
        │   │   ├── sidebar.ts      # SIDEBAR_COLLAPSED（provide/inject の型付きキー）
        │   │   └── index.ts
        │   ├── toast/              # AppToast.vue + index.ts
        │   ├── flash-messages/     # FlashMessages.vue + index.ts
        │   ├── filter-bar/         # FilterBar.vue      … 2フロー共用の絞り込みフォーム
        │   ├── pager/              # Pager.vue          … 2フロー共用のページャ
        │   └── billing-kind-badge/ # BillingKindBadge.vue … 2フロー共用の区分ラベル
        ├── lib/
        │   ├── cn.ts               # clsx + tailwind-merge
        │   ├── format-money.ts     # yen / percent
        │   └── felix-theme.ts      # useFelixTheme（配色トークン。業務ロジックなし）
        └── api/
            ├── index.ts            # セグメントの public API（下の DTO を再公開）
            ├── pagination.ts       # Pagination
            ├── project-filter.ts   # ProjectFilters（一覧の共通絞り込み条件）
            ├── quotation-message.ts # QuotationChatMessage / QuotationChatFile
            ├── inertia.d.ts        # Inertia 共有 props の型（flash / auth / menuBadges …）
            ├── vue-shims.d.ts      # `*.vue` の型宣言
            ├── actions/            # Wayfinder 自動生成（編集禁止）
            ├── routes/             # Wayfinder 自動生成（編集禁止）
            └── wayfinder/          # Wayfinder 自動生成（編集禁止）
```

### 4.3.3 セグメント

スライス内はセグメント（技術的目的）で分ける。**「何であるか」ではなく「何のためか」で分ける。**

| セグメント | 内容 |
| --- | --- |
| `ui/` | 表示用コンポーネント・表示都合の整形 |
| `model/` | データモデル、型、状態、業務ロジック、バリデーション |
| `api/` | バックエンド連携（リクエスト関数、通信用の型、マッパ） |
| `lib/` | そのスライス内部で使う補助コード |
| `config/` | 設定値・フィーチャーフラグ |

`app` と `shared` はスライスを持たず、直下がセグメントになる（セグメント同士の相互参照は可）。

**ファイル名はドメイン名で付ける（desegmentation の禁止）。**

```
❌ model/types.ts        どのドメインの型か分からず、無関係な型が混ざる
❌ lib/utils.ts          何のためのユーティリティか分からない
❌ lib/helpers.ts

✅ model/quotation.ts        見積の型 + ロジック
✅ model/order-delivery.ts   発注納品の型 + ロジック
✅ lib/format-money.ts       金額整形
```

> **`FilterBar` / `Pager` / `BillingKindBadge` が `shared/ui` にある理由**
> この3部品は見積管理フローと発注納品フローの両方から使われていた。features 同士の
> 相互 import は禁止（§4.3.5）なので、cross-import 解決の Strategy B（下位レイヤへ押し下げ）で
> `shared/ui` に降ろしている。いずれも業務判断を持たず、props を表示して emit するだけの部品。
> 同じ理由で、両フローが使う `Pagination` / `ProjectFilters` / `QuotationChatMessage` は
> `shared/api` に置いている。

### 4.3.4 コード配置の判断フロー

新しいコードを書くときは上から順に判定する。

1. **どこで使われるか？**
   1 ページでしか使わない → そのページスライス内に置く。
   2 ページ以上で使うが重複を許容できる → 各ページに複製したままでもよい。
2. **業務ロジックを持たない再利用インフラか？**
   UI 部品 → `shared/ui/` ／ ユーティリティ → `shared/lib/` ／ API クライアント・ルート定数・CRUD → `shared/api/`
3. **複数ページで現に使われており、境界が安定したユーザー操作フローか？**
   Yes → `features/` ／ 単一利用・推測での再利用 → ページに残す
4. **アプリ全体の設定か？** → `app/`

> **原則: 迷ったら `pages/` に置く。** 抽出は「現に複数箇所で使われていて、境界が明確なとき」だけ行う。
> ページ内に大きな UI ブロック・フォーム・バリデーション・データ取得・業務ロジックが残っていても、それは FSD 的に正しい状態である。

**アンチパターン**

- 使用箇所が 1 つしかない段階で `features` / `entities` に切り出す（まずページに置いておく）。
- 振り分けを検討せずに `widgets` へ逃がす（まず `pages` / `features` / `shared` を検討する）。
- `shared/` に業務ロジック（金額計算、承認判定などのドメイン規則）を置く。
- 責務が広すぎる god slice を作る（例: `quotation-management/` に全部入れる → 操作単位に分割する）。
- トップレベルに `assets/` を作る。静的アセットは使う場所の隣、共有するものは `shared/ui/` に置く。

### 4.3.5 インポート規則（MUST）

**1. 下位レイヤからのみ import できる。**

```
app → pages → widgets → features → shared
```

> 本プロジェクトは通常 `widgets` を挟まないため、実質は `app → pages → features → shared`。
> `widgets` を使う場合はこの位置（`pages` の下・`features` の上）に入る。

上位レイヤへの import、および**同一レイヤのスライス間の相互 import は禁止**。

```ts
// ✅ OK
import { Button } from '@/shared/ui/button';                     // features → shared
import { QuotationManagementScreen } from '@/features/quotation-flow'; // pages → features

// ❌ 違反
import { QuotationRow } from '@/pages/quotation-management/quote-request'; // features → pages（上位）
import { OrderDeliveryScreen } from '@/features/order-delivery-flow';      // features → features（同一レイヤ）
```

**2. スライスの外部公開は `index.ts`（public API）経由のみ。**

```ts
// ✅ OK
import { QuotationManagementScreen } from '@/features/quotation-flow';

// ❌ 違反（内部ファイルへの直接 import）
import QuotationManagementScreen from '@/features/quotation-flow/ui/QuotationManagementScreen.vue';
```

`shared` はスライスを持たないため、**セグメント単位で public API を定義する**。`shared/index.ts` は作らない。

- `shared/api/index.ts` … 共通 DTO（`Pagination` / `ProjectFilters` / `QuotationChatMessage` …）を再公開する。
  利用側は `@/shared/api` から import する（`@/shared/api/pagination` のような内部ファイル直参照は Steiger が弾く）。
- `shared/ui/<component>/index.ts` … UI 部品ごとの公開口（`@/shared/ui/button`, `@/shared/ui/filter-bar` …）。
- `shared/lib/*.ts` … 1ファイル1目的なのでファイル自体が公開口（`@/shared/lib/cn` など）。

**3. 同一レイヤの cross-import が必要になったら、import を書く前に次の順で解決する。**

- **A: スライス統合** — 常に同時に変更されるなら 1 スライスに統合する。
- **B: 下位レイヤへ押し下げ** — 共通するのがドメインロジックなら `shared`（ドメインモデルそのものなら `entities` を作って）へ移す。
  例: `useQuotationTheme` は見積・発注の両フローから使われていたため `shared/lib/felix-theme.ts` に降ろす。
- **C: 上位レイヤで合成（IoC）** — 親（`pages` / `app`）が両方を import し、slot で繋ぐ。
- **D: public API 経由のみ許可** — 再利用が避けられない場合に限り `index.ts` 経由で許す。内部ファイルには絶対に触らない。

`@x` 記法は `entities` レイヤ専用。現時点では `entities` を使っていないため出番はない。
将来 `entities` を作って cross-import が必要になった場合も、まずは境界の統合（A）を検討し、
`@x` は他に手段がないときの最終手段として、理由を残したうえで使う。

**4. 明文化された例外**（意図的な設計判断として記録する）

- **Wayfinder 生成物**（`shared/api/actions|routes|wayfinder`）は自動生成のため public API へ集約せず、生成パスを直接 import する。
- **shadcn-vue 生成物**（`shared/ui/<component>/`）は各コンポーネントディレクトリの `index.ts` を public API とする。

### 4.3.6 レイアウトの置き場所

`AppLayout.vue` / `GuestLayout.vue` は **`shared/ui/layouts/` に置く**。

- FSD の一般則ではアプリ全体のレイアウトは `app` に置けるが、`app` は `pages` の上位レイヤであり、
  **ページからレイアウトを import できなくなる**（上位への import は禁止）。
- 本プロジェクトのレイアウトはナビゲーション枠のみで業務文脈を持たないため、
  FSD が認める「業務文脈のない再利用可能なレイアウト UI は `shared/ui` に置ける」に該当する。
- 同じ理由で `FlashMessages.vue` / `AppToast.vue` も `shared/ui/` に置く。Inertia の共有 props を
  読むだけで業務ロジックを持たず、`shared` に許される「アプリ事情を知るコード」の範囲に収まる。

`AppLayout` はサイドバーの開閉状態を `provide` し、画面側が `inject` して固定ヘッダーの左余白を合わせる。
文字列キーだと型が付かず綴り違いにも気付けないため、**型付きの `InjectionKey` を
`shared/ui/layouts`（`sidebar.ts`）から公開し、provide/inject の両側でそれを使う**。

**レイアウトの適用は `pages` レイヤで行う。`features` の中でレイアウトを巻かない。**
（features がレイアウトを内包すると、ページ側で差し替えられずレイヤの合成が崩れるため。cross-import 解決の Strategy C。）

```vue
<!-- pages/quotation-management/quote-request/ui/QuoteRequestPage.vue -->
<script setup lang="ts">
import { AppLayout } from '@/shared/ui/layouts';
import { QuotationManagementScreen } from '@/features/quotation-flow';
import type { QuotationPageProps } from '@/features/quotation-flow';

defineOptions({ layout: AppLayout });   // Inertia の永続レイアウト
const props = defineProps<QuotationPageProps>();
</script>

<template>
    <QuotationManagementScreen v-bind="props" mode="quote-request" />
</template>
```

### 4.3.7 Laravel 都合による明示的な逸脱

FSD から外れるが、Laravel / Vite の規約を優先する箇所。**意図的な判断としてここに記録する。**

| 項目 | FSD の一般則 | 本プロジェクト | 理由 |
| --- | --- | --- | --- |
| グローバル CSS | `app/styles/` | `resources/css/app.css` | Laravel の `@vite()` エントリ規約に合わせる。役割は app レイヤの styles セグメント。 |
| レイヤルート | `src/` | `resources/js/` | Laravel の標準配置。 |
| パスエイリアス | `@/<layer>/*` を個別定義 | `@/*` → `resources/js/*` の 1 本 | レイヤが `resources/js/` 直下にあるため、単一エイリアスで `@/shared/...` `@/features/...` が成立する。 |

### 4.3.8 Inertia ページ名とページスライスの対応

**Inertia の `Inertia::render()` に渡すページ名 = `pages/` 配下のスライスパス（kebab-case）** とする。

| Inertia ページ名 | ファイル |
| --- | --- |
| `login` | `pages/login/index.ts` → `ui/LoginPage.vue` |
| `quotation-management/quote-request` | `pages/quotation-management/quote-request/index.ts` |
| `order-delivery/order-approval` | `pages/order-delivery/order-approval/index.ts` |

`quotation-management/` `order-delivery/` は **スライスグループ**（ナビゲーション目的のまとめ役）であり、
セグメントも public API も持たない。実体のスライスはその 1 階層下。

ページスライスの public API は、Inertia の解決仕様に合わせて **default export** で公開する。

```ts
// pages/quotation-management/quote-request/index.ts
export { default } from './ui/QuoteRequestPage.vue';
export type { QuoteRequestPageProps } from './model/quote-request.ts';
```

### 4.3.9 移行マッピング（旧構成 → 現構成）

移行は完了済み。旧パスを探すときの対応表として残す。

| 旧 | 現 |
| --- | --- |
| `app.ts` | `app/index.ts` |
| `pages/Auth/Login.vue` | `pages/login/ui/LoginPage.vue` |
| `pages/QuotationManagement/*.vue`（5件） | `pages/quotation-management/<kebab>/ui/<Pascal>Page.vue` |
| `pages/OrderDelivery/*.vue`（8件） | `pages/order-delivery/<kebab>/ui/<Pascal>Page.vue` |
| `components/quotation-management/QuotationManagementScreen.vue` / `QuotationProjectCard.vue` | `features/quotation-flow/ui/` |
| `components/order-delivery/OrderDeliveryScreen.vue` / `OrderProjectCard.vue` | `features/order-delivery-flow/ui/` |
| `components/quotation-management/QuotationFilterBar.vue` | `shared/ui/filter-bar/FilterBar.vue`（2フロー共用のため降ろした） |
| `components/quotation-management/QuotationPager.vue` | `shared/ui/pager/Pager.vue`（同上） |
| `components/quotation-management/BillingKindBadge.vue` | `shared/ui/billing-kind-badge/BillingKindBadge.vue`（同上） |
| `types/quotation-management.ts` | `features/quotation-flow/model/quotation.ts` |
| `types/order-delivery.ts` | `features/order-delivery-flow/model/order-delivery.ts` |
| ↑ のうち `QuotationChatMessage` / `QuotationChatFile` | `shared/api/quotation-message.ts`（2フロー共用） |
| ↑ のうち `*Pagination`（2つとも同一形） | `shared/api/pagination.ts` の `Pagination`（各 model が別名で再公開） |
| ↑ のうち絞り込みの共通3項目 | `shared/api/project-filter.ts` の `ProjectFilters`（各 model が `extends` して画面固有条件を足す） |
| `lib/quotation-management.ts` | `features/quotation-flow/model/quotation-mode.ts` |
| `lib/order-delivery.ts` | `features/order-delivery-flow/model/order-delivery-mode.ts` |
| `composables/useQuotationTheme.ts` | `shared/lib/felix-theme.ts` の `useFelixTheme`（2フロー共用のため降ろした） |
| `components/ui/*`（shadcn-vue） | `shared/ui/*` |
| `components/feedback/FlashMessages.vue` / `AppToast.vue` | `shared/ui/flash-messages/` / `shared/ui/toast/` |
| `layouts/AppLayout.vue` / `GuestLayout.vue` | `shared/ui/layouts/` |
| `lib/utils.ts`（`cn`） | `shared/lib/cn.ts` |
| `lib/format.ts`（`yen` / `percent`） | `shared/lib/format-money.ts` |
| `types/shims.d.ts` | `shared/api/vue-shims.d.ts`（`*.vue`）と `shared/api/inertia.d.ts`（共有 props）に分割 |
| `types/index.ts` | 削除（参照元なし。ステータス管理画面の型は実装時に該当スライスへ置き直す） |
| `actions/` `routes/` `wayfinder/` | `shared/api/actions|routes|wayfinder`（Wayfinder の `path` オプションで出力先を変更） |
| `composables/` | 廃止。`use` 系は所属スライスの `lib/` または `shared/lib/` に置く |

Inertia のページ名も同時に kebab-case へ変更済み（`app/Http/Controllers/**`、全14画面）。

## 4.4 エントリポイント（Inertia セットアップ）

`resources/js/app/index.ts`:

```ts
import '../../css/app.css';

import { createApp, h, type DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    // Inertia ページ名 = pages スライスのパス。public API（index.ts）を解決する。
    resolve: (name) =>
        resolvePageComponent(
            `../pages/${name}/index.ts`,
            import.meta.glob<DefineComponent>('../pages/**/index.ts'),
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
```

- Blade 側のエントリも合わせて更新する: `@vite(['resources/css/app.css', 'resources/js/app/index.ts'])`。
- `import.meta.glob('../pages/**/index.ts')` はセグメント内の `index.ts` も拾うため、
  **`pages` スライスのセグメント配下に `index.ts` を作らない**（public API はスライス直下の 1 枚のみ）。

## 4.5 フォーム / 状態管理

- サーバへの送信は Inertia の **`useForm`** を用いる。`fetch`/`axios` の直接利用は原則禁止（ファイルダウンロード等の例外を除く）。
- 送信先 URL・HTTP メソッドは **Wayfinder の生成アクション** から取得し、文字列ハードコードを避ける。

```ts
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { store } from '@/shared/api/actions/App/Http/Controllers/Quotation/QuoteRequestController'

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
- 再利用するロジック（`useXxx`）は、**そのスライス内でしか使わないなら `<slice>/lib/`、レイヤ横断で使うなら `shared/lib/`** に置く。トップレベルの `composables/` は作らない。

### フラッシュメッセージ（成功 / エラー通知）

- サーバ側の処理結果は **Inertia 共有プロパティ `flash`**（`{ success, error }`）で配る。Controller は `back()->with('success', ...)` / `->with('error', ...)` で積み、`HandleInertiaRequests::share()` が `flash` として公開する。型は `shared/api/inertia.ts` に定義する。
- 表示は `shared/ui/` の常設コンポーネントが担う（配置理由は §4.3.6）。
    - `shared/ui/flash-messages/FlashMessages.vue` … `usePage().props.flash` を購読し、画面右上にトーストを積んで自動消去する。`AppLayout.vue` に常設し全画面で共用する。
    - `shared/ui/toast/AppToast.vue` … 1件分のトースト（`variant: 'success' | 'error'`）。表示専用。
- フォーム成功時に「メッセージ表示＋一覧の再読込」を行いたい場合は、Controller で `back()` を返す（Inertia が同一ページを再取得＝リロード）。クライアントで個別に再取得処理を書かない。
- **`flash` は表示したら消費する（`FlashMessages.vue` が `flash.success` / `flash.error` を `null` に落とす）。**
  トーストを出す側で「表示済みかどうか」を持たない。理由は2つある。
    - Vue の `deep: true` watcher は**値が変化していなくてもトリガのたびにコールバックが走る**。
    - Inertia の部分リロードは**返却されなかったプロップを前回値のまま引き継ぐ**（`{ ...現在のprops, ...レスポンスのprops }`）。
      → 消費しないと、無関係な再取得のたびに前回の成功メッセージが再表示される。
- **部分リロード（`only` 指定）を書くときは `only` に `'flash'` を必ず含める。**
  含めないと上記の引き継ぎで古い `flash` が残り続ける。消費（上記）との二重防御として両方守る。

```ts
// 例：iframe を閉じた後の一覧再取得（features/quotation-flow/ui/QuotationManagementScreen.vue）
router.reload({ only: ['projects', 'pagination', 'flash'] });
```

## 4.6 ルーティング（Laravel Wayfinder）

- ルート/コントローラアクションへの参照は **Wayfinder が生成する型付き関数**を使用する。
- 生成は Vite プラグイン `@laravel/vite-plugin-wayfinder`（開発環境）で行う。
- 生成物は FSD 上「業務ロジックを持たないバックエンド連携」なので **`shared/api/` 配下に出力する**。
  プラグインの `path` オプション（`php artisan wayfinder:generate --path=` に渡る）で出力先を指定する。

```ts
wayfinder({
    formVariants: true,
    path: 'resources/js/shared/api',   // → shared/api/{actions,routes,wayfinder}
})
```

- 生成物（`shared/api/actions` `shared/api/routes` `shared/api/wayfinder`）は **自動生成のため手で編集しない**。
- インポートエイリアスは `@/` = `resources/js/`（`vite.config` / `tsconfig` で設定済み）。
  レイヤが `resources/js/` 直下にあるため、`@/shared/...` `@/features/...` `@/pages/...` の形で
  FSD の `@/<layer>/*` 規約を満たす。

## 4.7 UI / スタイリング

- **Tailwind CSS 4** を使用。`@tailwindcss/vite` プラグイン経由でビルドする。
- UI プリミティブは **shadcn-vue**（内部で **reka-ui** を利用）を採用し、**`shared/ui/` に置く**。新規 UI は原則 shadcn-vue の生成物をベースに構築する。
  - `components.json` を導入する場合、`aliases.components` / `aliases.ui` は `@/shared/ui`、`aliases.utils` は `@/shared/lib/cn` を指すこと。
- クラス結合は `shared/lib/cn.ts` の `cn()`（`clsx` + `tailwind-merge`）を使用する。
- 配色トークン（FELIX ブランドのグラス表現など）は `shared/lib/felix-theme.ts` の `useFelixTheme()` を使う。業務ロジックは持たせない。
- アイコンは `lucide-vue-next` を使用する。
- 静的アセット（画像・アイコン・フォント）は**使うコードの隣に置く**。複数スライスで共有するものだけ `shared/ui/` に置く。トップレベル `assets/` は作らない。

## 4.8 日付処理

- 日付・時刻の整形/演算は **dayjs** を使用する。素の `Date` 操作や `moment` は使用しない。
- 整形関数はレイヤ横断で使うため `shared/lib/` にドメイン名のファイルで置く（金額は `format-money.ts`。
  日付整形を切り出すときは `format-date.ts`。`lib/utils.ts` のような技術的役割名は使わない）。

## 4.9 数値・金額の扱い（計算はサーバ側）

- **小数点が絡む計算（金額・単価・数量・税額・率・按分など）をフロントで行わない。**
  JavaScript の `number` は IEEE 754 の倍精度浮動小数のため、`0.1 + 0.2 !== 0.3` のように
  10 進小数を正確に表現できず、サーバ側の確定値と 1 円ずれる。
- 計算はすべてサーバ側で **BCMath** により確定させ（[`backend.md`](backend.md) §5.5）、
  フロントは props で受け取った確定値を**表示するだけ**にする
  （「ロジックの排除」= [`ai-architecture-instructions.md`](ai-architecture-instructions.md) §4.1）。
- したがって、`shared/lib/format-money.ts` などの整形関数は**整形のみ**を担い、
  加算・乗算・丸め（`Math.round` / `toFixed` による金額の再計算）を行わない。
- 小数を含む値は props で**文字列**として受け取ってよい（`number` へ変換した時点で誤差が確定するため）。
  表示時は `Intl.NumberFormat` / `toLocaleString` で桁区切りするに留める。
- 入力フォームでの小計プレビュー等、どうしても即時計算が必要な場合は
  **表示上の目安**であることを明示し、確定値は必ずサーバのレスポンスで上書きする。

## 4.10 ビルド設定（Vite）

`vite.config.ts` の要点:

- プラグイン: `laravel-vite-plugin` / `@vitejs/plugin-vue` / `@tailwindcss/vite` / `@laravel/vite-plugin-wayfinder`
- エントリ入力: `resources/css/app.css`, `resources/js/app/index.ts`
- エイリアス: `@` → `resources/js`（= FSD レイヤルート）
- Wayfinder の出力先: `resources/js/shared/api`

```ts
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import { wayfinder } from '@laravel/vite-plugin-wayfinder'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  resolve: {
    alias: { '@': fileURLToPath(new URL('./resources/js', import.meta.url)) },
  },
  plugins: [
    laravel({ input: ['resources/css/app.css', 'resources/js/app/index.ts'], refresh: true }),
    vue(),
    tailwindcss(),
    wayfinder({ formVariants: true, path: 'resources/js/shared/api' }),
  ],
})
```

> `server` セクション（Docker 向けの host / cors / HMR 設定）は現行のまま維持する。

## 4.11 TypeScript 設定

- `tsconfig.json` を使用する（`jsconfig.json` は廃止済み）。
- `paths`: `@/*` → `resources/js/*`。レイヤが直下にあるため FSD の `@/<layer>/*` 規約を満たす（§4.3.7）。
- `strict: true` を有効にする。

## 4.12 命名規約

| 対象 | 規約 | 例 |
| --- | --- | --- |
| レイヤ | FSD 既定の名前のみ | `app` / `pages` / `features` / `shared`（必要なら `widgets`） |
| スライス | kebab-case | `features/quotation-flow/`, `pages/quote-request/` |
| スライスグループ | kebab-case（セグメント・public API を持たない） | `pages/quotation-management/` |
| セグメント | FSD 既定の名前 | `ui` / `model` / `api` / `lib` / `config` |
| ページコンポーネント | PascalCase + `Page` 接尾辞 | `pages/login/ui/LoginPage.vue` |
| 部品コンポーネント | PascalCase | `features/quotation-flow/ui/QuotationPager.vue` |
| composable | camelCase + `use` 接頭辞。ファイル名はドメイン名 | `shared/lib/felix-theme.ts` の `useFelixTheme` |
| 型 | PascalCase。ドメイン名のファイルに置く | `features/quotation-flow/model/quotation.ts` の `QuotationRow` |
| 関数ファイル | ドメイン/目的名の kebab-case | `shared/lib/format-money.ts` |
| 禁止するファイル名 | 技術的役割名 | `types.ts` / `utils.ts` / `helpers.ts` / `constants.ts` |

## 4.13 FSD 移行の完了状況

**FSD 構造への移行（完了）**

- [x] `resources/js/` 直下を `app/` `pages/` `features/` `shared/` の4レイヤに再編（`widgets` / `entities` は未使用）
- [x] `app.ts` → `app/index.ts`（`resolve` を public API 解決方式へ変更）
- [x] Blade の `@vite` エントリを `resources/js/app/index.ts` に変更
- [x] `vite.config.ts` の `laravel({ input })` を更新、`wayfinder({ path: 'resources/js/shared/api' })` を設定
- [x] `components/ui/` → `shared/ui/`、`layouts/` → `shared/ui/layouts/`、`components/feedback/` → `shared/ui/`
- [x] `lib/utils.ts` → `shared/lib/cn.ts`、`lib/format.ts` → `shared/lib/format-money.ts`
- [x] `composables/useQuotationTheme.ts` → `shared/lib/felix-theme.ts`（`useFelixTheme` へ改名）
- [x] 見積管理・発注納品の各コンポーネント／型／モード設定を `features/quotation-flow` `features/order-delivery-flow` へ集約
- [x] features 間の cross-import を解消（共用の表示部品を `shared/ui`、共用 DTO を `shared/api` へ降ろした）
- [x] 各 `features` スライスに `index.ts`（public API）を作成し、内部ファイルへの直接 import を解消
- [x] `pages/` を kebab-case のスライス構成へ再編し、各スライスに default export の `index.ts` を作成
- [x] Controller の `Inertia::render()` 文字列を kebab-case のスライスパスへ変更（全14画面）
- [x] `features` 内の `AppLayout` 参照を撤去し、各ページの `defineOptions({ layout: AppLayout })` へ移した
- [x] サイドバー開閉の provide/inject を型付き `InjectionKey`（`shared/ui/layouts/sidebar.ts`）へ変更
- [x] 参照のない `types/index.ts` を削除し、`types/` `components/` `layouts/` `composables/` `lib/` を廃止
- [x] `npx steiger resources/js` … 違反ゼロ
- [x] `npx vue-tsc --noEmit` … 移行前と同じ既存エラー4件のみ（新規エラーなし）
- [x] `npx vite build` … 成功（14ページが個別チャンクに分割される）

**Inertia / ツールチェーン**

- [x] `@inertiajs/vue3`, `dayjs`, `typescript` 等の npm パッケージ追加
- [x] `laravel/wayfinder`（composer）, `@laravel/vite-plugin-wayfinder`（npm）追加
- [x] `jsconfig.json` → `tsconfig.json`（`strict: true`）
- [x] `vite.config.js` → `vite.config.ts`（wayfinder プラグイン追加）
- [x] 既存 Vue 部品を `<script setup lang="ts">` へ移行
- [x] Blade 直接マウント構成を Inertia ページ構成へ移行
- [x] `steiger` / `@feature-sliced/steiger-plugin` を devDependencies に追加（`npm run lint:fsd`）
- [x] `@types/node` を devDependencies に明示（tsconfig の `types` が依存しているのに未宣言だった）

## 4.14 アーキテクチャ検証（Steiger）

FSD 違反は目視ではなく linter で検出する。

```bash
npm run lint:fsd      # = steiger resources/js
```

設定は `steiger.config.ts`。レイヤルートは `resources/js`、Wayfinder の自動生成物は検査対象外にしている。

```ts
import fsd from '@feature-sliced/steiger-plugin';
import { defineConfig } from 'steiger';

export default defineConfig([
    ...fsd.configs.recommended,
    { ignores: ['**/shared/api/actions/**', '**/shared/api/routes/**', '**/shared/api/wayfinder/**'] },
]);
```

主なルール:

- `insignificant-slice` … 1 ページからしか使われていない `features` / `entities` を、そのページへ統合するよう促す。
- `excessive-slicing` … 1 レイヤのスライスが多すぎる場合に統合・グルーピングを促す。
- `forbidden-imports` … レイヤ順序違反・同一レイヤの cross-import を検出する。
- `public-api` … `index.ts` を経由しない内部 import を検出する。

**ルールを破る場合は、意図的な設計判断としてコード中のコメントまたは本ドキュメント（§4.3.5-4 / §4.3.7）に理由を残す。**

# 実行予算「一覧」画面 実装まとめ（一覧画面のみ）

> スコープを **実行予算一覧画面のみ** に限定した実装計画。
> 詳細/明細の編集・承認・依頼など操作系の全体移植は [`execution-budget-migration-plan.md`](./execution-budget-migration-plan.md) を参照（本書はその一覧画面部分の抜き出し＋具体化）。
>
> 対象画面: `GET /execution-budgets`（`resources/js/pages/ExecutionBudget/Index.vue` + `components/BudgetTable.vue`）
> レガシ対応元: `resources/views/admin/new-layouts/estimate_list.blade.php` / `NewEstimateCustomEditController@list,refresh`
>
> 方針: 機能追加と**リファクタリングを同時**に進める（§5）。準拠は `docs/architecture/**`（Controller薄 / FormRequest / Repository / Resource / Wayfinder / `useForm` / Tailwindのみ）。

---

## 1. スコープ

### 含む（一覧画面でやること）
- 一覧の**検索・フィルタの拡充**（レガシ一覧と同等に）
- **並べ替え**の拡充（日付カラム指定 + 昇順/降順）
- 一覧テーブルの**2段グループヘッダー**対応
- **行ごとの再計算（refresh）**操作
- **仮実行予算**へのリンク
- 上記に伴う一覧周辺コードの**リファクタリング**

### 含まない（別計画 / 詳細画面側）
- 明細のインライン編集・承認・依頼・選定・ファイル・工期・PJ編集・発注（= 詳細/明細画面の機能）
- セルクリックで個別項目を編集するモーダル（レガシの iframe モーダルは詳細編集に属するため本書では「詳細画面へ遷移」に置換）
- 集計画面（`management`）・CSV・チェック一覧・ガント（一覧画面とは別画面）

---

## 2. 現状（実装済み）

| 機能 | 状態 | 実装箇所 |
| --- | --- | --- |
| 列表示（旧 estimate_list 全列踏襲・34列） | ✅ | `BudgetTable.vue` L37-74 |
| ID 検索（複数可） | ✅ | `Index.vue` + `SearchBudgetRequest` + `EstimateRepository@paginateBudgets` |
| 物件名キーワード検索 | ✅ | 同上 |
| 引渡月の範囲（monthFrom/monthTo） | ✅ | 同上（※ `delivery_date` 固定） |
| 並べ替え（id_desc / handover_asc の2択） | ✅ | 同上 |
| ページネーション（前へ/次へ・件数表示・クエリ保持） | ✅ | `Index.vue` L159-172 |
| 物件名クリックで詳細を新規タブ表示 | ✅ | `BudgetTable.vue` L164-181（`/estimates/{id}`） |
| 「仮実行予算」バッジ表示 | ✅（バッジのみ・リンクなし） | `BudgetTable.vue` L178 |
| 対象物件の絞り込み `name LIKE '●%'` | ✅ | `EstimateRepository@paginateBudgets` |

---

## 3. レガシ一覧との差分（= 実装する項目）

| # | 機能 | レガシ仕様 | 新の現状 | 対応 |
| --- | --- | --- | --- | --- |
| A | **日付カラム選択フィルタ** | `search_day_column` で6種の日付列から選び、その列に月度範囲を適用（複数カラムOR可） | `delivery_date` 固定 | 追加 |
| B | **並べ替えの拡充** | 選択した日付列を 新しい順(DESC)/古い順(ASC) | id_desc / handover_asc の2択のみ | 追加 |
| C | **JSONフィルタ（DL名・銀行名）** | `estimates.json->dealer_name` / `->owner_bank_name` で絞り込み | なし | 追加 |
| D | **フラグフィルタ** | `sail_start_flg` / `owner_flg`（JSON）。`on_sale_flg` はレガシでも未実装 | なし | 追加（on_sale_flg は仕様確認） |
| E | **行ごとの再計算（refresh）** | 再計算アイコン → `command:calculation_estimate` 実行 → 当該行を再描画 | なし | 追加 |
| F | **仮実行予算リンク** | バッジから仮実行予算の帳票/画面へ | バッジのみ | 追加（遷移先を決定） |
| G | **2段グループヘッダー** | 「土地建物PJ融資」「販売金融機関詳細」「ディーラー販売手数料」を colspan で束ねる | フラットな1段 | 追加（視認性向上） |
| H | 件数表示 | タイトルに `(総件数)` | 「該当 N件」バッジあり | 維持（差分なし） |

> 日付カラム選択肢（`config('constant.estimate_date_column')`）:
> 仕入契約日 `purchase_contract_date` / 仕入決済日 `purchase_settlement_date` / 販売掲載日 `sail_start_date` / 販売買付申込入金日 `sales_contract_payment_date` / 販売契約日 `sales_contract_date` / 販売引渡日 `delivery_date`

---

## 4. 実装タスク（一覧画面のみ）

### 4-1. バックエンド

#### `SearchBudgetRequest`（検索条件の拡張）
- [ ] ルール追加: `dateColumn`（in: 上記6種）、`sort`（`asc`/`desc` または `{column}_{dir}` 形式に再設計）、`dealerName`、`ownerBankName`、`sailStartFlg`、`ownerFlg`、`onSaleFlg`
- [ ] `filters()` / `filtersForView()` を拡張（sticky 値の往復）
- [ ] 🔧 リファクタ: フィルタキーの正規化（空→null）ロジックを1箇所に集約

#### `EstimateRepository@paginateBudgets`（クエリ拡張）
- [ ] `dateColumn` 指定時: `estimate_aggregates.column = :dateColumn` に対して `monthFrom`〜`monthTo` を適用（未指定時は現行どおり `delivery_date`）
- [ ] 並べ替え: 指定日付列で ASC/DESC（指定なしは id DESC）
- [ ] JSONフィルタ: `estimates.json->dealer_name` / `->owner_bank_name`、`->sail_start_flg` / `->owner_flg`
- [ ] 🔧 リファクタ: 検索条件の適用を `applyListFilters(Builder, array)` に分離し、`paginateBudgets` を薄く保つ。日付列のホワイトリストは `Support`/config 定数で一元管理（文字列直書き禁止）

#### 再計算（refresh）
- [ ] ルート: `POST /execution-budgets/{estimate}/recalculate`（`EstimateRecalculateController@update`）
- [ ] `RecalculateEstimateAction`: レガシ `Artisan::call('command:calculation_estimate', ['id' => $id])` 相当を実行（コマンドの移植可否を確認。なければ集計再計算ロジックを Action 化）
- [ ] レスポンス: 当該 estimate の `ExecutionBudgetResource` を返す（行だけ差し替え用）
- [ ] 🔧 リファクタ: 集計の取得元（`estimate_aggregates`）の読み出しを Resource/Repository で共通化

#### 設定・定数
- [ ] 日付カラム選択肢・並べ替え選択肢を `config` または `Support` 定数として定義（レガシ `config/constant.php` の `estimate_date_column` / `estimate_date_column_sort` を移植）
- [ ] フロントへは Controller から `dateColumnOptions` / `sortOptions` として渡す（既に `sortOptions` あり → 拡張）

### 4-2. フロント（`ExecutionBudget/Index.vue`）

- [ ] フィルタ UI 追加:
  - [ ] 日付カラム選択（Select、6種）
  - [ ] 月度範囲（既存）＋ 日付カラム未選択時は月度範囲を無効化（レガシの表示/非表示挙動を踏襲）
  - [ ] 並べ替え（新しい順/古い順、選択日付列に連動）
  - [ ] DL名 / 銀行名（Input）
  - [ ] 各フラグ（Switch/Checkbox。`on_sale_flg` は仕様確認のうえ）
- [ ] すべて `useForm` + Wayfinder ルート関数で送信（URLハードコード禁止、fetch/axios 禁止）
- [ ] 月度開始>終了の自動補正（レガシ L228-260 のUX踏襲）
- [ ] リセット動作に新フィルタを反映
- [ ] 🔧 リファクタ: フィルタフォームを `components/estimate/BudgetFilters.vue` に切り出し、`Index.vue` を薄く保つ

### 4-3. フロント（`BudgetTable.vue`）

- [ ] **2段グループヘッダー**対応（「土地建物PJ融資」colspan3 /「販売金融機関詳細」colspan4 /「ディーラー販売手数料」colspan3）。列名は全て中央揃え（既存ルール踏襲）、背景色は現状維持（一覧は `bg-primary`）
- [ ] **行の再計算ボタン**（物件名セルにアイコン）→ `useForm().post(recalculate.url(id))`、`preserveScroll`、成功で該当行のみ更新＋トースト
- [ ] **仮実行予算リンク**（バッジをリンク化、遷移先は §6 で決定）
- [ ] 🔧 リファクタ: 列定義（`columns`）の `header`/`group`/`align`/`width` をデータ駆動化し、グループヘッダーも同じ定義から生成（ヘッダー手書きの重複を排除）。`yen()` 等は `lib/format.ts` へ集約

### 4-4. ルーティング（Wayfinder）

- [ ] `routes/web.php`:
  - `POST /execution-budgets/{estimate}/recalculate` → `EstimateRecalculateController@update`（`whereNumber`）
- [ ] Wayfinder 生成関数を `resources/js/routes/**` から利用

---

## 5. リファクタリング方針（一覧画面分・本書での適用）

- **触る範囲を一段きれいに**: 一覧で触る `Index.vue` / `BudgetTable.vue` / `SearchBudgetRequest` / `EstimateRepository` を、機能追加と同時に整理（フィルタ部品の分離、列定義のデータ駆動化、フォーマッタ集約、検索条件適用の分離）。
- **コミットを分ける**: 「振る舞いを変えない整理」と「フィルタ追加・再計算追加」を別コミット。
- **テストで保護**: 着手前に一覧の Feature テスト（検索・並べ替え・ページネーション）を用意し、リファクタ前後でグリーン維持。
- **やり過ぎ防止**: 詳細/明細側のコードには触らない（本書スコープ外）。`on_sale_flg` のように仕様が曖昧なものは確定してから実装。

---

## 6. 要確認事項（一覧画面）

- [ ] **仮実行予算リンクの遷移先**: レガシは `print-tmp-budget`（帳票）。新側は (a) レガシ帳票へ外部リンク / (b) 新詳細へ / (c) 当面バッジのみ、のどれか。
- [ ] **再計算コマンドの移植**: レガシ `command:calculation_estimate` を新側に移植するか、集計再計算を Action として実装するか。`estimate_aggregates` の更新主体をどちらに置くか（一覧の数字の正の源泉）。
- [ ] **`on_sale_flg`**: レガシでも未実装。新で実装するか除外するか。
- [ ] **ページネーション UX**: レガシは AJAX 追記（無限スクロール）、新は 前へ/次へ。新方式を維持でよいか。
- [ ] **対象絞り込み `name LIKE '●%'`**: 一覧の対象母集合の条件をこのまま維持するか。

---

## 7. 完了の定義（一覧画面）

- レガシ実行予算一覧の**検索・フィルタ・並べ替え**が新一覧で同等にできる（日付カラム選択・月度範囲・DL名/銀行名/フラグ）。
- 行ごとの**再計算**ができ、当該行の数字が即時更新される。
- **仮実行予算**への導線がある。
- 列が**2段グループヘッダー**で表示され、可読性がレガシ同等以上。
- 触った一覧周辺コードがリファクタされ、列定義・フィルタ・フォーマッタの重複が解消されている。
- 一覧の Feature テストがグリーン。

# 実行予算（Execution Budget）機能 全面移植 計画書

> レガシ `felix_total`（Laravel 8 + Blade + jQuery）の実行予算画面が持つ **全機能** を、
> `new_felix_total`（Laravel 13 + Vue 3 / Inertia + TypeScript + shadcn-vue）の実行予算へ移植するための計画。
>
> 対象: `new_felix_total` の実行予算一覧（`/execution-budgets`）と詳細（`/estimates/{id}`）を起点に、
> 編集・各種操作系をすべて取り込む。
>
> 準拠ドキュメント: [`docs/architecture/README.md`](../architecture/README.md) / [`frontend.md`](../architecture/frontend.md) / [`backend.md`](../architecture/backend.md)
> （※コードとドキュメントに差異が出る場合は、先にドキュメントを更新してから実装する＝Single Source of Truth）

---

## 0. 現状サマリ

| 区分 | レガシ `felix_total` | 新 `new_felix_total` |
| --- | --- | --- |
| 一覧表示 | ✅ あり（estimate_list） | ✅ あり（`ExecutionBudget/Index.vue`） |
| 詳細/明細表示 | ✅ あり（estimate_edit + ajax明細） | ✅ あり（`Estimate/Detail.vue` + `CostTable.vue` / `EstimateSection.vue`） |
| **編集・操作系** | ✅ フル（インライン編集 / 依頼 / 選定 / 承認 / 工期 / 行追加削除 / ファイル / PJ融資 / 帳票 / CSV / チェック一覧 / ガント） | ❌ **未実装（完全 read-only）** |
| 書き込み API | ✅ 多数（GET/POST の ajax） | ❌ なし（GET のみ） |

**結論:** 新システムは「見るだけ」。本計画の本体は **書き込み系（CRUD + 業務操作）の移植**である。

---

## 1. ゴールとスコープ

### ゴール
レガシ実行予算でできる操作を、新システムのアーキテクチャ規約（Controller薄 / FormRequest / Repository / Resource / Action / Wayfinder / `useForm` / Tailwindのみ）に沿って再実装する。
**かつ、移植は「機能追加」と「既存コードのリファクタリング」を常にセットで進める**（詳細は §3.5）。単なる写経ではなく、触る範囲を新アーキテクチャに沿ってきれいにしながら積み上げる。

### スコープ（移植対象の全機能）
1. **インライン編集**（項目名・確定見積・税区分・各種金額の即時保存）
2. **業者（発注先/請求先）操作**（追加・選択・選定・支払先設定）
3. **承認フロー**（建設部選定 / 設計部=第一承認(否認理由付) / 常務承認 / 第一〜第三承認）
4. **見積依頼**（依頼ボタン → 履歴記録 → メール送信）
5. **見積UP / ファイル管理**（アップロード・削除・必須ファイル・請求書）
6. **工期設定**（着工/完了日、確定/差戻し、工期確定）
7. **行（工程 EstimateUnit）の追加 / 削除 / 並べ替え**
8. **PJ（融資）連携**（編集・作成・チェック・取得）
9. **帳票出力**（実行予算印刷：原価有/無、CSV）
10. **チェック（管理）一覧**（見積未完 / 未選定 / 回答待ち / 未発注 / ファイル / 請求 / 支払 等 10種）
11. **ガント工程表**
12. **発注**（発注作成・保存・発注メール送信・キャンセル送信）

### スコープ外（別計画）
- レガシ独自の旧レイアウト（`estimates-custom-detail`）の見た目そのものの再現（機能は取り込むがUIは新デザインに統一）
- 認証・権限基盤そのものの作り直し（既存の権限判定ロジックは移植時に踏襲）

---

## 2. 最重要の事前決定事項（着手前に確定する）

実装方針が大きく変わるため、**Phase 1 着手前に必ず合意**する。

### 決定1: データの書き込み先 DB
新システムは現状 **レガシDBのテーブルを直読み**している（新規マイグレーションは Laravel 標準のみ）。書き込みも同じテーブルに対して行うか？

- **案A（推奨・現実的）**: レガシDBの `estimates / estimate_units / estimate_unit_companies / *_histories` 等に**直接読み書き**する。
  - 利点: レガシ画面と新画面を**並行運用**でき、データ整合が自動で取れる。段階移行に最適。
  - 注意: レガシ側のトリガ的なロジック（フラグ連動・集計 `estimate_aggregates` の更新タイミング・メールキュー投入）を新側でも忠実に再現する必要がある。
- **案B**: 新テーブルへ移行（マイグレーション + データ移行）。
  - 利点: クリーンな設計。欠点: 並行運用が困難、移行コスト大。本計画では非推奨。

> 本計画は **案A** を前提に記述する。

### 決定2: 旧ロジックの「副作用」の洗い出し範囲
レガシの各更新メソッドは、単純な `update()` ではなく副作用（同一 unit 内の他業者フラグのリセット、履歴テーブルへの insert、`changed_flg` の付与、メールキュー投入、集計再計算）を伴う。**移植は「DBカラム更新」ではなく「業務手続き（Action）」として写経する**。各 Action 実装時にレガシ該当メソッドを精読し、副作用を1つずつ移すこと。

### 決定3: 楽観ロック / 同時編集
インライン編集を複数人が同時に行う前提。`updated_at` ベースの競合検知を入れるか（レガシは未対応）。最低限、保存失敗時のトースト表示は実装する。

### 決定4: 権限制御
常務承認は `Auth::user()->id == 72 || is_super_admin('system')` 等のハードコードがレガシに存在。新側では Policy / Gate に正規化するか、当面は同等の判定を `Support` に集約するか決める。

---

## 3. アーキテクチャ方針（docs 準拠）

```
Route(Wayfinder) → Middleware → Controller(薄) → FormRequest(検証)
        → Action / Service(業務手続き・副作用)
        → Repository(DBアクセスの唯一窓口)
        → Eloquent Model ↔ DB
返り: JsonResource → Inertia → Vue(表示専念) / useForm(送信)
```

- **Controller**: 入出力のみ。1メソッド=1ユースケース。
- **FormRequest**: 各操作ごとに作る（`UpdateEstimateUnitRequest`, `SelectCompanyRequest` 等）。
- **Action**: 副作用を伴う業務手続きを 1 クラス 1 操作で実装（`SelectUnitCompanyAction` 等）。トランザクションはここ。
- **Repository**: 既存 `EstimateRepository` を拡張。書き込みも Repository 経由（モデル直叩き禁止）。
- **Resource**: 既存の `EstimateUnitResource` 等を再利用。操作後の最新状態を返す。
- **フロント**: `<script setup lang="ts">`、`useForm` 必須（fetch/axios 禁止）、Wayfinder ルート関数使用、Tailwind のみ（`<style>`・インライン style 禁止）。

### Inertia 部分更新の方針
明細テーブルは巨大なため、操作のたびに全体リロードしない。
- **基本**: `router.reload({ only: ['budget'] })` または操作 Action から該当 unit/section の Resource だけ返す**部分リロード**を用いる。
- **インライン編集の即時保存**: `useForm().patch(url, { preserveScroll: true, preserveState: true })` で送信し、返ってきた最新セルで該当箇所のみ再描画。
- 楽観的更新（optimistic UI）は段階2以降の最適化として検討。

---

## 3.5 リファクタリングを同時並行で進める方針（本計画の前提）

本移植は「機能追加」だけでなく **リファクタリングを各フェーズに常時組み込む** ことを前提とする。
新規の書き込み機能を、肥大化・重複したままの既存コードの上に積み増すと負債が増えるため、
**触る範囲は必ず一段きれいにしてから機能を足す（Boy Scout Rule）**を全フェーズ共通の作業ルールとする。

### 基本原則
1. **触ったところを直す（Boy Scout Rule）**: そのフェーズで編集する component / Resource / Repository は、機能追加と同時に重複排除・責務分離・命名整理を行う。
2. **Strangler-Fig（段階的置換）**: レガシを一度に置き換えず、新パターン（Controller→FormRequest→Action→Repository→Resource→`useForm`）に**1ユースケースずつ**置き換えて旧経路を徐々に枯らす。
3. **リファクタとフィーチャはコミットを分ける**: 「振る舞いを変えない整理」と「機能追加」を別コミット（できれば別PR）にし、レビュー・revert を容易にする。
4. **テストで保護してから動かす**: リファクタ対象には先に最低限のテスト（Feature/Action/component）を当て、振る舞い不変を担保してから整理する。
5. **小さく刻む**: 1フェーズ＝デプロイ可能な単位。大規模一括リライトはしない。
6. **docs を先に更新**: 構造を変える判断は、先に `docs/architecture/**` を更新してから実装（Single Source of Truth）。

### 既存コードのリファクタリング対象（現状の負債）
移植で必ず通過する箇所。フェーズ進行に合わせて順次解消する。

| 対象 | 現状の問題 | リファクタ方針 | 主に着手するPhase |
| --- | --- | --- | --- |
| `CostTable.vue` / `EstimateSection.vue` | 列・セルの**markup と `variantOf()` がほぼ重複**。1ファイルが巨大 | 共通の `components/estimate/` 配下へ抽出（`StatusCell` / `MoneyCell` / `PeriodCell` / `CompanyList`）。`variantOf` を `composables/useEstimateStatus.ts` に一元化 | Phase 0〜1 |
| 状態→バッジ variant のマッピング | `CostTable` と `EstimateSection` で**定義が食い違っている**（同じ state で色が違う） | 単一の定義に統一（仕様を確定して composable 化） | Phase 0 |
| `yen()` / 日付整形などの小関数 | 各 component に**コピペ散在** | `lib/format.ts` に集約して import | Phase 0 |
| `EstimateRepository` | 現状は読み取り専用。クエリが長大 | 書き込み追加に合わせて**読み取り/書き込みの責務を整理**、共通の eager-load・base-filter をメソッド分割（一部済）。さらに重複を圧縮 | Phase 1〜 |
| 各 `*Resource` のロジック | 状態算出ロジックが Resource に厚め | 表示用の派生は Resource に残しつつ、**業務判定（承認可否・採用ロジック）は Action/Support へ移す** | Phase 2〜 |
| マジックナンバー（cate/sub_cate/status の数値直書き） | コード中に `2`,`5`,`11`… が散在 | PHP enum / `Support` 定数・TS の union 型へ集約（§7 の定義を正とする） | 全Phase |
| 型定義 `types/index.ts` | 肥大化の兆候 | ドメインごとに分割（`types/estimate.ts` 等）するか index で再エクスポート | Phase 1〜 |
| Wayfinder ルート利用 | 既存も含め URL ハードコードが残っていないか点検 | 触る画面から順に Wayfinder 関数へ寄せる | 全Phase |

### リファクタリングのガードレール（やり過ぎ防止）
- **そのフェーズで触らない領域は触らない**（無関係な大掃除をPRに混ぜない）。
- 振る舞いを変える「改善」は、必ず仕様確認 → テスト → 実装の順。勝手に仕様を変えない（例: バッジ色の統一は「正しい色」を確定してから）。
- パフォーマンス目的の最適化は計測してから（推測で書き換えない）。

---

## 4. ルート設計（Wayfinder / RESTful）

レガシの GET ベース ajax を、新側では適切な HTTP メソッドの RESTful ルートに再設計する。

| 操作 | メソッド・パス | Controller |
| --- | --- | --- |
| 一覧（既存） | `GET /execution-budgets` | `ExecutionBudgetController@index` |
| 詳細（既存） | `GET /estimates/{estimate}` | `EstimateDetailController@show` |
| 工程の項目/金額更新 | `PATCH /estimates/{estimate}/units/{unit}` | `EstimateUnitController@update` |
| 工程の追加 | `POST /estimates/{estimate}/units` | `EstimateUnitController@store` |
| 工程の削除 | `DELETE /estimates/{estimate}/units/{unit}` | `EstimateUnitController@destroy` |
| 工程の並べ替え | `PATCH /estimates/{estimate}/units/reorder` | `EstimateUnitController@reorder` |
| 業者の追加 | `POST /units/{unit}/companies` | `EstimateUnitCompanyController@store` |
| 業者の日付更新（工期） | `PATCH /unit-companies/{company}` | `EstimateUnitCompanyController@update` |
| 業者選定（採用） | `PATCH /unit-companies/{company}/adoption` | `CompanyAdoptionController@update` |
| 建設部選定 | `PATCH /unit-companies/{company}/build-select` | `CompanySelectionController@build` |
| 設計部=第一承認（否認理由付） | `PATCH /unit-companies/{company}/design-select` | `CompanySelectionController@design` |
| 常務承認 | `PATCH /unit-companies/{company}/exec-approval` | `CompanySelectionController@exec` |
| 第一〜第三承認 | `PATCH /unit-companies/{company}/approvals/{step}` | `CompanyApprovalController@update` |
| 見積依頼 | `POST /unit-companies/{company}/request` | `EstimateRequestController@store` |
| 見積UP（ファイル追加） | `POST /unit-companies/{company}/files` | `EstimateFileController@store` |
| ファイル削除 | `DELETE /files/{file}` | `EstimateFileController@destroy` |
| 工期 確定 / 差戻し | `PATCH /estimates/{estimate}/term` | `EstimateTermController@update` |
| 発注 作成/保存 | `POST /estimates/{estimate}/orders` | `EstimateOrderController@store` |
| 発注メール送信 | `POST /estimates/{estimate}/orders/{order}/send` | `EstimateOrderController@send` |
| PJ 編集 | `GET/PATCH /estimates/{estimate}/pj` | `EstimatePjController@edit/update` |
| PJ 作成チェック/作成 | `POST /estimates/{estimate}/pj/check`・`/pj` | `EstimatePjController@check/store` |
| CSV 出力 | `GET /execution-budgets/export` | `ExecutionBudgetExportController@csv` |
| 帳票（原価有/無） | `GET /estimates/{estimate}/print` | `EstimatePrintController@show` |
| チェック一覧 | `GET /execution-budgets/checks/{type}` | `EstimateCheckController@index` |
| ガント工程表 | `GET /estimates/{estimate}/gantt` | `EstimateGanttController@show` |

> ルートは `routes/web.php` に定義し、Wayfinder が `resources/js/routes/**` に型付き関数を自動生成する（URL ハードコード禁止）。

---

## 5. フェーズ別 実装計画

各フェーズは「独立してデプロイ可能」を原則とする。Phase 1 から順に。
**各フェーズには「リファクタ」項目を必ず含める**（🔧 印）。リファクタは機能追加とコミットを分ける（§3.5）。

### Phase 0 — 基盤整備＋初期リファクタ（前提）
- [ ] 決定1〜4 の合意（書き込み先DB / 副作用範囲 / 楽観ロック / 権限）
- [ ] 書き込み用 Repository インターフェース拡張（`EstimateRepositoryInterface` に save/update/delete 系を追加）
- [ ] `Action` レイヤのディレクトリ・命名規約を docs に追記（`app/Actions/Estimate/**`）
- [ ] 共通: 操作後に最新 unit/section を返す `EstimateUnitResource` 単体返却ルートの整備
- [ ] フロント共通: 明細セルの編集用 UI 部品（`EditableCell.vue`, `DatePickerCell.vue`, `StatusBadgeButton.vue`）を `components/` に新設
- [ ] 🔧 **リファクタ（表示の重複解消・破壊的変更なし）**:
  - [ ] `variantOf()` の食い違いを解消し `composables/useEstimateStatus.ts` に一元化（正しい配色を確定してから）
  - [ ] `yen()`・日付整形を `lib/format.ts` に集約
  - [ ] `CostTable.vue` / `EstimateSection.vue` から共通セル部品（`StatusCell` / `MoneyCell` / `PeriodCell` / `CompanyList`）を `components/estimate/` へ抽出
  - [ ] 着手前にこれら表示の現状を component テスト（スナップショット相当）で保護

### Phase 1 — インライン編集（最小の書き込み）
レガシ `update` / `update_company` 相当。最も利用頻度が高く、効果が大きい。
- [ ] `PATCH /estimates/{estimate}/units/{unit}`（label, price, tax, tmp_unit_price 等）
- [ ] `UpdateEstimateUnitRequest`（カラムのホワイトリスト検証）
- [ ] `UpdateEstimateUnitAction`（更新 + 必要なら `estimate_aggregates` 再計算）
- [ ] `PATCH /unit-companies/{company}`（tmp_construct_at, tmp_completion_at 等）
- [ ] フロント: Phase 0 で抽出した共通セルを `EditableCell` 化、`useForm().patch`
- [ ] 保存成功/失敗トースト（成功「保存しました。」＝レガシ踏襲）
- [ ] 🔧 **リファクタ**: `EstimateRepository` の読み取り/書き込み責務を整理（共通 base-filter・eager-load のメソッド分割）。cate/sub_cate の数値直書きを enum/定数化し始める

### Phase 2 — 業者選定・承認フロー
レガシ `update_adoption_flg` / `update_tmp_company_select_flg` / `update_design_company_select_flg` / `update_fix_company_select_flg` 相当。
- [ ] 採用フラグ（`adoption_flg`）更新 + **同一 unit 内の他業者を 0 にリセット**（副作用）
- [ ] 建設部選定 / 設計部第一承認（**否認理由 `deny_comment` の入力モーダル**）/ 常務承認
- [ ] 第一〜第三承認（complete_status / step）
- [ ] 各 Action でステータス連動・履歴 insert を写経
- [ ] 権限: 常務承認の表示・実行可否（決定4）
- [ ] フロント: バッジをクリック可能な `StatusBadgeButton` に、否認は `Dialog` で理由入力
- [ ] 🔧 **リファクタ**: 業務判定（承認可否・採用ロジック）を `*Resource` から `Action`/`Support` へ移し、Resource は表示派生のみに戻す。status の数値を enum/union 型へ集約

### Phase 3 — 行（工程）の追加・削除・並べ替え
レガシ `create` / `delete` / 並べ替え Grid 相当。
- [ ] `POST /estimates/{estimate}/units`（テンプレ工程の追加）
- [ ] `DELETE /estimates/{estimate}/units/{unit}`（論理削除 `deleted_at`、確認ダイアログ）
- [ ] `PATCH /estimates/{estimate}/units/reorder`（`sort` 一括更新、ドラッグ&ドロップ）
- [ ] 業者の追加（`POST /units/{unit}/companies`、会社検索コンボボックス）
- [ ] フロント: セクション内の行操作 UI、`AlertDialog` で削除確認
- [ ] 🔧 **リファクタ**: `CompanyList` 部品を編集対応に拡張し `CostTable`/`EstimateSection` 双方で共有（重複の最終解消）。入金/支払分割の描画ロジックを専用 composable へ

### Phase 4 — 見積依頼・ファイル管理
レガシ `create_send_order` / `estimate_unit_company_file_custom/*` 相当。
- [ ] 見積依頼（`POST /unit-companies/{company}/request`）→ 履歴 insert → **メールキュー投入**
- [ ] ファイルアップロード（`POST /unit-companies/{company}/files`、ドラッグ&ドロップ）
- [ ] ファイル削除（`DELETE /files/{file}`）、必須ファイル/請求書の区分（type=1/2）
- [ ] フロント: shadcn ベースのアップローダ部品（Dropzone 相当を Tailwind で）
- [ ] メール送信は Laravel の Queue/Mailable に再設計（レガシ `mail_queues` 互換要確認）
- [ ] 🔧 **リファクタ**: 履歴 insert + メール投入の共通手順を `Action`/`Support` に集約（依頼・発注・必須ファイルで再利用）

### Phase 5 — 工期設定・ガント
レガシ `term_set` / `update_ok` / `update_ng` / `gant_chart` 相当。
- [ ] 工期確定 / 差戻し（`PATCH /estimates/{estimate}/term`）
- [ ] ガント工程表（`GET /estimates/{estimate}/gantt`、読み取り → 後続で日付ドラッグ編集）

### Phase 6 — PJ（融資）連携
レガシ `apiPjEdit` / `apiPjUpdate` / `checkPjCreate` / `createPj` / `getPjsByUnitIds` 相当。
- [ ] PJ 編集フォーム / 更新
- [ ] PJ 作成チェック / 作成
- [ ] フロント: 融資セクションの編集ダイアログ

### Phase 7 — 帳票・CSV・チェック一覧
- [ ] 実行予算 印刷（原価有/無）— レガシの帳票レイアウトを移植（PDF/印刷ビュー）
- [ ] CSV 出力
- [ ] チェック一覧 10種（見積未完 / 未選定 / 回答待ち / 未発注 / ファイル / 請求 / 支払 / 複合）
- [ ] フロント: 一覧画面にチェック系へのナビゲーション・件数バッジ
- [ ] 🔧 **リファクタ**: チェック条件のクエリを `EstimateRepository` のスコープ/メソッドに集約し、10種で共通化（条件の重複排除）

### Phase 8 — 発注
- [ ] 発注作成・保存、発注メール送信、キャンセル送信、必須ファイル確認送信
- [ ] レガシ `EstimateCustomDetailController@order_*` を Action 化して移植
- [ ] 🔧 **リファクタ（仕上げ）**: 旧経路（GETベース ajax 等）で枯れたコードを削除。残ったマジックナンバー・URLハードコード・重複を一掃し、`docs/architecture/**` を最終状態に更新

---

## 6. 機能 ↔ レガシ実装 対応表（移植の写経元）

| 機能 | レガシ Controller::Method | レガシ JS / 画面 | 対象テーブル |
| --- | --- | --- | --- |
| インライン編集（工程） | `NewEstimateCustomEditController@update` | `estimate_script` `.js-update` | `estimate_units` |
| インライン編集（業者日付） | `@update_company` | `.js-update_company` | `estimate_unit_companies` |
| 採用フラグ | `@update_adoption_flg` | `.js_adoption_flg` | `estimate_unit_companies` |
| 仮見積選定 | `@update_tmp_company_select_flg` | `.js-tmp_company_select_flg` | `estimate_unit_companies` |
| 設計部第一承認 | `@update_design_company_select_flg` | `.js-design_company_select_flg`（否認理由） | `estimate_unit_companies` |
| 常務承認 | `@update_fix_company_select_flg` | `.js-fix_company_select_flg` | `estimate_unit_companies` |
| 工程追加 | `@create` | `.js-add` | `estimate_units` |
| 工程削除 | `@delete` | `.js-del` | `estimate_units` |
| 全体日付更新 | `@update_date` | `.js-update_date` | `estimate_dates` |
| 工期確定/差戻し | `@update_ok` / `@update_ng` | term_set 画面 | `estimate_units` |
| 見積依頼 | `EstimateCustomDetailController@create_send_order` | 依頼ボタン | `estimate_order_histories`, `mail_queues` |
| ファイルUP/削除 | `EstimateUnitCompanyFileCustomController@add/delete` | Dropzone | `estimate_unit_company_files` |
| PJ編集/作成 | `@apiPjEdit/@apiPjUpdate/@createPj` | `pj-edit.js` / `pj-create.js` | `projects`, `pj_histories` |
| CSV | `@csv` | — | `estimates` |
| 印刷 | `PrintEstimateController@print_budget` | 印刷ボタン | `estimates`, `estimate_units`, `estimate_unit_companies` |
| チェック一覧 | `@check_*_list`（10種） | チェック画面 | 各種 |
| ガント | `@gant_chart` / `@get_gant_data` | ガント画面 | `estimate_unit_gant` |

> 各セルの状態（`approved/selected/sent/has/denied/replied/pending/canceled/notified/none`）は、新側 Resource（`EstimateUnitCompanyResource`）で既に算出済み。**操作後はこの Resource を返して再描画**すれば表示ロジックを共有できる。

---

## 7. データ・ステータス定義（移植時の参照）

### カテゴリ `cate`
`11=土地販売 / 12=建物販売 / 1=土地原価 / 2=建物原価 / 5=金融融資 / 13=固定資産税 / 99=その他原価 / 0=対象外`

### サブカテゴリ `sub_cate`
`1=仕入 / 2=建設 / 3=販売 / 4=金融 / 5=融資 / 0=販売外 / 99=その他`

### 業者ステータス
- `adoption_flg`(0/1) 採用 / `company_select_flg`(0/1) 建設部選定 / `invoice_flg`(NULL=発注先,1=請求先) / `order_flg` 発注
- `tmp_status` / `design_status` / `fix_status`（1=未返信, 2=承認, 3=否認）/ `deny_comment` 否認理由
- `changed_flg` 金額変更 / `send_flg` 送信済

### 表示の特殊仕様（移植時に維持）
- **入金/支払の分離**: `invoice_flg=1` の業者がある工程は行を分割（請求側/発注側）。
- **共通工程 `common_flg`**: 複数実行予算で共有する工程は親値を参照。
- **対象物件の絞り込み**: 一覧は `name LIKE '●%'`（レガシ踏襲、新 `EstimateRepository@paginateBudgets` で実装済）。

---

## 8. リスク・留意点

1. **副作用の写経漏れ**: 単純 update に見えて履歴 insert / 他フラグリセット / 集計更新を伴う。Action 実装時に必ずレガシ該当メソッドを精読。
2. **`estimate_aggregates` の整合**: 一覧・サマリはこの集計テーブルを参照。金額編集後の再計算タイミングをレガシと一致させる（しないと一覧と明細が食い違う）。
3. **メール送信**: レガシは `mail_queues` にinsert。新側 Queue へ寄せるか、当面同テーブル互換にするか要決定。誤送信防止のため Phase 4 は dev 環境で送信先を固定。
4. **並行運用中の競合**: レガシ画面と新画面が同じDBを同時編集。フラグの意味・enum を完全一致させる。
5. **権限**: 常務承認などのハードコード権限を Policy/Gate に正規化（決定4）。
6. **大規模テーブルの描画性能**: 34列×多数行。操作のたびの全体リロードを避け、部分更新（`only`）を徹底。
7. **論理削除**: `deleted_at` を尊重（物理削除しない）。

---

## 9. テスト方針

- **Feature テスト（Pest/PHPUnit）**: 各書き込みエンドポイントについて「正常更新 / バリデーション / 副作用（履歴・他フラグ・集計）/ 権限」を検証。
- **Action 単体テスト**: 副作用ロジックを独立検証。
- **回帰**: 同一データに対しレガシ画面と新画面で結果（DB状態）が一致することを確認するシナリオを用意。
- **フロント**: 主要操作（編集・選定・承認・依頼）の `useForm` 送信と部分更新を最低限の component テストで担保。
- **リファクタ保護（重要）**: リファクタは「振る舞い不変」が前提。**整理に着手する前に対象へテストを当て**、リファクタ前後でグリーンを維持する（テスト無しの大きな整理はしない）。リファクタ commit と feature commit を分け、問題時に切り戻せるようにする。

---

## 10. 作成・変更ファイル一覧（Phase 1 例）

```
new_felix_total/
├── routes/web.php                                  # PATCH ルート追加
├── app/Http/Controllers/EstimateUnitController.php # update（薄い）
├── app/Http/Requests/UpdateEstimateUnitRequest.php # 検証
├── app/Actions/Estimate/UpdateEstimateUnitAction.php # 更新+集計
├── app/Repositories/EstimateRepository.php         # 書き込みメソッド追加
├── app/Repositories/Contracts/EstimateRepositoryInterface.php
├── resources/js/components/EditableCell.vue        # 新規 共通部品
├── resources/js/components/CostTable.vue           # セル編集対応
├── resources/js/components/EstimateSection.vue     # セル編集対応
└── resources/js/routes/**                          # Wayfinder 自動生成
```

---

## 11. 未確定事項（要確認リスト）

- [ ] 書き込み先 DB（案A: レガシDB直 / 案B: 新テーブル）→ **A推奨**
- [ ] メール送信基盤（`mail_queues` 互換 / 新 Queue）
- [ ] 権限モデル（Policy/Gate へ正規化するか）
- [ ] 楽観ロックの要否
- [ ] 「実行予算一覧画面に全機能を組み込む」の解釈
      （= 一覧から詳細へ遷移して操作する従来構成でよいか / 一覧上でインライン操作まで求めるか）
- [ ] レガシのチェック一覧10種・ガント・帳票の優先度（後半フェーズで可か）

---

### 進め方の提案
まず **Phase 0 + Phase 1（インライン編集）** を小さく通し、書き込み基盤（Controller→FormRequest→Action→Repository→Resource→`useForm`→部分更新）の型を確立する。その型ができれば Phase 2 以降は同じパターンの横展開になる。

なお Phase 0 のリファクタ（表示の重複解消・`variantOf`/`yen` 一元化・共通セル部品の抽出）は、**振る舞いを変えない安全な整理**なので、書き込み機能の着手前に先行して入れておくと、以降の全フェーズが共通部品の上に乗り、開発・レビューが速くなる。各フェーズで「機能追加 → そのフェーズで触った範囲のリファクタ → テスト」をワンセットで回す（🔧 印の項目）。

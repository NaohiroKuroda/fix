# 見積管理 連携仕様（IF仕様）

> 「画面同士・システム同士が **何を共有していて**、**何かが起きたら誰に伝わるか**」を整理するドキュメント。
> 姉妹資料 [`見積管理_シナリオテスト.md`](./見積管理_シナリオテスト.md)（＝正常系シナリオ／ハッピーパス）と対になる。
> 本書は **実装コードに準拠**。仕様の出典は `docs/detailed-design/quotations/`（00〜06）。差異があれば詳細設計書を正とする。

---

## このドキュメントの位置づけ（4点セット）

要件定義〜基本設計の間に、以下の4点セットを **1から順番に、1つずつ確定させてから次へ** 作る。
出来上がった1〜4は、そのまま AI への実装依頼・実装後の動作確認基準になる。

| # | 成果物 | 内容 | 本書での扱い |
| :-: | :-- | :-- | :-- |
| 1 | 用語・前提 | 登場人物（ロール）、データ単位の定義 | [シナリオテスト §0](./見積管理_シナリオテスト.md) を参照 |
| 2 | 画面一覧 | 各画面の役割・遷移 | [詳細設計 01〜05](../../detailed-design/quotations/) を参照 |
| 3 | **連携仕様（IF仕様）** | 状態の一覧＋イベントの一覧（本書の主題） | **本書 §3** |
| 4 | 正常系シナリオ | 「この操作をしたら、こう動く」の手順 | 本書 §4＋[シナリオテスト](./見積管理_シナリオテスト.md) |

**進め方**
- 1から順番に、1つずつ確定させてから次へ進む（並行してあれこれ触らない）。
- 出来上がった1〜4は、そのまま AI への実装依頼のベースになる。

**適用イメージ（実案件）**
- 要件定義〜基本設計の間に、この4点セットを作る。
- 詳細設計以降は、出来た部分から日本型アジャイルで進める。
- 特に **3（IF仕様）が、サブシステム間（fix ⇔ felix_total、担当 ⇔ 部長）の認識ズレを防ぐ要** になる。

---

## 3. 連携仕様（IF仕様）

### 3-0. 前提：この見積管理での「共有」と「通知」

- **共有される「正」のデータ**は、fix と felix_total が **同一 MySQL（`fix`）を共有** しているため、DB を単一の真実源（Single Source of Truth）とする。画面間の受け渡しは DB 再取得で反映される。
- **「通知」の手段はメール等ではなく**、①コメントスレッドへの自動記録（`t_comments`）と ②一覧の未読数バッヂ（`unread_count`）である。担当 ⇔ 部長の連絡はこの2つで伝わる。
- コメントスレッドは **費用項目（`t_building_cost_items`）単位**。項目に業者（見積先）が複数あっても1スレッドに集約される。

### 3-1. 状態の一覧（何が「正」で、どこに保存されるか）

| # | 状態・データ | 保存先（テーブル.カラム） | 「正」の定義 / 値 | 参照する画面 |
| :-: | :-- | :-- | :-- | :-- |
| S1 | 承認状態 | `t_cost_quotations.approval_status` | `UNSELECTED` / `STAFF_APPROVED` / `MANAGER_APPROVED` / `CANCEL_REQUESTED` / `APPROVED` | 全画面（画面ごとに対象状態が異なる） |
| S2 | 仮選定 | `t_cost_quotations.is_drafted` | `1`=仮選定中 / `0`=解除 | 見積依頼・業者選定 |
| S3 | 否認理由 | `t_cost_quotations.deny_comment` | 部長否認で記録される差し戻し理由（`null`=否認なし） | 業者選定（赤表示判定） |
| S4 | 確定見積額 | `t_building_cost_items.quotation_amount` | 選定確定後の項目金額 | 部長承認・取消申請・取消承認 |
| S5 | 相見積回答額 | 相見積履歴 `amount_excluding_tax`（`is_latest`） | 業者からの最新回答。有無が「回答状態」 | 見積依頼・業者選定 |
| S6 | 見積依頼履歴 | `t_cost_quotation_requests` | その見積先へ送信した依頼のログ（件数＝`sendCount`） | 見積依頼 |
| S7 | やり取り（コメント） | `t_comments`（`commentable_type=TBuildingCostItem` / `commentable_id=項目ID`） | 項目単位スレッドの発言（本文・投稿者・時刻） | 全画面（チャット） |
| S8 | 既読ポインタ | `t_comment_read_timestamps`（`readable_type/readable_id/user_id/last_read_at`） | ユーザー×項目ごとの最終既読時刻 | 全画面（未読バッヂ） |
| S9 | 添付ファイル | `t_attachments`（`file_path/original_name/mime_type/size/user_id`）＋ `public` ディスク | 本体 `comments/{itemId}/{uuid}.{ext}`、サムネ `.../thumbs/{uuid}.jpg` | 全画面（チャット） |
| S10 | felix_total 側の反映 | felix_total スキーマ（見積依頼／発注確定） | 見積依頼・業者選定の確定は felix_total へも反映（同一DB） | 見積依頼・業者選定 |

> 表示専用の派生値（一覧メタ）: `comments_count` / `has_comments` / `unread_count` は
> `BuildingQuotationRepository::attachCommentMeta()` が S7・S8 から一覧取得時に算出する（DB列ではない）。

### 3-2. イベントの一覧（何が起きたら、何が更新され、誰に伝わるか）

「通知先」列は、そのイベントで **相手に伝わる手段**（コメント自動記録＋未読バッヂ、または felix_total 反映）を示す。

| # | イベント（操作） | 発火画面 / 操作者 | 更新される状態 | 連携・通知（誰に伝わるか） |
| :-: | :-- | :-- | :-- | :-- |
| E1 | 見積依頼を送信 | 見積依頼 / 担当 | S6 追加（`recordQuoteRequests`） | **felix_total** へ依頼反映（`orderEstimate`）。移行元 `source_id` 無しは除外 |
| E2 | 仮選定トグル | 見積依頼・業者選定 / 担当 | S2（`is_drafted`）更新 | 画面内のみ（即時保存・失敗時ロールバック） |
| E3 | 業者選定を確定 | 業者選定 / 担当 | S1 `UNSELECTED → STAFF_APPROVED`（`adoptCompany`） | **felix_total** へ発注業者反映。バッヂ（未選定数）減 |
| E4 | 部長承認 | 部長承認 / 部長 | S1 `STAFF_APPROVED → MANAGER_APPROVED`（`tmpSelectCompany`） | **通知なし**（理由入力なし・コメント自動記録なし）※要注意 |
| E5 | 否認（差し戻し） | 部長承認 / 部長 | S1 `STAFF_APPROVED → UNSELECTED`、S3 `deny_comment` 記録 | **担当へ**：S7 に `【否認】{理由}` 自動投稿＋担当の未読バッヂ増。業者選定で赤表示 |
| E6 | 取消申請 | 部長取消申請 / 担当 | S1 `MANAGER_APPROVED → CANCEL_REQUESTED`（`advanceStatus`） | **部長へ**：S7 に `【取消申請】{理由}` 自動投稿＋部長の未読バッヂ増 |
| E7 | 取消承認 | 部長取消承認 / 部長 | S1 `CANCEL_REQUESTED → APPROVED`（`advanceStatus`） | **担当へ**：S7 に `【取消承認】{理由}` 自動投稿＋未読バッヂ増 |
| E8 | コメント投稿 | 全画面 / 担当・部長 | S7 追加、（添付時）S9 追加 | **相手へ**：同一項目スレッドに追記＋相手の未読バッヂ増。画像は圧縮＋サムネ生成 |
| E9 | スレッドを開く（閲覧） | 全画面 / 本人 | S8 更新（`last_read_at = now`） | 自分の未読バッヂを即時クリア |

> **IF上の注意点（E4）**: 部長承認（承認成功）だけは理由入力・コメント自動記録が無く、担当側に「承認された」ことが
> スレッド上には残らない。承認の事実は S1 の状態遷移でのみ表現される。仕様として通知が必要なら E4 に S7 追記を足すこと。

### 3-3. サブシステム境界（fix ⇔ felix_total）

| 連携点 | 方向 | 手段 | 対象イベント |
| :-- | :-- | :-- | :-- |
| 見積依頼 | fix → felix_total | `FelixTotalQuoteRequestGateway::orderEstimate` | E1 |
| 発注業者確定 | fix → felix_total | `adoptCompany`（`no_competitive_flg` 既定0） | E3 |
| 取消系 | fix 内で完結 | `t_cost_quotations` 直接 UPDATE（felix_total 連携なし） | E6・E7 |
| 一覧の反映 | 双方向（同一DB） | `router.reload` による再取得 | iframe モーダルを閉じた後 等 |

---

## 4. 画面ごとの正常系シナリオ（ハッピーパス）

「この操作をしたら、こう動く」を手順化したもの。**AI 実装後の動作確認基準**として使う。
詳細な期待結果（トースト文言・状態値・ケースID `ST-XXX-NN`）は
[`見積管理_シナリオテスト.md`](./見積管理_シナリオテスト.md) を参照。ここでは各画面の「正常系1本」を要約する。

| 画面 | 操作者 | 前提状態(S1) | 操作 | 結果（状態遷移・通知） | 詳細 |
| :-- | :-- | :-- | :-- | :-- | :-- |
| 見積依頼 | 担当 | 依頼行あり | 未依頼を選択→送信 | S6 追加・felix_total反映（E1） | ST-QR-02 |
| 業者選定 | 担当 | `UNSELECTED` | 回答ある業者を選定→確定 | `→STAFF_APPROVED`（E3） | ST-VS-04 |
| 部長承認 | 部長 | `STAFF_APPROVED` | 承認 | `→MANAGER_APPROVED`（E4・通知なし） | ST-MA-01/02 |
| 部長取消申請 | 担当 | `MANAGER_APPROVED` | 理由入力→取消申請 | `→CANCEL_REQUESTED`＋`【取消申請】`（E6） | ST-CR-01/02 |
| 部長取消承認 | 部長 | `CANCEL_REQUESTED` | 理由入力→取消承認 | `→APPROVED`＋`【取消承認】`（E7） | ST-CA-01/02 |
| チャット（全画面） | 担当・部長 | 任意 | 本文/添付を投稿 | S7（＋S9）追加・相手へ未読（E8） | ST-CHAT-06 / ST-FILE-* |

### 代表フロー（E2E）
1. **依頼→選定→承認**：担当が見積依頼(E1)→業者選定確定(E3)→部長が承認(E4)。`STAFF_APPROVED → MANAGER_APPROVED`。
2. **否認→再選定**：部長が否認(E5)→担当がスレッドで `【否認】` を確認→別業者を選定(E3)。
3. **取消**：担当が取消申請(E6)→部長が取消承認(E7)。`MANAGER_APPROVED → CANCEL_REQUESTED → APPROVED`。

> フローの各ステップは §3-2 のイベント（E1〜E9）に対応する。E5・E6・E7 では必ず S7（コメント自動記録）が発生する点が
> 「誰に伝わるか」の実体であり、IF仕様として最重要。

---

## 付録: 状態遷移図（S1 / approval_status）

```
UNSELECTED ──E3 業者選定──▶ STAFF_APPROVED ──E4 部長承認──▶ MANAGER_APPROVED
   ▲                            │(E5 否認で差し戻し)                  │
   └────────────────────────────┘                            (E6 取消申請)
                                                                    ▼
                                        APPROVED ◀──E7 取消承認── CANCEL_REQUESTED
```

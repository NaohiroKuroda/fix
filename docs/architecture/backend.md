# バックエンドアーキテクチャ

> 対象: `new_felix_total` のバックエンド層（Laravel 13 / PHP 8.3）。
> 実装前に必ず本ドキュメントを確認すること。

## 技術スタック

| 分類 | 採用技術 | バージョン |
| --- | --- | --- |
| 言語 | PHP | `^8.3` |
| フレームワーク | Laravel | `^13.8` |
| SPA アダプタ | Inertia.js サーバ（`inertiajs/inertia-laravel`） | `^3.1` |
| ルーティング型生成 | Laravel Wayfinder（`laravel/wayfinder`） | `^0.1` |
| REPL | `laravel/tinker` | `^3.0` |
| Lint / Format | `laravel/pint` | `^1.27` |
| テスト | `phpunit/phpunit`（Feature テストで Inertia 応答を検証） | `^12.5` |
| 開発補助 | `laravel/pail`（ログ） / `nunomaduro/collision` / `mockery/mockery` / `fakerphp/faker` | — |
| データベース | MySQL（既存 `felix_total` の `fix_db` を参照） | — |

> パッケージ単位の詳細は §4.2、各技術の運用方針（Repository / JsonResource / Wayfinder 等）は §1 以降を参照。
> 新規パッケージを追加する場合は、本ドキュメントに追記してから導入する（[`CLAUDE.md`](../../CLAUDE.md)）。

## 4.2 バックエンド（Laravel 13 / PHP 8.3）

* **役割**
    * HTTP リクエストの受付・認可・バリデーション。
    * ドメインロジックの実行と永続化。
    * **Inertia::render()** によるページ props のサーバサイド提供。
    * **Wayfinder** によるフロントエンド向け型付きルート/アクションの生成。

* **使用パッケージ（composer）**
    * `laravel/framework`（`^13.8`）
    * `laravel/tinker`（`^3.0`）
    * `laravel/wayfinder`
    * （Inertia サーバアダプタ）`inertiajs/inertia-laravel`
* **開発用（composer require-dev）**
    * `laravel/pint`（`^1.27`） — コードフォーマッタ
    * `phpunit/phpunit`（`^12.5`） — テスト
    * `nunomaduro/collision`, `mockery/mockery`, `fakerphp/faker`, `laravel/pail`

## 1. レイヤ構成

```
Route ─▶ Middleware ─▶ Controller ─▶ FormRequest（バリデーション）
                          │
                          ▼
                  Action / Service（ドメインロジック）
                          │
                          ▼
                  Repository（データアクセスの唯一の窓口）
                          │
                          ▼
                  Eloquent Model ─▶ Database
                          │
   Controller が JsonResource で整形し Inertia::render('Page', $props) で応答
```

- **Controller** は薄く保つ。入力の受け取りと応答生成に専念する。
- **Controller は Repository を直接呼ばない**。データアクセス・業務処理は必ず **Service 層**を経由する（Controller → Service → Repository）。
- ドメインロジックは **Action / Service クラス**へ切り出す。
- バリデーションは **FormRequest** に集約する。
- **DB アクセスは必ず Repository クラスを経由する**。Controller / Service / Action から Eloquent モデル（`Model::query()` / `Model::find()` 等）を直接呼ばない（→ [3. データアクセス](#3-データアクセスrepository-パターン)）。
- レスポンスに渡すデータは **JsonResource** で整形する（→ [4. レスポンス整形](#4-レスポンス整形jsonresource)）。
- 横断的な補助処理は **ユーティリティ（`Utils`）／ヘルパー（`Helpers`）** に集約する（→ 「5. 共通処理クラス」）。

## 1.5 サービス層（Controller とデータアクセスの仲介）

**Controller は Repository を直接呼ばず、必ず Service を経由する**（Controller → Service → Repository）。
Service は `app/Services/` に置き、1 集約（≒主要モデル）につき 1 つを基本とする
（例: `Estimate` 集約に対する `EstimateService`）。

- **責務**: ユースケース単位の入口。Repository を介したデータ取得/永続化の呼び出しと、
  業務ルール（判断・複数手順の調整）の実装を担う。
- **業務ロジックの置き場所**: 業務ルールは Service（または単一目的の `Action`）に書く。
  Controller / Repository / Resource には業務判断を持たせない。
- **依存**: Service はコンストラクタで **Repository のインターフェース**を受け取る。
  Service 自体は concrete クラスとして注入する（差し替え/モックが必要になるまで
  インターフェースは作らない＝過剰設計を避ける）。
- **read 中心の現状**: 取得処理の委譲が中心で業務ルールがほぼ無い場合でも、
  Controller からの単一の入口として Service を通す（将来ルールが増えた時の差し込み口になる）。

```php
class EstimateService
{
    public function __construct(
        private readonly EstimateRepositoryInterface $estimates,
    ) {}

    /** @param array<string, mixed> $filters */
    public function searchForStatusManagement(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->estimates->search($filters, $perPage);
    }
}
```

## 1.6 例外処理・ログ・エラー表示

サーバー処理で発生した例外は、**Service 層で記録（ログ）してからドメイン例外へ変換して投げ直し**、
**中央（`bootstrap/app.php`）でユーザー向けメッセージ（画面右上のトースト）へ変換する**。
これにより「記録は Service 層」「画面表示は中央集約」と責務を分ける。

### 方針

- **Service 層で try-catch**: Service の各公開メソッドは処理を `try` で囲み、`catch (\Exception $e)` で
  失敗時に `Log::error()` へ**統一フォーマット**で記録する。第一引数は失敗内容の和文、第二引数の配列は
  `message`（`$e->getMessage()`）→ 主要な業務コンテキスト（`companyId` 等）→ `file` / `line` / `trace`
  （`$e->getFile()` / `getLine()` / `getTraceAsString()`）の順とする。
  記録後は `App\Exceptions\ServiceException` を**メッセージ無し**で投げ直す（`previous` に元例外を保持）。
  ユーザー向け文言は `ServiceException` の既定メッセージを用いる。
- **中央でトースト変換**: `bootstrap/app.php` の `withExceptions()` で `ServiceException` を捕捉し、
  **web の更新系リクエスト（非 GET・非 api）**では `back()->with('error', $e->getMessage())` へ変換する。
  `flash.error` は `HandleInertiaRequests` の共有プロパティ経由で `FlashMessages` → `AppToast`
  （画面右上のトースト）に表示される。GET（ページ読込）・api は既定のレンダリングへ委ねる。
- **二重ログの禁止**: `ServiceException` は Service で既に記録済みのため、
  `withExceptions()` で `dontReport(ServiceException::class)` を指定し、フレームワーク側の再記録を止める。
- **共有プロパティ内の防御**: `menuBadges` のように**全画面のレンダリング時に評価される共有プロパティ**は、
  失敗しても画面全体を落とさないよう、その場で `try-catch` して `Log::error()` の上で安全な既定値
  （`null` 等）を返す（トーストは出さない）。

```php
// Service：記録してドメイン例外へ変換
public function reject(int $companyId, string $reason): int
{
    try {
        // ...ユースケース本体...
        return $count;
    } catch (\Exception $e) {
        Log::error('部長承認の否認に失敗しました', [
            'message'   => $e->getMessage(),
            'companyId' => $companyId,
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'trace'     => $e->getTraceAsString(),
        ]);
        throw new ServiceException(previous: $e);
    }
}
```

### ログ出力先（日次ローテーション・2 週間保持）

- ログチャネルは **`daily`**（`config/logging.php`）を用いる。日毎に
  `storage/logs/laravel-YYYY-MM-DD.log` を生成し、`LOG_DAILY_DAYS`（既定 **14 日 = 2 週間**）で自動削除する。
- `.env` で `LOG_STACK=daily`（`stack` の内訳を `daily` に）・`LOG_DAILY_DAYS=14` を指定する。

## 1.7 サイドメニューの表示制御（ロール別）

サイドメニューの各ボタンは、ログインユーザーのロール（`admin_roles.slug`）に応じて出し分ける。
**対応表は `config/felix.php` の `menu_roles` を唯一の正（Single Source of Truth）とする。**

- **ロール定義（`config/felix.php`）**
  - `staff_role_slugs`（既定 `engineer,system` = 建設部 / システム開発）: 見積依頼・業者選定・部長取消申請。
  - `manager_role_slugs`（既定 `engineer_manager,tmp` = 建設部部長 / 建設承認）: 部長承認・部長取消承認。
    やり取り（コメント）の発言ロール（manager/staff）・承認判定にも同じ slug を用いる（`isEstimateManager`）。
  - `admin_role_slugs`（既定 `administrator`）: **全メニューを表示するスーパーユーザー**。
    現行データに `administrator` slug が無い環境では、実運用の slug に合わせ `FELIX_ADMIN_ROLE_SLUGS` で上書きする。
- **判定（`AdminUser`）**: `menuPermissions(): array<string,bool>` が「メニューキー => 表示可否」を返す。
  administrator は全 true。それ以外は `menu_roles[キー]` と付与 slug の積集合で判定する。
- **受け渡し**: `HandleInertiaRequests` が共有プロパティ `menuPermissions`（Closure）で渡し、
  フロント（`AppLayout.vue`）は `perms[キー]` が true の項目だけ描画する。配下が 0 件のメニューグループは丸ごと隠す。
- **拡張（発注管理など）**: 新メニューは `menu_roles` に1行（`'order-management' => [...slugs]`）足し、
  フロントの項目に同じキーを付けて `perms['order-management']` で出し分ける。バックエンド／フロント両方の1箇所ずつで完結する。

## 2. ディレクトリ構成

```
app/
├── Http/
│   ├── Controllers/        # 薄いコントローラ
│   ├── Requests/           # FormRequest（バリデーション）
│   ├── Resources/          # JsonResource（レスポンス整形）
│   └── Middleware/
├── Repositories/           # データアクセス層
│   ├── Contracts/          # Repository インターフェース
│   │   └── EstimateRepositoryInterface.php
│   └── EstimateRepository.php
├── Actions/                # 単一目的のドメイン処理（任意）
├── Services/               # ドメインロジック（Controller の単一の入口・必須経路）
│   └── FelixTotal/         # 現行 felix_total への外部連携ゲートウェイ（HTTP + cross_auth）
├── Utils/                  # ユーティリティクラス（ステートレス・static のみ）
│   ├── Format.php
│   └── StatusLabel.php
├── Helpers/                # ヘルパークラス（コンテキスト依存・状態あり・インスタンス化）
│   └── SampleHelper.php
├── Models/                 # Eloquent モデル
│   └── Concerns/           # モデル横断の振る舞い（トレイト）
│       └── HasBlameColumns.php   # created_by / updated_by の自動記録
└── Providers/
    ├── AppServiceProvider.php
    └── RepositoryServiceProvider.php   # interface ⇔ 実装のバインド
routes/
├── web.php                 # Inertia ページのルート
└── console.php
```

## 3. データアクセス（Repository パターン）

**Eloquent モデルへのアクセスは、必ず Repository クラスを経由すること。**
Controller / Service / Action / FormRequest など、Repository 以外の層から
`Model::query()` / `Model::find()` / `Model::where()` 等を直接呼び出すことを禁止する。

### 3.1 目的・方針

- データ取得・永続化のロジックを一箇所に集約し、再利用とテスト容易性を高める。
- 上位層（Controller / Service）を永続化の詳細（Eloquent / クエリ）から切り離す。
- 各 Repository は **インターフェース（Contract）** を定義し、Service Provider で実装をバインドする。これにより差し替え・モック化を可能にする。
- 1 集約（≒テーブル / 主要モデル）につき 1 Repository を基本とする。

### 3.2 インターフェース

`app/Repositories/Contracts/EstimateRepositoryInterface.php`:

```php
<?php

namespace App\Repositories\Contracts;

use App\Models\Estimate;
use Illuminate\Support\Collection;

interface EstimateRepositoryInterface
{
    public function findById(int $id): ?Estimate;

    /** @return Collection<int, Estimate> */
    public function search(array $conditions): Collection;

    public function store(array $attributes): Estimate;
}
```

### 3.3 実装

`app/Repositories/EstimateRepository.php`:

```php
<?php

namespace App\Repositories;

use App\Models\Estimate;
use App\Repositories\Contracts\EstimateRepositoryInterface;
use Illuminate\Support\Collection;

class EstimateRepository implements EstimateRepositoryInterface
{
    public function findById(int $id): ?Estimate
    {
        return Estimate::with('units.companies')->find($id);
    }

    /** @return Collection<int, Estimate> */
    public function search(array $conditions): Collection
    {
        return Estimate::query()
            ->when($conditions['keyword'] ?? null, fn ($q, $kw) => $q->where('name', 'like', "%{$kw}%"))
            ->with('units')
            ->get();
    }

    public function store(array $attributes): Estimate
    {
        return Estimate::create($attributes);
    }
}
```

### 3.4 バインドと利用

`app/Providers/RepositoryServiceProvider.php` で interface と実装を紐付ける。

```php
// RepositoryServiceProvider::register()
$this->app->bind(
    \App\Repositories\Contracts\EstimateRepositoryInterface::class,
    \App\Repositories\EstimateRepository::class,
);
```

**Repository を利用するのは Service 層**であり、Controller は Repository を直接注入しない。
Service はコンストラクタインジェクションで **インターフェース**を受け取る。

```php
class EstimateService
{
    public function __construct(
        private readonly EstimateRepositoryInterface $estimates,
    ) {}

    /** @param array<string, mixed> $filters */
    public function searchForStatusManagement(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->estimates->search($filters, $perPage);
    }
}
```

Controller は **Service** を受け取り、データアクセスを委譲する（Controller → Service → Repository）。

```php
class StatusManagementController extends Controller
{
    public function __construct(
        private readonly EstimateService $estimateService,
    ) {}

    public function index(SearchEstimateRequest $request): \Inertia\Response
    {
        $paginator = $this->estimateService->searchForStatusManagement($request->filters(), $perPage);

        return Inertia::render('StatusManagement/Index', [
            'estimates' => EstimateResource::collection($paginator->getCollection()),
            // pagination / filters / options は省略
        ]);
    }
}
```

> Service は concrete クラスのためコンテナが自動解決する（バインド不要）。
> 新しい Repository を追加したら `RepositoryServiceProvider` に bind を追記し、`bootstrap/providers.php` への登録を確認すること。

### 3.5 外部システム連携（felix_total の見積依頼）

新スキーマ（`BuildingQuotationRepository`）には、見積依頼の履歴を保存するテーブルが存在しない
（旧スキーマの `estimate_order_histories` 相当が無い）。また見積依頼は **トークン発行・依頼履歴作成・
業者へのメール送信** を伴う複合処理であり、その実体は現行 felix_total（laravel-admin）の
`EstimateCustomDetailController@order_estimate` が唯一の正である。

したがって新スキーマ画面の「見積依頼送信」は、**felix_total の `order_estimate` を再実装せず、
サーバ間 HTTP でそのまま実行する**。窓口は外部連携ゲートウェイ
`app/Services/FelixTotal/FelixTotalQuoteRequestGateway.php`（concrete・自動解決）に集約する。

- **永続化境界としての位置づけ**: 新スキーマでは「見積依頼を記録する」＝「felix_total を呼ぶ」である。
  そのため Repository（`BuildingQuotationRepository::recordQuoteRequests`）からゲートウェイを呼ぶ。
  ゲートウェイ自身は Eloquent に触れず、HTTP と認証のみを担う（モデルアクセスは Repository に限る方針を維持）。
- **ID 写像**: チェックされた `t_cost_quotations.id` を、`source_id`（旧 `estimate_unit_companies.id`）と
  その費用 `t_building_cost_items.source_id`（旧 `estimate_units.id`）へ写像し、
  `estimate_unit_ids = "{estimate_units.id}:{estimate_unit_companies.id}"` 形式で渡す
  （`estimate_id=""` を渡すと felix_total 側がユニットの案件で自動グルーピングする）。
  `source_id` を持たない（移行されていない）見積先は依頼対象外として除外する。
- **認証**: cross_auth クッキー（`v1.{adminId}.{exp}.{HMAC}` 平文＋署名）をサーバ側で発行して
  `Cookie` ヘッダに付与する。felix_total 側の `CrossAuthCookie` ミドルウェアが admin セッションを復元する。
  クッキー値の生成は `CrossAuthCookie::mintValue()` に一本化する（署名仕様の二重定義を避ける）。
- **一覧表示**: 見積依頼画面の対象行は「`source_id` があり、かつ旧 `estimate_order_histories` に
  依頼履歴が無い見積先」。履歴の有無で「未依頼」を判定する（felix_total と同じ依拠）。
- **失敗時**: ゲートウェイは URL 未設定・未ログイン・HTTP 失敗で `RuntimeException` を投げ、
  Controller がエラーのフラッシュメッセージへ変換する。
- 連携先パスは `config('services.felix_total.quote_request_path')`（既定
  `/admin/estimates-custom-detail/order_estimate`、`FELIX_TOTAL_QUOTE_REQUEST_PATH` で上書き可）。

### 3.6 作成者 / 更新者（`created_by` / `updated_by`）の自動記録

新見積管理系のテーブルは、`created_at` / `updated_at` と対になる形で
**`created_by`（作成者 ID）/ `updated_by`（更新者 ID）** を持つ（値は `admin_users.id`・いずれも nullable）。
マイグレーションは felix_total 側に存在する（fix は同一 MySQL を default 接続で参照する）。

対象テーブル: `t_buildings` / `t_building_cost_items` / `t_building_cost_item_groups` /
`t_building_group_statuses` / `t_cost_quotations` / `t_cost_quotation_histories` /
`t_cost_quotation_details` / `t_cost_quotation_requests` / `t_approval_requests` /
`t_approval_actions` / `t_comments` / `t_comment_read_timestamps` / `t_attachments`

- **押印は Eloquent のモデルイベントで自動化する。** 各層（Service / Repository）で個別に
  `created_by` を組み立てない。`app/Models/Concerns/HasBlameColumns` を対象モデルに `use` すると、
  `creating` で `created_by` / `updated_by`、`updating` で `updated_by` を操作者で埋める。
  呼び出し側が明示的に値を入れている場合は上書きしない。
- **操作者の解決は `App\Utils\Blame` に一本化する**（`Auth::guard('admin')->id()`）。
  未ログイン（バッチ・コンソール実行）では `null` のままとし、押印しない。
- **一括更新はモデルイベントが発火しない。** `Model::query()->update([...])` を使う Repository は
  `Blame::stampUpdate()` で `updated_by` を明示的に差し込む。
- `created_by` / `updated_by` は **`$fillable` に含めない**（外部入力による詐称を防ぐため。
  押印はモデルイベント側の責務とする）。
- **新しいモデルを追加したら本トレイトの `use` を忘れないこと**（列を持つテーブルであれば必須）。

```php
// app/Models/TCostQuotation.php
class TCostQuotation extends Model
{
    use HasBlameColumns, HasFactory;
}
```

```php
// Repository：一括更新は明示的に押印する
return TCostQuotation::query()
    ->whereIn('id', $ids)
    ->where('approval_status', $from)
    ->update(Blame::stampUpdate(['approval_status' => $to]));
```

> **felix_total 側**（現行システム）も同じ新テーブルへ同期書き込みを行う
> （`NewQuotationRegisterService` 等）。そちらは laravel-admin の `Admin::user()` を用いる
> `App\Traits\BlameableTrait`（felix_total リポジトリ）で同等の押印を行う。仕様は本節と揃える。

## 4. レスポンス整形（JsonResource）

**フロント（Inertia props）へ渡すデータは、必ず JsonResource を経由して整形すること。**
Eloquent モデルやコレクションをそのまま `Inertia::render()` の props に渡さない。

### 4.1 目的・方針

- API/props として公開する **フィールドを明示的に制御**する（不要・機密カラムの漏洩防止）。
- 日付フォーマットや表示用の派生値など、**プレゼンテーション都合の整形をここに集約**する（フロントではロジックを持たない方針＝`frontend.md` 4.2 と対応）。
- `App\Http\Resources` に配置し、`XxxResource`（単数）/ コレクションは `XxxResource::collection(...)` を用いる。
- フロントの型定義（`resources/js/types/`）と **キー・型を一致**させる。

### 4.2 実装例

`app/Http/Resources/EstimateResource.php`:

```php
<?php

namespace App\Http\Resources;

use App\Models\Estimate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Estimate */
class EstimateResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'title' => $this->title,
            'units' => EstimateUnitResource::collection($this->whenLoaded('units')),
        ];
    }
}
```

```php
return Inertia::render('StatusManagement/Index', [
    'estimates' => EstimateResource::collection($estimates),
]);
```

## 5. 共通処理クラス（ユーティリティ / ヘルパー）

層をまたいで再利用する補助処理は、性質に応じて **ユーティリティクラス**と
**ヘルパークラス**に分けて配置する。グローバル関数（`helpers.php`）や各クラスへの
重複実装は避ける。どちらも **業務ルール（判断・業務手順・データアクセス）は持たない**
（それは Service / Action / Repository の責務）。

### 5.1 使い分け

| 種類 | 役割 | 状態 | 呼び出し | 置き場所 / namespace | 例 |
| --- | --- | --- | --- | --- | --- |
| **ユーティリティクラス** | 広範な汎用タスク | 持たない（ステートレス） | `static` で直接 | `app/Utils/`（`App\Utils`） | `Format` / `StatusLabel` |
| **ヘルパークラス** | 特定のコンテキスト/機能に密接した補助 | 持ってよい（コンテキスト依存） | インスタンス化して使う | `app/Helpers/`（`App\Helpers`） | `XxxHelper` |

判断基準: **状態を持たず汎用** → ユーティリティ（`Support`）。
**特定のコンテキスト（あるモデル・画面・処理）に紐づき、状態を持つ** → ヘルパー（`Helpers`）。

### 5.2 ユーティリティクラス（`app/Utils/`）

- **ステートレス**。状態を持たず、入力に対して同じ出力を返す純粋な処理。
- メソッドは **`static`**。`Format::yen(1000)` のように直接呼ぶ（DI 不要・副作用なし）。
- 1 クラス 1 関心。整形系は `Format`、ステータス解決は `StatusLabel` のように責務で分割する。
- すべてのメソッドに引数型・戻り値型を付与する。

```php
<?php

namespace App\Utils;

class Format
{
    /** 金額を「¥1,234,567」形式に整形する。 */
    public static function yen(int|float|null $value): string
    {
        return $value === null ? '' : '¥' . number_format((float) $value);
    }
}
```

```php
use App\Utils\Format;

$label = Format::yen($unit->master_price); // "¥1,200,000"
```

### 5.3 ヘルパークラス（`app/Helpers/`）

- 特定のコンテキスト（あるモデル/画面/処理）に**密接に関連する補助的役割**を担う。
- **状態（インスタンス変数）を持ってよい**。コンストラクタでコンテキストを受け取り、インスタンスメソッドで提供する。
- **インスタンス化して使う**（`new XxxHelper($context)` もしくは DI）。`static` の寄せ集めにしない。
- あくまで補助（整形・組み立て）。**業務ルールやデータアクセスは持たない**（→ Service / Repository）。
- クラス名は `XxxHelper`。すべてのメソッドに引数型・戻り値型を付与する。

```php
<?php

namespace App\Helpers;

use App\Models\Estimate;

/**
 * 1 件の実行予算に紐づく表示補助（コンテキスト = 対象 Estimate）。
 */
class EstimateViewHelper
{
    public function __construct(
        private readonly Estimate $estimate,
    ) {}

    /** 対象案件の表示用タイトル。 */
    public function title(): string
    {
        return "#{$this->estimate->id} {$this->estimate->name}";
    }
}
```

```php
use App\Helpers\EstimateViewHelper;

$title = (new EstimateViewHelper($estimate))->title(); // "#123 ●○マンション"
```

> 表示専用の整形は JsonResource から呼び出して再利用してよい（例: Resource で `Format::yen(...)`）。
> ユーティリティ／ヘルパーが業務判断を持ち始めたら、それは Service / Action へ移すサイン。

## 6. Inertia レスポンス

- 画面遷移を伴うレスポンスは `Inertia::render()` を返す。
- ページ名はフロントの `resources/js/pages/` と 1:1 で対応させる。

```php
use Inertia\Inertia;

public function index(): \Inertia\Response
{
    return Inertia::render('StatusManagement/Index', [
        'estimates' => EstimateResource::collection($estimates),
    ]);
}
```

- props はできるだけ整形済みデータ（Resource）で渡す。
- 共有データ（認証ユーザー等）は `HandleInertiaRequests` ミドルウェアの `share()` で定義する。

## 7. Wayfinder（型付きルート生成）

- `laravel/wayfinder` を導入し、コントローラ/ルート定義からフロント向けの型付きアクションを生成する。
- 生成物はフロント側 `resources/js/actions/` `resources/js/routes/` に出力され、`useForm` 等から利用する（`frontend.md` 4.5/4.6 参照）。
- ルート/コントローラの引数・メソッドを変更したら、Wayfinder の再生成を行う。

## 8. バリデーション

- 入力検証は **FormRequest** に定義する。コントローラ内のインライン `validate()` は避ける。
- 認可は FormRequest の `authorize()` または Policy で行う。

```php
class StoreEstimateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title'  => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:0'],
        ];
    }
}
```

## 9. コーディング規約

- PHP 8.3 の機能（型宣言・enum・readonly 等）を積極的に利用する。
- すべてのメソッドに **引数型・戻り値型**を付与する。
- フォーマットは **laravel/pint** に従う（`./vendor/bin/pint`）。
- 命名規約:
    - コントローラ `XxxController`、FormRequest `StoreXxxRequest`/`UpdateXxxRequest`、モデルは単数 PascalCase。
    - Repository インターフェース `XxxRepositoryInterface`、実装 `XxxRepository`。
    - リソース `XxxResource`、共通関数クラスは責務名（`Format`, `DateHelper` 等）。
- ControllerからRepositoryクラスは呼ばない。サービスクラスを経由すること。

## 10. テスト

- `phpunit`（`^12.5`）を使用。
- Feature テストで Inertia レスポンス（ページ名・props）を検証する。

```php
$this->get(route('estimates.index'))
    ->assertInertia(fn ($page) => $page
        ->component('StatusManagement/Index')
        ->has('estimates')
    );
```

## 11. 現状からの移行チェックリスト

- [ ] `inertiajs/inertia-laravel` を `composer require`
- [ ] `laravel/wayfinder` を `composer require`
- [ ] `HandleInertiaRequests` ミドルウェアを追加・登録
- [ ] ルートデフォルトの Blade 直返しを `Inertia::render()` へ移行
- [ ] FormRequest によるバリデーション体系を整備
- [ ] `Repositories/`（Contracts 含む）と `RepositoryServiceProvider` を整備し、モデル直アクセスを排除
- [ ] `Http/Resources/`（JsonResource）でレスポンス整形を統一
- [ ] `Support/`（共通関数クラス）に横断的ヘルパを集約

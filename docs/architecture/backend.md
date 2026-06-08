# バックエンドアーキテクチャ

> 対象: `new_felix_total` のバックエンド層（Laravel 13 / PHP 8.3）。
> 実装前に必ず本ドキュメントを確認すること。

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
- ドメインロジックは **Action / Service クラス**へ切り出す。
- バリデーションは **FormRequest** に集約する。
- **DB アクセスは必ず Repository クラスを経由する**。Controller / Service / Action から Eloquent モデル（`Model::query()` / `Model::find()` 等）を直接呼ばない（→ [3. データアクセス](#3-データアクセスrepository-パターン)）。
- レスポンスに渡すデータは **JsonResource** で整形する（→ [4. レスポンス整形](#4-レスポンス整形jsonresource)）。
- ヘルパ的な共通処理は **共通関数クラス（Support）** に集約する（→ [5. 共通関数クラス](#5-共通関数クラスsupport--helper)）。

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
├── Services/               # 複数手順のドメインロジック（任意）
├── Support/                # 共通関数クラス（ステートレスなヘルパ）
│   └── Format.php
├── Models/                 # Eloquent モデル
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

`app/Providers/RepositoryServiceProvider.php` で interface と実装を紐付け、
利用側はコンストラクタインジェクションで **インターフェース**を受け取る。

```php
// RepositoryServiceProvider::register()
$this->app->bind(
    \App\Repositories\Contracts\EstimateRepositoryInterface::class,
    \App\Repositories\EstimateRepository::class,
);
```

```php
class StatusManagementController extends Controller
{
    public function __construct(
        private readonly EstimateRepositoryInterface $estimates,
    ) {}

    public function index(): \Inertia\Response
    {
        $estimates = $this->estimates->search(request()->only('keyword'));

        return Inertia::render('StatusManagement/Index', [
            'estimates' => EstimateResource::collection($estimates),
        ]);
    }
}
```

> 新しい Repository を追加したら `RepositoryServiceProvider` に bind を追記し、`bootstrap/providers.php` への登録を確認すること。

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

## 5. 共通関数クラス（Support / Helper）

複数の層・機能から再利用する **汎用処理**は、`app/Support/` 配下の
**共通関数クラス**に集約する。グローバル関数（`helpers.php`）や各クラスへの
重複実装は避ける。

### 5.1 方針

- **ステートレス**であること。状態を持たず、入力に対して同じ出力を返す純粋な処理を基本とする。
- メソッドは **`static`** で公開し、`Format::yen(1000)` のように呼び出す（DI 不要・副作用なしのもの）。状態や依存注入が必要な処理は Service 層に置く。
- 1 クラス 1 関心。整形系は `Format`、日付系は `DateHelper` のように責務で分割する。
- ドメインロジック（業務ルール）はここに置かない。あくまで横断的・技術的なユーティリティに限定する。
- すべてのメソッドに引数型・戻り値型を付与する。

### 5.2 実装例

`app/Support/Format.php`:

```php
<?php

namespace App\Support;

class Format
{
    /** 金額を「¥1,234,567」形式に整形する。 */
    public static function yen(int|float|null $value): string
    {
        return $value === null ? '' : '¥' . number_format((float) $value);
    }

    /** null/空文字を「—」に置き換える。 */
    public static function dash(?string $value): string
    {
        return ($value === null || $value === '') ? '—' : $value;
    }
}
```

```php
use App\Support\Format;

$label = Format::yen($unit->master_price); // "¥1,200,000"
```

> 表示専用の整形は JsonResource 内から共通関数クラスを呼び出して再利用してよい
> （例: `EstimateResource` で `Format::yen(...)`）。

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

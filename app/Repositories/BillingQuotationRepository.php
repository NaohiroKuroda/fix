<?php

namespace App\Repositories;

use App\Models\AdminUser;
use App\Models\TBillingPartner;
use App\Models\TBillingQuotation;
use App\Models\TBillingQuotationDetail;
use App\Models\TBuilding;
use App\Models\TBuildingBudgetItem;
use App\Repositories\Contracts\BillingRepositoryInterface;
use App\Utils\Blame;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * 請求（もらい）系画面のデータ取得・更新。
 *
 * 支払（はらい）系の {@see BuildingQuotationRepository} と対になる。共通の約束事
 * （区分「全て」のときの絞り込み適用範囲・行キー・コメントメタ）は
 * docs/detailed-design/quotations/00_共通仕様_詳細設計.md を参照。
 *
 * 起点は `t_billing_partners`。見積額は `t_billing_quotations`（`is_latest`）の
 * `amount_excluding_tax`、業者の承諾日時は同テーブルの `accepted_at`。
 */
class BillingQuotationRepository implements BillingRepositoryInterface
{
    /**
     * 画面モード → **操作できる** 承認ステータス（処理フロー J列「表示承認ステータス」）。
     * 一覧にはこれ以外のステータスも出す（K列＝出すが操作させない）。
     *
     * @var array<string, list<string>>
     */
    private const MODE_OPERABLE_STATUS = [
        'billing-quote-create' => ['DRAFT', 'CANCELLED'],   // 未申請 / 取消承認済（差し戻し）
        'billing-quote-approval' => ['APPLIED'],            // 申請中（承認待ち）
        'billing-cancel-request' => ['APPROVED'],           // 承認済（かつ業者承諾なし）
        'billing-cancel-approval' => ['CANCEL_APPLIED'],    // 取消申請中
        'billing-order-confirmation' => ['APPROVED'],       // 承認済（かつ業者承諾あり）
    ];

    /**
     * 一覧を空で返す画面。
     *
     * 【請求】発注書確認は発注（Order）を起点にする画面で、**新しい発注テーブルの作成待ち**。
     * それまでは中途半端なデータを出さないよう、一覧を空にしておく
     * （→ docs/detailed-design/orders/02_請求_発注書確認_詳細設計.md）。
     *
     * @var list<string>
     */
    private const MODE_PENDING_TABLE = ['billing-order-confirmation'];

    /** 画面の既定の区分（処理フロー H列「区分が請求」）。 */
    private const DEFAULT_KIND = 'billing';

    public function forBillingManagement(array $filters, int $perPage, string $mode): LengthAwarePaginator
    {
        $keyword = $this->nonEmpty($filters['keyword'] ?? null);     // 実行予算名（t_buildings.name）
        $itemLabel = $this->nonEmpty($filters['itemLabel'] ?? null); // 項目名（t_building_budget_items.name）
        $vendor = $this->nonEmpty($filters['vendor'] ?? null);       // 請求先（company_name）
        $comment = (string) ($filters['comment'] ?? 'all');          // コメント有無（all/has/none）
        // 区分。billing=請求のみ / payable=支払のみ / all=両方を同じ一覧に並べる。
        // 請求系画面の既定は billing。all のとき支払行は表示のみ（操作不可）。
        $kind = (string) ($filters['kind'] ?? self::DEFAULT_KIND);
        if (! in_array($kind, ['all', 'payable', 'billing'], true)) {
            $kind = self::DEFAULT_KIND;
        }
        /** @var list<string> $relations 読み込む取引先リレーション（all は両方）。 */
        $relations = array_values(array_filter([
            $kind !== 'payable' ? 'billingPartners' : null,
            $kind !== 'billing' ? 'payablePartners' : null,
        ]));
        // 本リポジトリは請求系画面のもの。自区分＝請求（t_billing_partners）。
        $screenIsBilling = true;
        $ownRelation = 'billingPartners';
        // 取引先レベルの絞り込みを効かせる区分。**自区分だけに効かせる**（共通仕様 §3.3）。
        // 逆区分は「表示のみ」なので絞り込まず、自区分が残った項目にだけ並べる。
        $filterRelation = in_array($ownRelation, $relations, true) ? $ownRelation : ($relations[0] ?? null);

        $operable = self::MODE_OPERABLE_STATUS[$mode] ?? null;
        // 未知の mode、および発注テーブル待ちの画面は空で返す。
        $empty = $operable === null || in_array($mode, self::MODE_PENDING_TABLE, true);

        // 取引先の絞り込み：画面の初期表示条件（H列）＋ 請求先名。
        $makeFilter = fn (bool $isBilling): callable => function (Builder $q) use ($mode, $vendor, $isBilling): void {
            if ($isBilling) {
                $this->applyScopeFilter($q, $mode);
            }
            if ($vendor !== false) {
                $q->whereHas('company', fn (Builder $c) => $c->where('company_name', 'like', "%{$vendor}%"));
            }
        };
        $passThrough = static function (Builder $q): void {};
        $filterFor = [
            'billingPartners' => $filterRelation === 'billingPartners' ? $makeFilter(true) : $passThrough,
            'payablePartners' => $filterRelation === 'payablePartners' ? $makeFilter(false) : $passThrough,
        ];

        $paginator = TBuilding::query()
            ->when($empty, fn (Builder $q) => $q->whereRaw('1 = 0'))
            ->when($keyword, fn (Builder $q, string $kw) => $q->where('name', 'like', "%{$kw}%"))
            ->whereHas('budgetItems', fn (Builder $i) => $this->applyItemFilter($i, $itemLabel, $comment, $filterFor, $filterRelation))
            ->with(['budgetItems' => function (HasMany $i) use ($itemLabel, $comment, $filterFor, $relations, $filterRelation): void {
                $this->applyItemFilter($i->getQuery(), $itemLabel, $comment, $filterFor, $filterRelation);
                $i->orderBy('sort')->orderBy('id');
                foreach ($relations as $relation) {
                    $filter = $filterFor[$relation];
                    $i->with([$relation => function (HasMany $q) use ($filter, $relation): void {
                        $filter($q->getQuery());
                        $q->orderBy('id')->with('company:id,company_name');
                        // 見積本体は請求側にしかない。明細は見積修正モーダルの初期値に使う。
                        if ($relation === 'billingPartners') {
                            $q->with(['latestQuotation.details' => fn ($d) => $d->orderBy('id')]);
                        }
                    }]);
                }
            }])
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        foreach ($paginator as $building) {
            foreach ($building->budgetItems as $item) {
                // Resource が区分に依らず同じ名前で読めるよう、表示対象を displayPartners に寄せる。
                // all のときは請求と支払を1つの一覧に合成する（請求＝もらいを先に並べる）。
                $partners = collect();
                foreach ($relations as $relation) {
                    $isBilling = $relation === 'billingPartners';
                    foreach ($item->getRelation($relation) as $partner) {
                        // 区分（請求＝もらい / 支払＝はらい）。行の地色・バッジに使う。
                        $partner->setAttribute('billing_target', $isBilling);
                        // 操作できる行か（J列）。**画面の区分と異なる取引先は常に表示のみ**。
                        $partner->setAttribute('operable', $isBilling === $screenIsBilling
                            && in_array((string) $partner->approval_status, $operable ?? [], true));
                        $partners->push($partner);
                    }
                }
                $item->setRelation('displayPartners', $partners);
            }
        }

        // やり取り（コメント）のメタ情報（件数・コメント有無・未読数）を各取引先に付与。
        // コメントは建物予算項目単位で支払・請求共通（共通仕様 §4）。
        $this->attachCommentMeta($paginator);

        return $paginator;
    }

    /**
     * 画面の初期表示条件（処理フロー H列）を請求取引先のクエリへ適用する。
     *
     * 承認ステータスでは絞らない（K列のとおり他ステータスも一覧に出す）。ただし
     * 見積承認・見積取消申請・発注書確認は「見積の有無」「業者承諾日時の有無」で対象が変わる。
     */
    private function applyScopeFilter(Builder $q, string $mode): void
    {
        $latest = fn (Builder $h) => $h->where('is_latest', true);

        match ($mode) {
            // 見積が作成されているもの（未作成は対象外）。
            'billing-quote-approval' => $q->whereHas('quotations', $latest),
            // 承認済みで、まだ業者が承諾していないもの（承諾後は取消できない）。
            'billing-cancel-request' => $q->whereDoesntHave(
                'quotations',
                fn (Builder $h) => $h->where('is_latest', true)->whereNotNull('accepted_at'),
            ),
            // 承認済みで、業者が承諾済みのもの。
            'billing-order-confirmation' => $q->whereHas(
                'quotations',
                fn (Builder $h) => $h->where('is_latest', true)->whereNotNull('accepted_at'),
            ),
            default => null,
        };
    }

    /**
     * 項目（明細）の絞り込み：項目名 ＋ コメント有無 ＋ 自区分の取引先を持つこと。
     *
     * @param  array<string, callable>  $filterFor  リレーション名 → 絞り込みクロージャ
     * @param  string|null  $filterRelation  絞り込みを効かせる（＝項目の存在判定に使う）自区分のリレーション
     */
    private function applyItemFilter(Builder $i, string|false $itemLabel, string $comment, array $filterFor, ?string $filterRelation): Builder
    {
        if ($itemLabel !== false) {
            $i->where('name', 'like', "%{$itemLabel}%");
        }

        // コメント有無（コメントは項目=t_building_budget_items 単位のポリモーフィック）。
        if ($comment === 'has') {
            $i->whereHas('comments');
        } elseif ($comment === 'none') {
            $i->whereDoesntHave('comments');
        }

        if ($filterRelation === null) {
            return $i;
        }

        $filter = $filterFor[$filterRelation];

        return $i->whereHas($filterRelation, fn (Builder $p) => $filter($p));
    }

    /**
     * ページ内の各取引先に、コメント（t_comments）のメタ情報を付与する。
     * 支払側（{@see BuildingQuotationRepository::attachCommentMeta()}）と同じ項目単位スレッド。
     *
     * @param  LengthAwarePaginator<int, TBuilding>  $paginator
     */
    private function attachCommentMeta(LengthAwarePaginator $paginator): void
    {
        $morphType = (new TBuildingBudgetItem)->getMorphClass();

        $admin = Auth::guard('admin')->user();
        $userId = $admin instanceof AdminUser ? (int) $admin->id : 0;

        $partners = [];
        $itemIds = [];
        foreach ($paginator as $building) {
            foreach ($building->budgetItems as $item) {
                $itemIds[(int) $item->id] = true;
                foreach ($item->displayPartners as $partner) {
                    $partners[] = $partner;
                }
            }
        }
        $itemIds = array_keys($itemIds);

        $countMap = [];
        $unreadMap = [];
        $deniedMap = [];
        if ($itemIds !== []) {
            $countMap = DB::table('t_comments')
                ->where('commentable_type', $morphType)
                ->whereIn('commentable_id', $itemIds)
                ->groupBy('commentable_id')
                ->selectRaw('commentable_id as id, COUNT(*) as cnt')
                ->pluck('cnt', 'id')
                ->all();

            // 否認済み判定（新スキーマに否認理由の列が無いため、コメント本文の接頭辞で判定する）。
            $deniedMap = array_flip(array_map('intval', DB::table('t_comments')
                ->where('commentable_type', $morphType)
                ->whereIn('commentable_id', $itemIds)
                ->where('body', 'like', '【否認】%')
                ->distinct()
                ->pluck('commentable_id')
                ->all()));

            $unreadMap = DB::table('t_comments as c')
                ->leftJoin('t_comment_read_timestamps as r', function ($join) use ($morphType, $userId): void {
                    $join->on('r.readable_id', '=', 'c.commentable_id')
                        ->where('r.readable_type', '=', $morphType)
                        ->where('r.user_id', '=', $userId);
                })
                ->where('c.commentable_type', $morphType)
                ->whereIn('c.commentable_id', $itemIds)
                ->where('c.user_id', '!=', $userId)
                ->whereRaw('(r.last_read_at IS NULL OR c.created_at > r.last_read_at)')
                ->groupBy('c.commentable_id')
                ->selectRaw('c.commentable_id as id, COUNT(*) as cnt')
                ->pluck('cnt', 'id')
                ->all();
        }

        foreach ($partners as $partner) {
            $itemId = (int) $partner->building_budget_item_id;
            $count = (int) ($countMap[$itemId] ?? 0);
            $partner->setAttribute('comments_count', $count);
            $partner->setAttribute('has_comments', $count > 0);
            $partner->setAttribute('unread_count', (int) ($unreadMap[$itemId] ?? 0));
            $partner->setAttribute('denied', isset($deniedMap[$itemId]));
        }
    }

    public function masters(): array
    {
        return [
            // 拠点は felix_total の config('constant.branch_list') 相当（マスタテーブルが無いため設定値）。
            'branches' => collect((array) config('felix.branch_list', []))
                ->map(fn (string $name, int $code) => ['code' => $code, 'name' => $name])
                ->values()
                ->all(),
            'departments' => DB::table('departments')
                ->orderBy('id')
                ->get(['id', 'name'])
                ->map(fn ($r) => ['id' => (int) $r->id, 'name' => (string) $r->name])
                ->all(),
            'units' => DB::table('master_units')
                ->orderBy('id')
                ->get(['id', 'name'])
                ->map(fn ($r) => ['id' => (int) $r->id, 'name' => (string) $r->name])
                ->all(),
        ];
    }

    public function advanceStatus(array $partnerIds, string $from, string $to): int
    {
        if ($partnerIds === []) {
            return 0;
        }

        return TBillingPartner::query()
            ->whereIn('id', $partnerIds)
            ->where('approval_status', $from)
            // 一括更新はモデルイベントが発火しないため、更新者（updated_by）を明示的に押印する。
            ->update(Blame::stampUpdate(['approval_status' => $to]));
    }

    public function itemIdForPartner(int $partnerId): ?int
    {
        $itemId = TBillingPartner::query()
            ->where('id', $partnerId)
            ->value('building_budget_item_id');

        return $itemId === null ? null : (int) $itemId;
    }

    public function saveQuotation(int $partnerId, array $quotation, array $details): int
    {
        return DB::transaction(function () use ($partnerId, $quotation, $details): int {
            // 版管理：既存の最新版を落としてから新しい版を作る（履歴は残す）。
            TBillingQuotation::query()
                ->where('billing_partner_id', $partnerId)
                ->where('is_latest', true)
                ->update(Blame::stampUpdate(['is_latest' => false]));

            $amount = $this->detailTotal($details);

            // 作成者・更新者は HasBlameColumns が自動で押印する（Eloquent イベント経由）。
            $created = TBillingQuotation::query()->create([
                'billing_partner_id' => $partnerId,
                'is_latest' => true,
                'file_url' => (string) ($quotation['fileUrl'] ?? ''),
                'quotation_date' => $quotation['quotationDate'],
                'amount_excluding_tax' => $amount,
                'tax_adjust' => (int) ($quotation['taxAdjust'] ?? 0),
                'withholding_income_tax' => $quotation['withholdingIncomeTax'] ?? null,
                'comment' => (string) ($quotation['comment'] ?? ''),
            ]);

            foreach ($details as $detail) {
                TBillingQuotationDetail::query()->create([
                    'billing_quotation_id' => $created->id,
                    'is_memo' => (bool) ($detail['isMemo'] ?? false),
                    'branch_code' => $detail['branchCode'] ?? null,
                    'department_id' => $detail['departmentId'] ?? null,
                    'name' => (string) ($detail['name'] ?? ''),
                    'quantity' => $detail['quantity'] ?? null,
                    'unit_id' => $detail['unitId'] ?? null,
                    'unit_price' => $detail['unitPrice'] ?? null,
                    'tax_type' => (string) ($detail['taxType'] ?? 'TAXABLE'),
                    'tax_rate' => $detail['taxRate'] ?? '0.10',
                    'is_tax_inclusive' => (bool) ($detail['isTaxInclusive'] ?? false),
                    'price' => $detail['price'] ?? null,
                ]);
            }

            return (int) $created->id;
        });
    }

    /**
     * 明細から税抜合計を求める（メモ行は金額を持たないため除外）。
     *
     * @param  list<array<string, mixed>>  $details
     */
    private function detailTotal(array $details): int
    {
        $total = 0;
        foreach ($details as $detail) {
            if (($detail['isMemo'] ?? false) === true) {
                continue;
            }
            $total += (int) ($detail['price'] ?? 0);
        }

        return $total;
    }

    public function pendingCounts(): array
    {
        $latest = fn (Builder $h) => $h->where('is_latest', true);

        return [
            // 【請求】見積作成：まだ承認申請していない（DRAFT）。
            'billing-quote-create' => TBillingPartner::query()
                ->where('approval_status', 'DRAFT')
                ->count(),
            // 【請求】見積作成（差し戻し）：見積承認で否認され、見積作成へ戻った（CANCELLED）。
            // 新スキーマに否認理由の列が無いため、項目のコメントに「【否認】」で始まる投稿が
            // あることをもって否認済みと判定する（支払側の業者選定と同じ方法）。
            'billing-quote-create-rejected' => TBillingPartner::query()
                ->where('approval_status', 'CANCELLED')
                ->whereIn('building_budget_item_id', $this->denialItemIds())
                ->count(),
            // 【請求】見積承認：申請中（APPLIED）で、まだ承認も否認もしていない。
            'billing-quote-approval' => TBillingPartner::query()
                ->where('approval_status', 'APPLIED')
                ->whereHas('quotations', $latest)
                ->count(),
            // 【請求】見積取消承認：取消申請中（CANCEL_APPLIED）で、まだ承認も否認もしていない。
            'billing-cancel-approval' => TBillingPartner::query()
                ->where('approval_status', 'CANCEL_APPLIED')
                ->count(),
            // 【請求】見積取消申請はバッヂ対象外（常時ブラウズする画面のため）。
        ];
    }

    /**
     * 否認コメント（`【否認】` 始まり）を持つ建物予算項目の ID 一覧。
     *
     * @return list<int>
     */
    private function denialItemIds(): array
    {
        return DB::table('t_comments')
            ->where('commentable_type', (new TBuildingBudgetItem)->getMorphClass())
            ->where('body', 'like', '【否認】%')
            ->distinct()
            ->pluck('commentable_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** 値が「未指定（null / 空文字）」なら false、それ以外は文字列で返す。 */
    private function nonEmpty(mixed $value): string|false
    {
        if ($value === null || $value === '') {
            return false;
        }

        return (string) $value;
    }
}

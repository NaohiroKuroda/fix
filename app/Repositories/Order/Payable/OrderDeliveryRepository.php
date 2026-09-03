<?php

namespace App\Repositories\Order\Payable;

use App\Models\AdminUser;
use App\Models\TBuilding;
use App\Models\TBuildingBudgetItem;
use App\Models\TPayablePartner;
use App\Repositories\Contracts\Order\Payable\OrderDeliveryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * 発注フローのデータアクセス。
 *
 * 見積管理（{@see PayableRepository}）と同じ「物件 → 項目 → 見積先」の構造で
 * 一覧を返し、各見積先に発注書（{@see \App\Models\TPayableOrder}）の状態を付与する。
 *
 * 2026-09 に旧テーブル（t_orders / t_order_approval_actions / t_delivery_reports /
 * t_delivery_report_approval_actions / t_invoices / t_invoice_approval_actions）を廃止した
 * （DB設計書に無いため）。現在扱えるのは **業者承諾確認（order-acceptance）** だけで、
 * 発注実行・発注承認・発注取消・完了確認・部長完了承認・請求取消承認は停止している。
 * これらを復活させる場合は、進行状態の持ち方を DB設計書で先に決めること。
 */
class OrderDeliveryRepository implements OrderDeliveryRepositoryInterface
{
    public function forScreen(string $mode, array $filters, int $perPage): LengthAwarePaginator
    {
        $keyword = $this->nonEmpty($filters['keyword'] ?? null);
        $itemLabel = $this->nonEmpty($filters['itemLabel'] ?? null);
        $vendor = $this->nonEmpty($filters['vendor'] ?? null);

        $quotationFilter = function (Builder $q) use ($mode, $vendor, $filters): void {
            $this->applyModeFilter($q, $mode, $filters);
            if ($vendor !== false) {
                $q->whereHas('company', fn (Builder $c) => $c->where('company_name', 'like', "%{$vendor}%"));
            }
        };

        $paginator = TBuilding::query()
            ->when($keyword, fn (Builder $q, string $kw) => $q->where('name', 'like', "%{$kw}%"))
            ->whereHas('budgetItems', fn (Builder $i) => $this->applyItemFilter($i, $itemLabel, $quotationFilter))
            ->with(['budgetItems' => function (HasMany $i) use ($itemLabel, $quotationFilter): void {
                $this->applyItemFilter($i->getQuery(), $itemLabel, $quotationFilter);
                $i->orderBy('sort')->orderBy('id')
                    ->with(['payablePartners' => function (HasMany $q) use ($quotationFilter): void {
                        $quotationFilter($q->getQuery());
                        $q->orderBy('id')->with([
                            'company:id,company_name',
                            'latestQuotation',
                            // 発注書（金額・業者の承諾日時）。業者承諾確認画面の表示元。
                            'payableOrder',
                        ]);
                    }]);
            }])
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $this->attachCommentMeta($paginator);

        return $paginator;
    }

    /**
     * mode ごとの見積先（t_payable_partners）状態フィルタ。
     *
     * @param  array<string, mixed>  $filters
     */
    private function applyModeFilter(Builder $q, string $mode, array $filters = []): void
    {
        switch ($mode) {
            // 停止中の画面。旧 t_orders / t_delivery_reports / t_invoices を条件にしていたが、
            // それらのテーブルを廃止したため絞り込めない。画面は残っているので、
            // 落とさずに「該当0件」を返す（→ クラス doc）。
            case 'order-execution':        // 発注実行
            case 'order-approval':         // 発注承認
            case 'order-cancel-request':   // 発注取消申請
            case 'order-cancel-approval':  // 発注取消承認
            case 'delivery-report':        // 完了確認
            case 'delivery-approval':      // 部長完了承認
            case 'invoice-approval':       // 請求取消承認
                $q->whereRaw('1 = 0');
                break;
            case 'order-acceptance':
                // 業者承諾確認（表示のみ）：**発注書（t_payable_orders）が発行済み**のものを出す。
                // 発注書は【支払】部長承認で発行されるため、発行済み＝発注確定として扱う
                // （status は 'ISSUED' 固定で、進行状態は持たない）。
                // 承諾の有無は発注書の請負承認日時（contract_approved_at）で判定する
                // （→ docs/detailed-design/orders/01_支払_業者承諾確認_詳細設計.md §4）。
                $acceptance = $filters['acceptance'] ?? 'pending';
                $q->whereHas('payableOrder', function (Builder $o) use ($acceptance): void {
                    if ($acceptance === 'confirmed') {
                        $o->whereNotNull('contract_approved_at');
                    } elseif ($acceptance === 'pending') {
                        $o->whereNull('contract_approved_at');
                    }
                });
                break;
            default:
                $q->whereRaw('1 = 0');
        }
    }

    /** 項目（明細）の絞り込み：項目名 ＋ mode に合う見積先を持つこと。 */
    private function applyItemFilter(Builder $i, string|false $itemLabel, callable $quotationFilter): Builder
    {
        // 現行でチェックを外した項目（use_flg → is_enabled = false）は、絞り込みに関わらず出さない
        // （共通仕様 §3.4）。ユーザーの選ぶ条件では解除できない前提条件として扱う。
        $i->where('is_enabled', true);

        if ($itemLabel !== false) {
            $i->where('name', 'like', "%{$itemLabel}%");
        }

        return $i->whereHas('payablePartners', fn (Builder $q) => $quotationFilter($q));
    }

    // ---- アクション（全て t_payable_partners.id 起点）----
    //
    // 発注実行・発注承認・発注否認・発注取消申請/承認・完了確認・部長完了承認・完了否認・請求取消承認は、
    // 旧テーブル（t_orders / t_delivery_reports / t_invoices とその承認履歴）の廃止に伴い停止した。
    // 画面・ルートは残っているため、呼ばれたら黙って成功したように見せず例外で落とす。
    // 復活させる場合は進行状態の持ち先を DB設計書で決めてから実装し直すこと（→ クラス doc）。

    public function executeOrders(array $quotationIds): int
    {
        throw $this->retired('発注実行');
    }

    public function approveOrders(array $quotationIds): int
    {
        throw $this->retired('発注承認');
    }

    public function rejectOrder(int $quotationId, string $reason): int
    {
        throw $this->retired('発注否認');
    }

    public function recordCancelRequests(array $quotationIds): int
    {
        throw $this->retired('発注取消申請');
    }

    public function recordCancelApprovals(array $quotationIds): int
    {
        throw $this->retired('発注取消承認');
    }

    public function confirmDeliveryReports(array $quotationIds): int
    {
        throw $this->retired('完了確認');
    }

    public function approveDeliveryReports(array $quotationIds): int
    {
        throw $this->retired('部長完了承認');
    }

    public function rejectDeliveryReport(int $quotationId, string $reason): int
    {
        throw $this->retired('完了否認');
    }

    public function cancelInvoices(array $quotationIds): int
    {
        throw $this->retired('請求取消承認');
    }

    /** 停止中の操作を表す例外。 */
    private function retired(string $operation): \RuntimeException
    {
        return new \RuntimeException(
            "{$operation}は停止中です。発注・完了報告・請求の進行状態を持つテーブル"
            .'（t_orders / t_delivery_reports / t_invoices）を廃止したため、この操作は実行できません。'
        );
    }

    /**
     * バッヂ集計の起点。表示対象の項目に属する支払取引先だけを数える。
     *
     * 現行でチェックを外した項目（use_flg → is_enabled = false）は一覧に出さないため、
     * バッヂの件数からも除く（共通仕様 §3.4）。
     */
    private function countablePartners(): Builder
    {
        return TPayablePartner::query()
            ->whereHas('budgetItem', fn (Builder $i) => $i->where('is_enabled', true));
    }

    /**
     * 発注管理（業者承諾確認・【請求】発注書確認）は**バッヂを出さない**
     * （見積管理_処理フローの「サイドメニューのバッヂの意味」も緑・赤とも「表示なし」）。
     * 完了・納品管理のバッヂは旧 t_delivery_reports を数えていたが、テーブル廃止に伴い停止した。
     * 本メソッドは全リクエスト（{@see \App\Http\Middleware\HandleInertiaRequests}）から
     * 呼ばれるため、落とさずに空を返す。
     */
    public function pendingCounts(): array
    {
        return [];
    }

    public function itemIdForQuotation(int $quotationId): ?int
    {
        $itemId = TPayablePartner::query()
            ->where('id', $quotationId)
            ->value('building_budget_item_id');

        return $itemId === null ? null : (int) $itemId;
    }

    // ---- 内部ヘルパ ----

    /**
     * 各見積先へコメント（t_comments）のメタ情報（件数・未読数）を付与する（見積管理と同じ）。
     *
     * @param  LengthAwarePaginator<int, TBuilding>  $paginator
     */
    private function attachCommentMeta(LengthAwarePaginator $paginator): void
    {
        $morphType = (new TBuildingBudgetItem)->getMorphClass();
        $admin = Auth::guard('admin')->user();
        $userId = $admin instanceof AdminUser ? (int) $admin->id : 0;

        $quotations = [];
        $itemIds = [];
        foreach ($paginator as $building) {
            foreach ($building->budgetItems as $item) {
                $itemIds[(int) $item->id] = true;
                foreach ($item->payablePartners as $quotation) {
                    $quotations[] = $quotation;
                }
            }
        }
        $itemIds = array_keys($itemIds);

        $countMap = [];
        $unreadMap = [];
        if ($itemIds !== []) {
            $countMap = DB::table('t_comments')
                ->where('commentable_type', $morphType)
                ->whereIn('commentable_id', $itemIds)
                ->groupBy('commentable_id')
                ->selectRaw('commentable_id as id, COUNT(*) as cnt')
                ->pluck('cnt', 'id')
                ->all();

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

        foreach ($quotations as $quotation) {
            $itemId = (int) $quotation->building_budget_item_id;
            $count = (int) ($countMap[$itemId] ?? 0);
            $quotation->setAttribute('comments_count', $count);
            $quotation->setAttribute('has_comments', $count > 0);
            $quotation->setAttribute('unread_count', (int) ($unreadMap[$itemId] ?? 0));
        }
    }

    private function currentUserId(): ?int
    {
        $admin = Auth::guard('admin')->user();

        return $admin instanceof AdminUser ? (int) $admin->id : null;
    }

    private function nonEmpty(mixed $value): string|false
    {
        if ($value === null || $value === '' || $value === 'all') {
            return false;
        }

        return (string) $value;
    }
}

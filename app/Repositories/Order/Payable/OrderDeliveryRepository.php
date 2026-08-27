<?php

namespace App\Repositories\Order\Payable;

use App\Models\AdminUser;
use App\Models\TBuilding;
use App\Models\TBuildingBudgetItem;
use App\Models\TDeliveryReport;
use App\Models\TDeliveryReportApprovalAction;
use App\Models\TInvoice;
use App\Models\TInvoiceApprovalAction;
use App\Models\TOrder;
use App\Models\TOrderApprovalAction;
use App\Models\TPayablePartner;
use App\Repositories\Contracts\Order\Payable\OrderDeliveryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * 発注〜納品フローのデータアクセス。
 *
 * 見積管理（{@see PayableRepository}）と同じ「物件 → 項目 → 見積先」の構造で
 * 一覧を返し、各見積先に発注・納品の状態を付与する。各フェーズは「担当者が実行/確認 →
 * 承認者が承認」の固定2段階。発注の取消も見積管理と同様に「取消申請 → 取消承認」の2段階。
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
                        $q->orderBy('id')->with(['company:id,company_name', 'latestQuotation', 'order.deliveryReport.invoice']);
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
            case 'order-execution': // 承認済み見積・未発注
                $q->where('approval_status', 'APPROVED')->whereDoesntHave('order');
                break;
            case 'order-approval': // 発注済み・担当実行済（部長承認待ち）
                $q->whereHas('order', fn (Builder $o) => $o->where('order_status', 'STAFF_APPROVED'));
                break;
            case 'order-cancel-request': // 発注承認済み・取消申請可能
                $q->whereHas('order', fn (Builder $o) => $o->where('order_status', 'APPROVED'));
                break;
            case 'order-cancel-approval': // 取消申請中・部長取消承認待ち
                $q->whereHas('order', fn (Builder $o) => $o->where('order_status', 'CANCEL_REQUESTED'));
                break;
            case 'order-acceptance': // 発注承認済み・業者承諾確認（acceptance フィルタで確認済/未完了/全てを切替）
                $acceptance = $filters['acceptance'] ?? 'pending';
                $q->whereHas('order', function (Builder $o) use ($acceptance): void {
                    $o->where('order_status', 'APPROVED');
                    if ($acceptance === 'confirmed') {
                        $o->whereNotNull('vendor_accepted_at');
                    } elseif ($acceptance === 'pending') {
                        $o->whereNull('vendor_accepted_at');
                    }
                });
                break;
            case 'delivery-report': // 完了確認：請求月（報告書提出日＝業者承諾日、月末17:00締め）で絞り込み
                [$billingFrom, $billingTo] = $this->billingMonthRange($filters['billingMonth'] ?? 'current');
                $q->whereHas('order', function (Builder $o) use ($billingFrom, $billingTo): void {
                    $o->whereNotNull('vendor_accepted_at')
                        ->where('vendor_accepted_at', '>', $billingFrom)
                        ->where('vendor_accepted_at', '<=', $billingTo);
                });
                break;
            case 'delivery-approval': // 報告書受領済み・部長承認待ち
                $q->whereHas('order.deliveryReport', fn (Builder $r) => $r->where('report_status', 'STAFF_APPROVED'));
                break;
            case 'invoice-approval': // 請求取消承認：作成済み請求書（未取消）全件
                $q->whereHas('order.deliveryReport.invoice', fn (Builder $i) => $i->whereIn('invoice_status', ['STAFF_APPROVED', 'APPROVED']));
                break;
            default:
                $q->whereRaw('1 = 0');
        }
    }

    /**
     * 請求月（先月/当月/来月）に対応する報告書提出日（vendor_accepted_at）の範囲を返す。
     * 「月末17:00締め」：各月の最終日17:00より後の提出は翌月扱いになる。
     *
     * @return array{0: Carbon, 1: Carbon} [下限（この時刻より後）, 上限（この時刻以下）]
     */
    private function billingMonthRange(string $billingMonth): array
    {
        $offset = match ($billingMonth) {
            'last' => -1,
            'next' => 1,
            default => 0,
        };
        $targetMonthStart = Carbon::now()->startOfMonth()->addMonthsNoOverflow($offset);
        $to = $targetMonthStart->copy()->endOfMonth()->setTime(17, 0, 0);
        $from = $targetMonthStart->copy()->subMonthNoOverflow()->endOfMonth()->setTime(17, 0, 0);

        return [$from, $to];
    }

    /** 項目（明細）の絞り込み：項目名 ＋ mode に合う見積先を持つこと。 */
    private function applyItemFilter(Builder $i, string|false $itemLabel, callable $quotationFilter): Builder
    {
        if ($itemLabel !== false) {
            $i->where('name', 'like', "%{$itemLabel}%");
        }

        return $i->whereHas('payablePartners', fn (Builder $q) => $quotationFilter($q));
    }

    // ---- アクション（全て t_payable_partners.id 起点）----

    public function executeOrders(array $quotationIds): int
    {
        $quotations = TPayablePartner::query()
            ->whereIn('id', $quotationIds)
            ->where('approval_status', 'APPROVED')
            ->whereDoesntHave('order')
            ->with('latestQuotation')
            ->get();

        foreach ($quotations as $quotation) {
            $order = TOrder::create([
                'cost_quotation_id' => $quotation->id,
                'order_status' => 'STAFF_APPROVED',
                'amount' => optional($quotation->latestQuotation)->amount_excluding_tax,
                'order_date' => Carbon::now()->toDateString(),
            ]);
            $this->action(TOrderApprovalAction::class, 'order_id', $order->id, 'STAFF', 'SELECT');
        }

        return $quotations->count();
    }

    public function approveOrders(array $quotationIds): int
    {
        $orders = $this->ordersFor($quotationIds, fn (Builder $o) => $o->where('order_status', 'STAFF_APPROVED'));
        foreach ($orders as $order) {
            $order->update(['order_status' => 'APPROVED']);
            $this->action(TOrderApprovalAction::class, 'order_id', $order->id, 'MANAGER', 'APPROVE');
        }

        return $orders->count();
    }

    public function rejectOrder(int $quotationId, string $reason): int
    {
        $order = TOrder::query()->where('cost_quotation_id', $quotationId)->where('order_status', 'STAFF_APPROVED')->first();
        if (! $order) {
            return 0;
        }
        $this->action(TOrderApprovalAction::class, 'order_id', $order->id, 'MANAGER', 'REJECT');
        $order->delete();

        return 1;
    }

    public function recordCancelRequests(array $quotationIds): int
    {
        $orders = $this->ordersFor($quotationIds, fn (Builder $o) => $o->where('order_status', 'APPROVED'));
        foreach ($orders as $order) {
            $order->update(['order_status' => 'CANCEL_REQUESTED']);
            $this->action(TOrderApprovalAction::class, 'order_id', $order->id, 'STAFF', 'CANCEL_SUBMIT');
        }

        return $orders->count();
    }

    public function recordCancelApprovals(array $quotationIds): int
    {
        $orders = $this->ordersFor($quotationIds, fn (Builder $o) => $o->where('order_status', 'CANCEL_REQUESTED'));
        foreach ($orders as $order) {
            // 取消承認＝発注を取り消す。発注を削除すると見積先は「発注実行待ち」へ戻る。
            $this->action(TOrderApprovalAction::class, 'order_id', $order->id, 'MANAGER', 'CANCEL_APPROVE');
            $order->delete();
        }

        return $orders->count();
    }

    public function recordVendorAcceptances(array $quotationIds): int
    {
        $orders = $this->ordersFor($quotationIds, fn (Builder $o) => $o->where('order_status', 'APPROVED')->whereNull('vendor_accepted_at'));
        foreach ($orders as $order) {
            $order->update(['vendor_accepted_at' => Carbon::now()]);
        }

        return $orders->count();
    }

    /**
     * 完了確認画面の「確認」：報告書を確認済みにする（確認日を記録）と同時に、
     * 請求書（TInvoice）を自動作成して部長の請求承認待ちにする。
     */
    public function confirmDeliveryReports(array $quotationIds): int
    {
        $orders = $this->ordersFor($quotationIds, fn (Builder $o) => $o->whereNotNull('vendor_accepted_at')->whereDoesntHave('deliveryReport'));
        foreach ($orders as $order) {
            $report = TDeliveryReport::create([
                'order_id' => $order->id,
                'report_status' => 'STAFF_APPROVED',
                'submitted_at' => Carbon::now(),
            ]);
            $this->action(TDeliveryReportApprovalAction::class, 'delivery_report_id', $report->id, 'STAFF', 'SELECT');

            $invoice = TInvoice::create([
                'delivery_report_id' => $report->id,
                'invoice_status' => 'STAFF_APPROVED',
                'amount' => $order->amount,
                'submitted_at' => Carbon::now(),
            ]);
            $this->action(TInvoiceApprovalAction::class, 'invoice_id', $invoice->id, 'STAFF', 'SELECT');
        }

        return $orders->count();
    }

    public function approveDeliveryReports(array $quotationIds): int
    {
        $reports = $this->reportsFor($quotationIds, fn (Builder $r) => $r->where('report_status', 'STAFF_APPROVED'));
        foreach ($reports as $report) {
            $report->update(['report_status' => 'APPROVED']);
            $this->action(TDeliveryReportApprovalAction::class, 'delivery_report_id', $report->id, 'MANAGER', 'APPROVE');
        }

        return $reports->count();
    }

    public function rejectDeliveryReport(int $quotationId, string $reason): int
    {
        $report = $this->reportsFor([$quotationId], fn (Builder $r) => $r->where('report_status', 'STAFF_APPROVED'))->first();
        if (! $report) {
            return 0;
        }
        $this->action(TDeliveryReportApprovalAction::class, 'delivery_report_id', $report->id, 'MANAGER', 'REJECT');
        $report->delete();

        return 1;
    }

    /** 請求取消承認：作成済み請求書（未取消）を取消確定する。発注取消承認と同様、請求書自体を削除する。 */
    public function cancelInvoices(array $quotationIds): int
    {
        $invoices = $this->invoicesFor($quotationIds, fn (Builder $i) => $i->whereIn('invoice_status', ['STAFF_APPROVED', 'APPROVED']));
        foreach ($invoices as $invoice) {
            $this->action(TInvoiceApprovalAction::class, 'invoice_id', $invoice->id, 'MANAGER', 'CANCEL_APPROVE');
            $invoice->delete();
        }

        return $invoices->count();
    }

    public function pendingCounts(): array
    {
        $countBy = fn (string $mode): int => TPayablePartner::query()->tap(fn (Builder $q) => $this->applyModeFilter($q, $mode))->count();

        return [
            'order-execution' => $countBy('order-execution'),
            'order-approval' => $countBy('order-approval'),
            'order-cancel-approval' => $countBy('order-cancel-approval'),
            'order-acceptance' => $countBy('order-acceptance'),
            // 完了確認画面自体は業者承諾済み全件を表示するが、バッヂは「未確認」のみをカウントする。
            'delivery-report-submission' => TPayablePartner::query()
                ->whereHas('order', fn (Builder $o) => $o->whereNotNull('vendor_accepted_at')->whereDoesntHave('deliveryReport'))
                ->count(),
            'delivery-approval' => $countBy('delivery-approval'),
            // invoice-approval（請求取消承認）はバッヂ対象外（発注取消申請と同様、常時ブラウズ可能な取消確認画面のため）。
        ];
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
     * @param  list<int>  $quotationIds
     * @return Collection<int, TOrder>
     */
    private function ordersFor(array $quotationIds, callable $extra)
    {
        if ($quotationIds === []) {
            return TOrder::query()->whereRaw('1 = 0')->get();
        }

        return TOrder::query()->whereIn('cost_quotation_id', $quotationIds)->tap($extra)->get();
    }

    /**
     * @param  list<int>  $quotationIds
     * @return Collection<int, TDeliveryReport>
     */
    private function reportsFor(array $quotationIds, callable $extra)
    {
        if ($quotationIds === []) {
            return TDeliveryReport::query()->whereRaw('1 = 0')->get();
        }

        return TDeliveryReport::query()
            ->whereHas('order', fn (Builder $o) => $o->whereIn('cost_quotation_id', $quotationIds))
            ->tap($extra)
            ->get();
    }

    /**
     * @param  list<int>  $quotationIds
     * @return Collection<int, TInvoice>
     */
    private function invoicesFor(array $quotationIds, callable $extra)
    {
        if ($quotationIds === []) {
            return TInvoice::query()->whereRaw('1 = 0')->get();
        }

        return TInvoice::query()
            ->whereHas('deliveryReport.order', fn (Builder $o) => $o->whereIn('cost_quotation_id', $quotationIds))
            ->tap($extra)
            ->get();
    }

    /** 承認履歴を1件記録する。 */
    private function action(string $modelClass, string $fk, int $id, string $step, string $type): void
    {
        $modelClass::create([
            $fk => $id,
            'step_name' => $step,
            'action_type' => $type,
            'operator_id' => $this->currentUserId() ?? 0,
            'action_at' => Carbon::now(),
        ]);
    }

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

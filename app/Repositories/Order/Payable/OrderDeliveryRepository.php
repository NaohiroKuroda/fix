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
use App\Models\TPayableOrder;
use App\Models\TPayableOrderDetail;
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
                        $q->orderBy('id')->with([
                            'company:id,company_name',
                            'latestQuotation',
                            'order.deliveryReport.invoice',
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
            case 'order-acceptance':
                // 業者承諾確認（表示のみ）：発注承認済み（t_orders.order_status）で、
                // **発注書（t_payable_orders）が発行済み**のものを出す。
                // 承諾の有無は発注書の請負承認日時（contract_approved_at）で判定する
                // （→ docs/detailed-design/orders/01_支払_業者承諾確認_詳細設計.md §4）。
                $acceptance = $filters['acceptance'] ?? 'pending';
                $q->whereHas('order', fn (Builder $o) => $o->where('order_status', 'APPROVED'))
                    ->whereHas('payableOrder', function (Builder $o) use ($acceptance): void {
                        if ($acceptance === 'confirmed') {
                            $o->whereNotNull('contract_approved_at');
                        } elseif ($acceptance === 'pending') {
                            $o->whereNull('contract_approved_at');
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
        // 現行でチェックを外した項目（use_flg → is_enabled = false）は、絞り込みに関わらず出さない
        // （共通仕様 §3.4）。ユーザーの選ぶ条件では解除できない前提条件として扱う。
        $i->where('is_enabled', true);

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
                'amount' => optional($quotation->latestQuotation)->subtotal_amount,
                'order_date' => Carbon::now()->toDateString(),
            ]);
            $this->action(TOrderApprovalAction::class, 'order_id', $order->id, 'STAFF', 'SELECT');
        }

        return $quotations->count();
    }

    /**
     * 発注承認。承認と同時に**発注書（t_payable_orders）を発行**する。
     * 発行した発注書が業者承諾確認画面の表示元になる（→ 01_支払_業者承諾確認_詳細設計.md §4）。
     */
    public function approveOrders(array $quotationIds): int
    {
        return DB::transaction(function () use ($quotationIds): int {
            $orders = $this->ordersFor($quotationIds, fn (Builder $o) => $o->where('order_status', 'STAFF_APPROVED'));
            foreach ($orders as $order) {
                $order->update(['order_status' => 'APPROVED']);
                $this->action(TOrderApprovalAction::class, 'order_id', $order->id, 'MANAGER', 'APPROVE');
                $this->issuePayableOrder((int) $order->cost_quotation_id);
            }

            return $orders->count();
        });
    }

    /**
     * 発注書（t_payable_orders ＋ 明細）を1件発行する。
     *
     * 金額は発注時点の最新見積（t_payable_quotations の is_latest）を写す。もらい・はらいとも
     * 「見積＝発注」で同額のため、見積の税別合計・消費税をそのまま発注書の金額にする。
     * 見積が無い取引先は発注書を作らない（payable_quotation_id が NOT NULL のため）。
     *
     * @return int 発行した件数（0=見積なし、または発行済み）
     */
    private function issuePayableOrder(int $partnerId): int
    {
        $partner = TPayablePartner::query()->with('latestQuotation.details')->find($partnerId);
        $quotation = $partner?->latestQuotation;

        if ($quotation === null || TPayableOrder::query()->where('payable_partner_id', $partnerId)->exists()) {
            return 0;
        }

        $order = TPayableOrder::create([
            'payable_quotation_id' => (int) $quotation->id,
            'payable_partner_id' => $partnerId,
            'issued_at' => Carbon::now(),
            'subtotal_amount' => (int) ($quotation->subtotal_amount ?? 0),
            'tax_amount' => (int) ($quotation->tax_amount ?? 0),
            'tax_adjust' => (int) ($quotation->tax_adjust ?? 0),
            'status' => 'ISSUED',
            'withholding_tax' => $quotation->withholding_income_tax,
        ]);

        // 明細は見積明細をそのまま写す。メモ行（is_memo）は金額を持たないため発注書には載せない。
        $details = $quotation->relationLoaded('details') ? $quotation->details : $quotation->details()->get();
        foreach ($details as $detail) {
            if ((bool) $detail->is_memo) {
                continue;
            }
            TPayableOrderDetail::create([
                'payable_order_id' => (int) $order->id,
                'name' => (string) ($detail->name ?? ''),
                // 発注明細は数量・単位・単価が NOT NULL。見積側は任意入力のため 0 で埋める。
                'quantity' => (int) ($detail->quantity ?? 0),
                'unit_id' => (int) ($detail->unit_id ?? 0),
                'unit_price' => (int) ($detail->unit_price ?? 0),
                'tax_type' => (string) ($detail->tax_type ?? 'TAXABLE'),
                'tax_rate' => $detail->tax_rate ?? '0.10',
                'is_tax_inclusive' => (bool) $detail->is_tax_inclusive,
                'price' => (int) ($detail->price ?? 0),
            ]);
        }

        return 1;
    }

    /** 発注書（t_payable_orders ＋ 明細）を取り消す（発注の否認・取消承認）。 */
    private function revokePayableOrder(int $partnerId): void
    {
        $orders = TPayableOrder::query()->where('payable_partner_id', $partnerId)->get();
        foreach ($orders as $order) {
            TPayableOrderDetail::query()->where('payable_order_id', $order->id)->delete();
            $order->delete();
        }
    }

    public function rejectOrder(int $quotationId, string $reason): int
    {
        $order = TOrder::query()->where('cost_quotation_id', $quotationId)->where('order_status', 'STAFF_APPROVED')->first();
        if (! $order) {
            return 0;
        }
        $this->action(TOrderApprovalAction::class, 'order_id', $order->id, 'MANAGER', 'REJECT');
        $this->revokePayableOrder($quotationId);
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
            // 発行済みの発注書（t_payable_orders）も併せて取り消す。
            $this->action(TOrderApprovalAction::class, 'order_id', $order->id, 'MANAGER', 'CANCEL_APPROVE');
            $this->revokePayableOrder((int) $order->cost_quotation_id);
            $order->delete();
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
     * 発注管理（発注実行・発注承認・発注取消承認・業者承諾確認・【請求】発注書確認）は
     * **バッヂを出さない**ため件数を数えない（見積管理_処理フローの「サイドメニューのバッヂの意味」も
     * 緑・赤とも「表示なし」）。ここで返すのは完了・納品管理のぶんだけ。
     */
    public function pendingCounts(): array
    {
        return [
            // 完了確認画面自体は業者承諾済み全件を表示するが、バッヂは「未確認」のみをカウントする。
            'delivery-report-submission' => $this->countablePartners()
                ->whereHas('order', fn (Builder $o) => $o->whereNotNull('vendor_accepted_at')->whereDoesntHave('deliveryReport'))
                ->count(),
            'delivery-approval' => $this->countablePartners()
                ->tap(fn (Builder $q) => $this->applyModeFilter($q, 'delivery-approval'))
                ->count(),
            // invoice-approval（請求取消承認）はバッヂ対象外（常時ブラウズ可能な取消確認画面のため）。
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

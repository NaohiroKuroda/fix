<?php

namespace App\Repositories\Contracts\Quotation\Billing;

use App\Models\TBuilding;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 請求（もらい）系画面のデータ取得・更新の入口。
 *
 * 支払（はらい）系は {@see PayableRepositoryInterface}。承認ステータスの語彙は共通
 * （DRAFT / APPLIED / APPROVED / CANCEL_APPLIED / CANCELLED）。
 */
interface BillingRepositoryInterface
{
    /**
     * 請求系画面の案件一覧（実行予算 → 項目 → 請求取引先）を取得する。
     *
     * @param  array<string, mixed>  $filters  keyword / itemLabel / vendor / comment / kind
     * @param  string  $mode  画面モード（billing-quote-create ほか）
     * @return LengthAwarePaginator<int, TBuilding>
     */
    public function forBillingManagement(array $filters, int $perPage, string $mode): LengthAwarePaginator;

    /**
     * 見積作成モーダルの選択肢（拠点 / 部署 / 単位）。
     *
     * @return array<string, mixed>
     */
    public function masters(): array;

    /**
     * 承認ステータスを進める（遷移元に一致する行だけ更新する）。
     *
     * @param  list<int>  $partnerIds  t_billing_partners.id
     * @return int 実際に更新した件数
     */
    public function advanceStatus(array $partnerIds, string $from, string $to): int;

    /**
     * 請求取引先（t_billing_partners.id）が属する建物予算項目（t_building_budget_items.id）を返す。
     */
    public function itemIdForPartner(int $partnerId): ?int;

    /**
     * 請求見積を保存する（既存の最新版は is_latest を落とし、新しい版を作る）。
     *
     * @param  array<string, mixed>  $quotation  見積ヘッダ（quotationDate / taxAdjust / withholdingIncomeTax / comment / fileUrl）
     * @param  list<array<string, mixed>>  $details  明細行
     * @return int 作成した請求見積（t_billing_quotations）の ID
     */
    public function saveQuotation(int $partnerId, array $quotation, array $details): int;

    /**
     * サイドメニューの未処理件数バッヂ（請求系）。
     *
     * @return array<string, int>
     */
    /**
     * 発注書を取り消す（見積の否認・取消承認で見積作成へ差し戻したとき）。
     *
     * @param  list<int>  $partnerIds
     * @return int 取り消した件数
     */
    public function revokeOrders(array $partnerIds): int;

    public function pendingCounts(): array;
}

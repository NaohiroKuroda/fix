<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 発注〜納品フローの各画面が使うデータアクセス。
 *
 * 見積管理と同じ「物件 → 項目 → 見積先」の構造で一覧を返し、各見積先に発注・納品の状態を付与する。
 * アクションは全て見積先ID（t_cost_quotations.id）を起点にする。
 *
 * mode は画面名：order-execution / order-approval / order-cancel-request /
 * order-cancel-approval / order-acceptance / delivery-report / delivery-approval。
 */
interface OrderDeliveryRepositoryInterface
{
    /**
     * 画面（mode）ごとの対象を物件ネスト構造でページネーションして返す。
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, \App\Models\TBuilding>
     */
    public function forScreen(string $mode, array $filters, int $perPage): LengthAwarePaginator;

    /**
     * 発注実行：承認済み見積（未発注）から発注を作成する。
     *
     * @param  list<int>  $quotationIds
     */
    public function executeOrders(array $quotationIds): int;

    /**
     * 発注承認：担当実行済（STAFF_APPROVED）の発注を承認する。
     *
     * @param  list<int>  $quotationIds
     */
    public function approveOrders(array $quotationIds): int;

    /** 発注の否認（担当実行済 → 発注を取り消して発注実行へ戻す）。 */
    public function rejectOrder(int $quotationId, string $reason): int;

    /**
     * 発注取消申請：発注承認済み（APPROVED）の発注を取消申請中にする。
     *
     * @param  list<int>  $quotationIds
     */
    public function recordCancelRequests(array $quotationIds): int;

    /**
     * 発注取消承認：取消申請中（CANCEL_REQUESTED）の発注を承認して取り消す。
     *
     * @param  list<int>  $quotationIds
     */
    public function recordCancelApprovals(array $quotationIds): int;

    /**
     * 業者承諾記録：承認済み発注に承諾日時を記録する。
     *
     * @param  list<int>  $quotationIds
     */
    public function recordVendorAcceptances(array $quotationIds): int;

    /**
     * 報告書受領の確認：承諾済み発注に対して納品報告を作成する（納品承認申請）。
     *
     * @param  list<int>  $quotationIds
     */
    public function confirmDeliveryReports(array $quotationIds): int;

    /**
     * 部長完了承認：担当申請済（STAFF_APPROVED）の納品報告を承認する。
     *
     * @param  list<int>  $quotationIds
     */
    public function approveDeliveryReports(array $quotationIds): int;

    /** 納品報告の否認（担当申請済 → 報告書を取り消して報告書受領へ戻す）。 */
    public function rejectDeliveryReport(int $quotationId, string $reason): int;

    /**
     * 請求取消承認：作成済み請求書（未取消）を取消確定する（請求書を削除）。
     *
     * @param  list<int>  $quotationIds
     */
    public function cancelInvoices(array $quotationIds): int;

    /**
     * サイドメニューのバッヂ用：各画面の未処理件数（見積先＝t_cost_quotations 単位）。
     *
     * @return array<string, int>
     */
    public function pendingCounts(): array;

    /**
     * 見積先（t_cost_quotations.id）が属する費用項目（t_building_cost_items.id）を返す。
     * 取消申請の理由コメントを投稿する項目を特定するために使う。
     */
    public function itemIdForQuotation(int $quotationId): ?int;
}

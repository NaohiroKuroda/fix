<?php

namespace App\Services\Quotation;

use App\Models\TBuilding;
use App\Repositories\Contracts\QuotationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 部長承認（manager-approval）画面のユースケース。
 */
class ManagerApprovalService
{
    public function __construct(
        private readonly QuotationRepositoryInterface $estimates,
        private readonly QuotationCommentService $comments,
    ) {}

    /**
     * 部長承認画面の案件一覧（選定済みで未承認の見積先）を取得する。
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, TBuilding>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->estimates->forEstimateManagement($filters, $perPage, 'manager-approval');
    }

    /**
     * 部長承認の実行（選択した見積先を承認）。
     *
     * @param  list<int>  $companyIds
     * @return int 実際に承認した件数
     */
    public function confirm(array $companyIds): int
    {
        return $this->estimates->recordManagerApprovals($companyIds);
    }

    /**
     * 否認（業者選定へ差し戻し）。対象の見積先を未選定へ戻し、否認理由を記録する。
     * 併せて、否認理由を項目のやり取り（コメント）へ部長発言として残す。
     *
     * @return int 実際に差し戻した件数
     */
    public function reject(int $companyId, string $reason): int
    {
        $count = $this->estimates->rejectManagerApproval($companyId, $reason);

        if ($count > 0) {
            // 否認理由を項目（t_building_cost_items）単位のコメントスレッドへ残す（投稿者＝操作した部長）。
            $itemId = $this->estimates->itemIdForQuotation($companyId);
            if ($itemId !== null) {
                $this->comments->post($itemId, '【否認】'.$reason, []);
            }
        }

        return $count;
    }
}

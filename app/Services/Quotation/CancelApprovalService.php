<?php

namespace App\Services\Quotation;

use App\Models\TBuilding;
use App\Repositories\Contracts\QuotationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 部長取消承認（cancel-approval）画面のユースケース。
 */
class CancelApprovalService
{
    public function __construct(
        private readonly QuotationRepositoryInterface $estimates,
        private readonly QuotationCommentService $comments,
    ) {}

    /**
     * 部長取消承認画面の案件一覧（承認済み・取消申請中の見積先）を取得する。
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, TBuilding>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->estimates->forEstimateManagement($filters, $perPage, 'cancel-approval');
    }

    /**
     * 部長取消承認の実行（取消申請を承認）。
     * 併せて、取消承認の理由を項目のやり取り（コメント）へ残す。
     *
     * @param  list<int>  $companyIds
     * @return int 実際に承認した件数
     */
    public function confirm(array $companyIds, string $reason): int
    {
        $count = $this->estimates->recordCancelApprovals($companyIds);

        if ($count > 0) {
            // 対象見積先が属する項目（重複排除）へ理由コメントを残す（投稿者＝操作者）。
            $itemIds = [];
            foreach ($companyIds as $companyId) {
                $itemId = $this->estimates->itemIdForQuotation((int) $companyId);
                if ($itemId !== null) {
                    $itemIds[$itemId] = true;
                }
            }
            foreach (array_keys($itemIds) as $itemId) {
                $this->comments->post($itemId, '【取消承認】'.$reason, []);
            }
        }

        return $count;
    }
}

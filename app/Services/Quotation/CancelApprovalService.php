<?php

namespace App\Services\Quotation;

use App\Exceptions\ServiceException;
use App\Models\TBuilding;
use App\Repositories\Contracts\QuotationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

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
        try {
            return $this->estimates->forEstimateManagement($filters, $perPage, 'cancel-approval');
        } catch (\Exception $e) {
            Log::error('部長取消承認の一覧取得に失敗しました', [
                'message' => $e->getMessage(),
                'filters' => $filters,
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new ServiceException(previous: $e);
        }
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
        try {
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
        } catch (\Exception $e) {
            Log::error('部長取消承認の実行に失敗しました', [
                'message' => $e->getMessage(),
                'companyIds' => $companyIds,
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new ServiceException(previous: $e);
        }
    }

    /**
     * 取消承認の否認（取消申請の却下）。取消を認めず、部長承認済み（APPROVED）のまま据え置く。
     * 併せて、否認理由を項目のやり取り（コメント）へ `【否認】{理由}` として残す。
     *
     * @return int 実際に却下した件数（0=対象外）
     */
    public function reject(int $companyId, string $reason): int
    {
        try {
            $count = $this->estimates->rejectCancelApproval($companyId, $reason);

            if ($count > 0) {
                // 否認理由を項目単位のコメントスレッドへ残す（投稿者＝操作した部長）。
                $itemId = $this->estimates->itemIdForQuotation($companyId);
                if ($itemId !== null) {
                    $this->comments->post($itemId, '【否認】'.$reason, []);
                }
            }

            return $count;
        } catch (\Exception $e) {
            Log::error('部長取消承認の否認（却下）に失敗しました', [
                'message' => $e->getMessage(),
                'companyId' => $companyId,
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new ServiceException(previous: $e);
        }
    }
}

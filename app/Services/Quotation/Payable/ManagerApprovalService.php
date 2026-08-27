<?php

namespace App\Services\Quotation\Payable;

use App\Exceptions\ServiceException;
use App\Models\TBuilding;
use App\Repositories\Contracts\Quotation\Payable\PayableRepositoryInterface;
use App\Services\Comment\CommentService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

/**
 * 部長承認（manager-approval）画面のユースケース。
 */
class ManagerApprovalService
{
    public function __construct(
        private readonly PayableRepositoryInterface $estimates,
        private readonly CommentService $comments,
    ) {}

    /**
     * 部長承認画面の案件一覧（選定済みで未承認の見積先）を取得する。
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, TBuilding>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        try {
            return $this->estimates->forPayableManagement($filters, $perPage, 'manager-approval');
        } catch (\Exception $e) {
            Log::error('部長承認の一覧取得に失敗しました', [
                'message' => $e->getMessage(),
                'filters' => $filters,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new ServiceException(previous: $e);
        }
    }

    /**
     * 部長承認の実行（選択した見積先を承認）。
     *
     * @param  list<int>  $partnerIds
     * @return int 実際に承認した件数
     */
    public function confirm(array $partnerIds): int
    {
        try {
            return $this->estimates->recordManagerApprovals($partnerIds);
        } catch (\Exception $e) {
            Log::error('部長承認の実行に失敗しました', [
                'message' => $e->getMessage(),
                'partnerIds' => $partnerIds,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new ServiceException(previous: $e);
        }
    }

    /**
     * 否認（業者選定へ差し戻し）。対象の見積先を未選定へ戻し、否認理由を記録する。
     * 併せて、否認理由を項目のやり取り（コメント）へ部長発言として残す。
     *
     * @return int 実際に差し戻した件数
     */
    public function reject(int $partnerId, string $reason): int
    {
        try {
            $count = $this->estimates->rejectManagerApproval($partnerId, $reason);

            if ($count > 0) {
                // 否認理由を項目（t_building_budget_items）単位のコメントスレッドへ残す（投稿者＝操作した部長）。
                $itemId = $this->estimates->itemIdForPartner($partnerId);
                if ($itemId !== null) {
                    $this->comments->post($itemId, '【否認】'.$reason, []);
                }
            }

            return $count;
        } catch (\Exception $e) {
            Log::error('部長承認の否認（差し戻し）に失敗しました', [
                'message' => $e->getMessage(),
                'partnerId' => $partnerId,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new ServiceException(previous: $e);
        }
    }
}

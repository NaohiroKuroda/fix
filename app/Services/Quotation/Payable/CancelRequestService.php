<?php

namespace App\Services\Quotation\Payable;

use App\Exceptions\ServiceException;
use App\Models\TBuilding;
use App\Repositories\Contracts\Quotation\Payable\PayableRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

/**
 * 部長取消申請（cancel-request）画面のユースケース。
 */
class CancelRequestService
{
    public function __construct(
        private readonly PayableRepositoryInterface $estimates,
        private readonly CommentService $comments,
    ) {}

    /**
     * 部長取消申請画面の案件一覧（承認済みで未取消申請の見積先）を取得する。
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, TBuilding>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        try {
            return $this->estimates->forPayableManagement($filters, $perPage, 'cancel-request');
        } catch (\Exception $e) {
            Log::error('部長取消申請の一覧取得に失敗しました', [
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
     * 部長取消申請の実行（選択した見積先の取消を申請）。
     * 併せて、取消申請の理由を項目のやり取り（コメント）へ残す。
     *
     * @param  list<int>  $partnerIds
     * @return int 実際に申請した件数
     */
    public function confirm(array $partnerIds, string $reason): int
    {
        try {
            $count = $this->estimates->recordCancelRequests($partnerIds);

            if ($count > 0) {
                // 対象見積先が属する項目（重複排除）へ理由コメントを残す（投稿者＝操作者）。
                $itemIds = [];
                foreach ($partnerIds as $partnerId) {
                    $itemId = $this->estimates->itemIdForPartner((int) $partnerId);
                    if ($itemId !== null) {
                        $itemIds[$itemId] = true;
                    }
                }
                foreach (array_keys($itemIds) as $itemId) {
                    $this->comments->post($itemId, '【取消申請】'.$reason, []);
                }
            }

            return $count;
        } catch (\Exception $e) {
            Log::error('部長取消申請の実行に失敗しました', [
                'message' => $e->getMessage(),
                'partnerIds' => $partnerIds,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new ServiceException(previous: $e);
        }
    }
}

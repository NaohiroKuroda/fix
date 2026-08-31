<?php

namespace App\Services\Order\Payable;

use App\Repositories\Contracts\Order\Payable\OrderDeliveryRepositoryInterface;
use App\Services\Comment\CommentService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 発注実行・発注承認・業者承諾記録（発注フェーズ3画面）のユースケース。
 * 操作単位は支払取引先ID（t_payable_partners.id）。
 */
class OrderService
{
    public function __construct(
        private readonly OrderDeliveryRepositoryInterface $repository,
        private readonly CommentService $comments,
    ) {}

    public function paginate(string $mode, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->repository->forScreen($mode, $filters, $perPage);
    }

    /** @param  list<int>  $quotationIds */
    public function execute(array $quotationIds): int
    {
        return $this->repository->executeOrders($quotationIds);
    }

    /** @param  list<int>  $quotationIds */
    public function approve(array $quotationIds): int
    {
        return $this->repository->approveOrders($quotationIds);
    }

    public function reject(int $quotationId, string $reason): int
    {
        return $this->repository->rejectOrder($quotationId, $reason);
    }

    /** @param  list<int>  $quotationIds */
    public function requestCancel(array $quotationIds): int
    {
        return $this->repository->recordCancelRequests($quotationIds);
    }

    /** @param  list<int>  $quotationIds */
    public function approveCancel(array $quotationIds): int
    {
        return $this->repository->recordCancelApprovals($quotationIds);
    }

    /**
     * 発注取消承認画面からの取消承認。実行に併せて、理由を項目のやり取り（コメント）へ残す。
     *
     * @param  list<int>  $quotationIds
     * @return int 実際に承認した件数
     */
    public function approveCancelWithReason(array $quotationIds, string $reason): int
    {
        $count = $this->repository->recordCancelApprovals($quotationIds);

        if ($count > 0) {
            // 対象見積先が属する項目（重複排除）へ理由コメントを残す（投稿者＝操作者）。
            $itemIds = [];
            foreach ($quotationIds as $quotationId) {
                $itemId = $this->repository->itemIdForQuotation($quotationId);
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

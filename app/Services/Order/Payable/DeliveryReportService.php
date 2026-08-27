<?php

namespace App\Services\Order\Payable;

use App\Repositories\Contracts\Order\Payable\OrderDeliveryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 完了確認（提出日・確認日・請求日）、部長完了承認、請求取消承認（納品〜請求フェーズ3画面）のユースケース。
 * 業者はシステムに登録しないため、担当者が「確認」して納品報告を作成する。確認と同時に請求書を自動作成する。
 */
class DeliveryReportService
{
    public function __construct(
        private readonly OrderDeliveryRepositoryInterface $repository,
    ) {}

    public function paginate(string $mode, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->repository->forScreen($mode, $filters, $perPage);
    }

    /** @param  list<int>  $quotationIds */
    public function confirm(array $quotationIds): int
    {
        return $this->repository->confirmDeliveryReports($quotationIds);
    }

    /** @param  list<int>  $quotationIds */
    public function approve(array $quotationIds): int
    {
        return $this->repository->approveDeliveryReports($quotationIds);
    }

    public function reject(int $quotationId, string $reason): int
    {
        return $this->repository->rejectDeliveryReport($quotationId, $reason);
    }

    /** @param  list<int>  $quotationIds */
    public function cancelInvoice(array $quotationIds): int
    {
        return $this->repository->cancelInvoices($quotationIds);
    }
}

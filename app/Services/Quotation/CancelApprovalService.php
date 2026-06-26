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
     *
     * @param  list<int>  $companyIds
     * @return int 実際に承認した件数
     */
    public function confirm(array $companyIds): int
    {
        return $this->estimates->recordCancelApprovals($companyIds);
    }
}

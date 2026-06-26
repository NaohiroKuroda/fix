<?php

namespace App\Services\Quotation;

use App\Models\Estimate;
use App\Repositories\Contracts\QuotationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 部長承認（manager-approval）画面のユースケース。
 */
class ManagerApprovalService
{
    public function __construct(
        private readonly QuotationRepositoryInterface $estimates,
    ) {}

    /**
     * 部長承認画面の案件一覧（選定済みで未承認の見積先）を取得する。
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Estimate>
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
}

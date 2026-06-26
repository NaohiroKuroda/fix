<?php

namespace App\Services\Quotation;

use App\Models\TBuilding;
use App\Repositories\Contracts\QuotationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 部長取消申請（cancel-request）画面のユースケース。
 */
class CancelRequestService
{
    public function __construct(
        private readonly QuotationRepositoryInterface $estimates,
    ) {}

    /**
     * 部長取消申請画面の案件一覧（承認済みで未取消申請の見積先）を取得する。
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, TBuilding>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->estimates->forEstimateManagement($filters, $perPage, 'cancel-request');
    }

    /**
     * 部長取消申請の実行（選択した見積先の取消を申請）。
     *
     * @param  list<int>  $companyIds
     * @return int 実際に申請した件数
     */
    public function confirm(array $companyIds): int
    {
        return $this->estimates->recordCancelRequests($companyIds);
    }
}

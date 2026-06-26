<?php

namespace App\Services\Quotation;

use App\Models\Estimate;
use App\Repositories\Contracts\QuotationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 見積依頼（quote-request）画面のユースケース。
 */
class QuoteRequestService
{
    public function __construct(
        private readonly QuotationRepositoryInterface $estimates,
    ) {}

    /**
     * 見積依頼画面の案件一覧（未依頼の見積先）を取得する。
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Estimate>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->estimates->forEstimateManagement($filters, $perPage, 'quote-request');
    }

    /**
     * 見積依頼送信（選択した見積先へ相見積依頼を記録）。
     *
     * @param  list<int>  $companyIds
     * @return int 実際に記録した件数
     */
    public function send(array $companyIds): int
    {
        return $this->estimates->recordQuoteRequests($companyIds);
    }
}

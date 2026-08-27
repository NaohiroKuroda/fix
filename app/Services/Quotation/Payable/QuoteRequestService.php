<?php

namespace App\Services\Quotation\Payable;

use App\Exceptions\ServiceException;
use App\Models\TBuilding;
use App\Repositories\Contracts\Quotation\Payable\PayableRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

/**
 * 見積依頼（quote-request）画面のユースケース。
 */
class QuoteRequestService
{
    public function __construct(
        private readonly PayableRepositoryInterface $estimates,
    ) {}

    /**
     * 見積依頼画面の案件一覧（未依頼の見積先）を取得する。
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, TBuilding>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        try {
            return $this->estimates->forPayableManagement($filters, $perPage, 'quote-request');
        } catch (\Exception $e) {
            Log::error('見積依頼の一覧取得に失敗しました', [
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
     * 見積依頼送信（選択した見積先へ相見積依頼を記録）。
     *
     * @param  list<int>  $partnerIds
     * @return int 実際に記録した件数
     */
    public function send(array $partnerIds): int
    {
        try {
            return $this->estimates->recordQuoteRequests($partnerIds);
        } catch (\Exception $e) {
            Log::error('見積依頼の送信に失敗しました', [
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

<?php

namespace App\Services\Quotation;

use App\Exceptions\ServiceException;
use App\Models\TBuilding;
use App\Repositories\Contracts\QuotationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

/**
 * 業者選定（vendor-selection）画面のユースケース。
 */
class VendorSelectionService
{
    public function __construct(
        private readonly QuotationRepositoryInterface $estimates,
    ) {}

    /**
     * 業者選定画面の案件一覧（依頼済み・回答ありで未選定の見積先）を取得する。
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, TBuilding>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        try {
            return $this->estimates->forEstimateManagement($filters, $perPage, 'vendor-selection');
        } catch (\Exception $e) {
            Log::error('業者選定の一覧取得に失敗しました', [
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
     * 業者選定の確定（選択した見積先を採用業者として確定）。
     *
     * @param  list<int>  $companyIds
     * @return int 実際に確定した件数
     */
    public function confirm(array $companyIds): int
    {
        try {
            return $this->estimates->recordVendorSelections($companyIds);
        } catch (\Exception $e) {
            Log::error('業者選定の確定に失敗しました', [
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
     * 仮選定の保存（FELIXが依頼したい業者の印を ON/OFF）。
     * 新スキーマでは t_cost_quotations.is_drafted を更新する。
     *
     * @param  int  $companyId 見積先 ID
     * @param  bool  $drafted 仮選定 ON=true / OFF=false
     * @return int 実際に更新した件数（0=未更新/未対応）
     */
    public function setProvisional(int $companyId, bool $drafted): int
    {
        try {
            return $this->estimates->setProvisional($companyId, $drafted);
        } catch (\Exception $e) {
            Log::error('仮選定の保存に失敗しました', [
                'message' => $e->getMessage(),
                'companyId' => $companyId,
                'drafted' => $drafted,
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new ServiceException(previous: $e);
        }
    }
}

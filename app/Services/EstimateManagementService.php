<?php

namespace App\Services;

use App\Models\Estimate;
use App\Repositories\Contracts\EstimateRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 見積管理（申請/承認専用）画面のユースケース。
 *
 * felix_total 実行予算画面の「見積部分」のみを切り出した画面群（見積り依頼・
 * 発注業者選定・部長承認 …）に、共通の「案件 → 項目 → 見積先」データを提供する。
 * Controller → Service → Repository。
 */
class EstimateManagementService
{
    public function __construct(
        private readonly EstimateRepositoryInterface $estimates,
    ) {}

    /**
     * 見積管理画面用の案件一覧を取得する。
     *
     * @param  array<string, mixed>  $filters  正規化済みの絞り込み条件
     * @return LengthAwarePaginator<int, Estimate>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->estimates->forEstimateManagement($filters, $perPage);
    }
}

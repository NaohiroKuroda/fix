<?php

namespace App\Services;

use App\Models\Estimate;
use App\Repositories\Contracts\EstimateRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 実行予算（Estimate 集約）のユースケースを束ねるサービス層。
 *
 * Controller はデータアクセスを直接行わず、本サービスを経由する
 * （Controller → Service → Repository）。現状は取得処理の委譲が中心だが、
 * 業務ルール（判断・複数手順の調整）が生じた場合はここに実装する。
 */
class EstimateService
{
    public function __construct(
        private readonly EstimateRepositoryInterface $estimates,
    ) {}

    /**
     * ステータス管理画面用に、検索条件に一致する案件をページネーション取得する。
     *
     * @param  array<string, mixed>  $filters  正規化済みの検索条件
     * @return LengthAwarePaginator<int, Estimate>
     */
    public function searchForStatusManagement(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->estimates->search($filters, $perPage);
    }

    /**
     * 実行予算一覧を財務サマリ + 見積明細つきでページネーション取得する。
     *
     * @param  array<string, mixed>  $filters  正規化済みの検索条件
     * @return LengthAwarePaginator<int, Estimate>
     */
    public function paginateBudgets(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->estimates->paginateBudgets($filters, $perPage);
    }

    /**
     * 実行予算 詳細（原価明細）を取得する。存在しなければ null を返す。
     */
    public function findBudgetDetail(int $id): ?Estimate
    {
        return $this->estimates->findBudgetDetail($id);
    }
}

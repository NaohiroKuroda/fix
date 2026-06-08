<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EstimateRepositoryInterface
{
    /**
     * 検索条件に一致する案件を、明細ユニット・発注先を含めてページネーション取得する。
     *
     * @param  array<string, mixed>  $filters 正規化済みの検索条件
     * @param  int  $perPage 1ページあたりの案件数
     * @return LengthAwarePaginator<int, \App\Models\Estimate>
     */
    public function search(array $filters, int $perPage): LengthAwarePaginator;

    /**
     * 実行予算一覧。案件（実行予算）を財務サマリ + 見積明細つきでページネーション取得する。
     *
     * @param  array<string, mixed>  $filters 正規化済みの検索条件
     * @return LengthAwarePaginator<int, \App\Models\Estimate>
     */
    public function paginateBudgets(array $filters, int $perPage): LengthAwarePaginator;

    /**
     * 実行予算 詳細（原価明細セクション表示用）。集計値 + 明細ユニット + 採用業者を読み込む。
     */
    public function findBudgetDetail(int $id): ?\App\Models\Estimate;
}

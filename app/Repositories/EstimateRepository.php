<?php

namespace App\Repositories;

use App\Models\Estimate;
use App\Models\EstimateOrderHistory;
use App\Models\EstimateUnitCompany;
use App\Repositories\Contracts\EstimateRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * ステータス管理画面のデータアクセス。
 *
 * 旧 felix_total の NewEstimateCustomEditController@check_company_list を、
 * 本画面で必要な範囲に絞ってリファクタリングしたもの。
 *
 * 設計方針:
 * - 表示対象は「本見積（step=2）かつ見積確定（estimate_clear_flg=1）かつ未クローズ」の明細ユニット。
 * - 案件単位でページネーションし、全件取得を避ける（performance）。
 * - whereHas（絞り込み）と with（表示）に同一のユニット条件を適用し、検索結果と表示を一致させる。
 */
class EstimateRepository implements EstimateRepositoryInterface
{
    /** 発注先（業者）の列。 */
    private const COMPANY_COLUMNS = [
        'id', 'estimate_unit_id', 'company_id', 'branch_id', 'pay_company_id',
        'adoption_flg', 'changed_flg', 'order_flg', 'invoice_flg', 'cancel_flg',
        'tmp_status', 'design_status', 'fix_status', 'denial_comment',
    ];

    /** 実行予算一覧で読み込む明細ユニットの列。 */
    private const BUDGET_UNIT_COLUMNS = [
        'id', 'estimate_id', 'sort', 'label', 'sub_cate', 'use_flg',
        'amount', 'unit_price', 'price', 'master_price', 'tmp_unit_price',
    ];

    public function forEstimateManagement(array $filters, int $perPage): LengthAwarePaginator
    {
        $keyword = $this->nonEmpty($filters['keyword'] ?? null);      // 物件名
        $itemLabel = $this->nonEmpty($filters['itemLabel'] ?? null);  // 項目名

        // 対象ユニットの共通条件（本見積・確定・未クローズ）＋項目名の絞り込み。
        $unitFilter = function (Builder $q) use ($itemLabel): void {
            $this->applyBudgetBaseFilter($q);
            if ($itemLabel !== false) {
                $q->where('label', 'like', "%{$itemLabel}%");
            }
        };

        return Estimate::query()
            ->select(['id', 'name', 'department_id'])
            ->where('name', 'like', '●%')
            ->when($keyword, fn (Builder $q, string $kw) => $q->where('name', 'like', "%{$kw}%"))
            ->whereHas('units', fn (Builder $q) => $unitFilter($q))
            ->with(['units' => function (HasMany $q) use ($unitFilter): void {
                $unitFilter($q->getQuery());
                $q->select(self::BUDGET_UNIT_COLUMNS)
                    ->orderBy('sort')
                    ->orderBy('id')
                    ->with(['companies' => function ($c): void {
                        // 見積部分のみ：業者名・最新見積書(type=1)・依頼履歴・採用フラグ。
                        $c->select(self::COMPANY_COLUMNS)
                            ->whereNull('deleted_at')
                            ->orderByDesc('adoption_flg')
                            ->orderBy('id')
                            ->with([
                                'company:id,company_name',
                                'files:id,estimate_unit_company_id,price,date,type',
                                'orderHistories:id,estimate_unit_company_id,created_at',
                            ]);
                    }]);
            }])
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function recordQuoteRequests(array $companyIds): int
    {
        if ($companyIds === []) {
            return 0;
        }

        // 依頼履歴がまだ無い見積先だけを対象にする（重複送信を防ぐ）。
        $companies = EstimateUnitCompany::query()
            ->whereIn('id', $companyIds)
            ->whereNull('deleted_at')
            ->whereDoesntHave('orderHistories')
            ->get(['id', 'company_id', 'estimate_unit_id']);

        if ($companies->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($companies): int {
            foreach ($companies as $company) {
                EstimateOrderHistory::create([
                    'estimate_unit_company_id' => $company->id,
                    'company_id' => $company->company_id,
                    'estimate_unit_id' => $company->estimate_unit_id,
                ]);
            }

            return $companies->count();
        });
    }

    public function recordVendorSelections(array $companyIds): int
    {
        if ($companyIds === []) {
            return 0;
        }

        // 実在する（未削除の）見積先だけを対象にする。
        $companies = EstimateUnitCompany::query()
            ->whereIn('id', $companyIds)
            ->whereNull('deleted_at')
            ->get(['id', 'estimate_unit_id']);

        if ($companies->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($companies): int {
            $unitIds = $companies->pluck('estimate_unit_id')->unique()->all();
            $selectedIds = $companies->pluck('id')->all();

            // 選定対象を含むユニット内は一度すべて採用解除し、選定された見積先のみ採用する
            // （項目ごとに採用業者は1社、という発注業者選定の前提を満たす）。
            EstimateUnitCompany::query()
                ->whereIn('estimate_unit_id', $unitIds)
                ->whereNull('deleted_at')
                ->update(['adoption_flg' => 0]);

            EstimateUnitCompany::query()
                ->whereIn('id', $selectedIds)
                ->update(['adoption_flg' => 1]);

            return count($selectedIds);
        });
    }

    public function recordDesignSelections(array $companyIds): int
    {
        if ($companyIds === []) {
            return 0;
        }

        // 実在する（未削除の）見積先だけを対象にする。
        $companies = EstimateUnitCompany::query()
            ->whereIn('id', $companyIds)
            ->whereNull('deleted_at')
            ->get(['id', 'estimate_unit_id']);

        if ($companies->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($companies): int {
            $unitIds = $companies->pluck('estimate_unit_id')->unique()->all();
            $selectedIds = $companies->pluck('id')->all();

            // 選定対象を含むユニット内は一度すべて設計部選定を解除し、選定された見積先のみ選定する
            // （項目ごとに設計部選定は1社、という前提を満たす）。
            EstimateUnitCompany::query()
                ->whereIn('estimate_unit_id', $unitIds)
                ->whereNull('deleted_at')
                ->update(['design_status' => 0]);

            EstimateUnitCompany::query()
                ->whereIn('id', $selectedIds)
                ->update(['design_status' => 1]);

            return count($selectedIds);
        });
    }

    public function recordManagerApprovals(array $companyIds): int
    {
        // 部長承認 = 常務承認フラグ（fix_status）を承認済み(1)にする。
        return $this->updateCompanyColumn($companyIds, 'fix_status', 1);
    }

    public function recordCancelRequests(array $companyIds): int
    {
        // 取消申請 = cancel_flg を申請中(1)にする。
        return $this->updateCompanyColumn($companyIds, 'cancel_flg', 1);
    }

    public function recordCancelApprovals(array $companyIds): int
    {
        // 取消承認 = cancel_flg を承認済み(2)にする。
        return $this->updateCompanyColumn($companyIds, 'cancel_flg', 2);
    }

    /**
     * 指定の見積先（EstimateUnitCompany）の1カラムを一括更新する。
     * 実在（未削除）の ID のみを対象に、更新件数を返す。
     *
     * @param  list<int>  $companyIds
     */
    private function updateCompanyColumn(array $companyIds, string $column, int $value): int
    {
        if ($companyIds === []) {
            return 0;
        }

        $ids = EstimateUnitCompany::query()
            ->whereIn('id', $companyIds)
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($ids->isEmpty()) {
            return 0;
        }

        return EstimateUnitCompany::query()
            ->whereIn('id', $ids->all())
            ->update([$column => $value]);
    }

    /** 実行予算一覧の対象ユニット条件（本見積・確定・未クローズ）。 */
    private function applyBudgetBaseFilter(Builder $q): void
    {
        $q->where('step', 2)
            ->where('estimate_clear_flg', 1)
            ->whereNull('tmp_close_date');
    }

    /** 値が「未指定（null/空文字/'all'）」でなければ文字列として返す。 */
    private function nonEmpty(mixed $value): string|false
    {
        if ($value === null || $value === '' || $value === 'all') {
            return false;
        }

        return (string) $value;
    }
}

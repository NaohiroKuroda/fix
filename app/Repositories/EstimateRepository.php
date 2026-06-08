<?php

namespace App\Repositories;

use App\Models\Estimate;
use App\Repositories\Contracts\EstimateRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    /** 表示するユニットの列（不要列を読み込まない）。 */
    private const UNIT_COLUMNS = [
        'id', 'estimate_id', 'sort', 'label', 'sub_cate', 'use_flg',
        'master_price', 'tmp_unit_price', 'unit_price',
        'company_select_status', 'complete_status',
        'tmp_construct_at', 'fix_construct_at', 'tmp_completion_at', 'fix_completion_at',
        'ordered_at',
    ];

    /** 発注先（業者）の列。 */
    private const COMPANY_COLUMNS = [
        'id', 'estimate_unit_id', 'company_id', 'branch_id', 'pay_company_id',
        'adoption_flg', 'changed_flg', 'order_flg', 'invoice_flg', 'cancel_flg',
        'tmp_status', 'design_status', 'fix_status', 'denial_comment',
    ];

    public function search(array $filters, int $perPage): LengthAwarePaginator
    {
        return Estimate::query()
            ->select(['id', 'name', 'department_id', 'status', 'complete_date'])
            ->whereHas('units', fn (Builder $q) => $this->applyUnitFilters($q, $filters))
            ->when(
                $this->nonEmpty($filters['keyword'] ?? null),
                fn (Builder $q, string $keyword) => $q->where(function (Builder $w) use ($keyword): void {
                    $w->where('name', 'like', "%{$keyword}%");
                    if (ctype_digit($keyword)) {
                        $w->orWhere('id', (int) $keyword);
                    }
                })
            )
            ->with(['units' => function (HasMany $q) use ($filters): void {
                $this->applyUnitFilters($q->getQuery(), $filters);
                $q->select(self::UNIT_COLUMNS)
                    ->orderBy('sort')
                    ->orderBy('id')
                    ->with(['companies' => function ($c): void {
                        $c->select(self::COMPANY_COLUMNS)
                            ->whereNull('deleted_at')
                            ->orderByDesc('adoption_flg')
                            ->orderBy('id')
                            ->with([
                                'company:id,company_name,sub_cate',
                                'branch:id,company_name',
                                'payCompany:id,company_name',
                                'files:id,estimate_unit_company_id,price,date,type,file_cate,complete_flg',
                                'changeHistories:id,estimate_unit_company_id,type,created_at',
                                'orderHistories:id,estimate_unit_company_id,created_at',
                                'completeHistories:id,estimate_unit_company_id,status,created_at',
                                'endReports:id,estimate_unit_company_id,status,denial_flg,updated_at',
                                'orders:id,estimate_unit_company_id,date,status,adoption_flg',
                                'dates:id,estimate_unit_company_id,reply_date,adoption_flg',
                            ]);
                    }]);
            }])
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** 実行予算一覧で読み込む明細ユニットの列。 */
    private const BUDGET_UNIT_COLUMNS = [
        'id', 'estimate_id', 'sort', 'label', 'sub_cate', 'use_flg',
        'amount', 'unit_price', 'price', 'master_price', 'tmp_unit_price',
    ];

    public function paginateBudgets(array $filters, int $perPage): LengthAwarePaginator
    {
        $monthFrom = $this->nonEmpty($filters['monthFrom'] ?? null);
        $monthTo = $this->nonEmpty($filters['monthTo'] ?? null);
        $sort = $filters['sort'] ?? null;

        return Estimate::query()
            // 旧 estimate_list と同じく、引渡日(delivery_date)集計を結合して絞り込み/並べ替えに使う。
            ->leftJoin('estimate_aggregates as ea', function ($join): void {
                $join->on('estimates.id', '=', 'ea.estimate_id')
                    ->where('ea.column', '=', 'delivery_date');
            })
            ->select('estimates.id', 'estimates.name', 'estimates.tmp_budget_id', 'ea.date as delivery_date')
            // 旧画面の対象は「●」で始まる物件。
            ->where('estimates.name', 'like', '●%')
            // ID 検索（カンマ/空白区切りで複数可）
            ->when($this->idList($filters['id'] ?? null), fn (Builder $q, array $ids) => $q->whereIn('estimates.id', $ids))
            // 物件名
            ->when(
                $this->nonEmpty($filters['keyword'] ?? null),
                fn (Builder $q, string $kw) => $q->where('estimates.name', 'like', "%{$kw}%")
            )
            // 引渡月（delivery_date）範囲
            ->when($monthFrom, fn (Builder $q, string $m) => $q->whereDate('ea.date', '>=', $m.'-01'))
            ->when($monthTo, fn (Builder $q, string $m) => $q->whereDate('ea.date', '<=', date('Y-m-t', strtotime($m.'-01'))))
            ->with(['aggregates:id,estimate_id,column,date,aggregate,summary'])
            ->when($sort === 'handover_asc', fn (Builder $q) => $q->orderByRaw('ea.date IS NULL, ea.date ASC'))
            ->orderByDesc('estimates.id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findBudgetDetail(int $id): ?\App\Models\Estimate
    {
        return Estimate::query()
            ->select(['id', 'name', 'tmp_budget_id'])
            ->with([
                'aggregates:id,estimate_id,column,date,aggregate,summary',
                'units' => function (HasMany $q): void {
                    $this->applyBudgetBaseFilter($q->getQuery());
                    // 原価明細セクションごとに全列（請求先・各種ステータス）を表示するため、
                    // ステータス画面と同じ全業者・全リレーションを読み込む。
                    $q->select(array_merge(self::UNIT_COLUMNS, ['cate']))
                        ->orderBy('sort')
                        ->orderBy('id')
                        ->with(['companies' => $this->companyEagerLoad()]);
                },
            ])
            ->find($id);
    }

    /** 業者（請求先/発注先）の全リレーションを読み込むクロージャ（一覧・詳細で共用）。 */
    private function companyEagerLoad(): \Closure
    {
        return function ($c): void {
            $c->select(self::COMPANY_COLUMNS)
                ->whereNull('deleted_at')
                ->orderByDesc('adoption_flg')
                ->orderBy('id')
                ->with([
                    'company:id,company_name,sub_cate',
                    'branch:id,company_name',
                    'payCompany:id,company_name',
                    'files:id,estimate_unit_company_id,price,date,type,file_cate,complete_flg',
                    'changeHistories:id,estimate_unit_company_id,type,created_at',
                    'orderHistories:id,estimate_unit_company_id,created_at',
                    'completeHistories:id,estimate_unit_company_id,status,created_at',
                    'endReports:id,estimate_unit_company_id,status,denial_flg,updated_at',
                    'orders:id,estimate_unit_company_id,date,status,adoption_flg',
                    'dates:id,estimate_unit_company_id,reply_date,adoption_flg',
                ]);
        };
    }

    /** 実行予算一覧の対象ユニット条件（本見積・確定・未クローズ）。 */
    private function applyBudgetBaseFilter(Builder $q): void
    {
        $q->where('step', 2)
            ->where('estimate_clear_flg', 1)
            ->whereNull('tmp_close_date');
    }

    /** ID 検索文字列を整数配列へ。 */
    private function idList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        return collect(preg_split('/[\s,]+/', (string) $value))
            ->filter(fn ($v) => $v !== '' && ctype_digit($v))
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();
    }

    /**
     * 表示対象ユニットの基本条件 + 検索条件を適用する。
     *
     * @param  array<string, mixed>  $f
     */
    private function applyUnitFilters(Builder $q, array $f): void
    {
        // 基本条件（本見積・確定・未クローズ）
        $q->where('step', 2)
            ->where('estimate_clear_flg', 1)
            ->whereNull('tmp_close_date');

        // 区分（0 を含む有効値があるため null 判定で分岐）
        if (($f['subCate'] ?? null) !== null) {
            $q->where('sub_cate', (int) $f['subCate']);
        }

        // 選定承認ステータス
        if (($f['companySelectStatus'] ?? null) !== null) {
            $q->where('company_select_status', (int) $f['companySelectStatus']);
        }

        // 完了報告（承認待ち）ステータス
        if (($f['completeStatus'] ?? null) !== null) {
            $q->where('complete_status', (int) $f['completeStatus']);
        }

        // 工期（開始日以降）
        if ($this->nonEmpty($f['constructAt'] ?? null)) {
            $q->whereNotNull('tmp_construct_at')
                ->whereDate('tmp_construct_at', '>=', $f['constructAt']);
        }

        // 工期（完了日以前）
        if ($this->nonEmpty($f['completionAt'] ?? null)) {
            $q->whereNotNull('tmp_completion_at')
                ->whereDate('tmp_completion_at', '<=', $f['completionAt']);
        }

        // 業者名 / 採用 / 金額変更（いずれか1社が全条件を満たすこと）
        $companyName = $this->nonEmpty($f['company'] ?? null) ? (string) $f['company'] : null;
        $adoption = ! empty($f['adoptionFlg']);
        $changed = ! empty($f['changedFlg']);

        if ($companyName !== null || $adoption || $changed) {
            $q->whereHas('companies', function (Builder $c) use ($companyName, $adoption, $changed): void {
                $c->whereNull('deleted_at');
                if ($adoption) {
                    $c->where('adoption_flg', 1);
                }
                if ($changed) {
                    $c->where('changed_flg', 1);
                }
                if ($companyName !== null) {
                    $c->whereHas('company', fn (Builder $co) => $co->where('company_name', 'like', "%{$companyName}%"));
                }
            });
        }
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

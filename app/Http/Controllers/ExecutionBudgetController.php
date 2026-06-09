<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchBudgetRequest;
use App\Http\Resources\ExecutionBudgetResource;
use App\Repositories\Contracts\EstimateRepositoryInterface;
use Inertia\Inertia;
use Inertia\Response;

class ExecutionBudgetController extends Controller
{
    public function __construct(
        private readonly EstimateRepositoryInterface $estimates,
    ) {}

    /**
     * 実行予算一覧。
     *
     * 旧 felix_total の NewEstimateCustomEditController@list（estimates-management/list）を、
     * 案件＝実行予算の財務サマリ + 見積明細アコーディオンとして再設計したもの。
     */
    public function index(SearchBudgetRequest $request): Response
    {
        $perPage = (int) config('status_management.per_page', 10);
        $paginator = $this->estimates->paginateBudgets($request->filters(), $perPage);

        return Inertia::render('ExecutionBudget/Index', [
            'budgets' => ExecutionBudgetResource::collection($paginator->getCollection()),
            'pagination' => [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'filters' => $request->filtersForView(),
            'sortOptions' => config('status_management.budget_sort'),
            'dateColumnOptions' => config('status_management.budget_date_column'),
        ]);
    }
}

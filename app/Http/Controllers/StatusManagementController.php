<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchEstimateRequest;
use App\Http\Resources\EstimateResource;
use App\Repositories\Contracts\EstimateRepositoryInterface;
use Inertia\Inertia;
use Inertia\Response;

class StatusManagementController extends Controller
{
    public function __construct(
        private readonly EstimateRepositoryInterface $estimates,
    ) {}

    /**
     * ステータス管理画面（業者選定承認・材料納品日・完了報告書）。
     *
     * 既存 felix_total のデータ（Docker MySQL: fix）を Repository 経由で取得し、
     * JsonResource で整形して Inertia ページに渡す。検索とページネーションに対応。
     */
    public function index(SearchEstimateRequest $request): Response
    {
        $perPage = (int) config('status_management.per_page', 15);
        $paginator = $this->estimates->search($request->filters(), $perPage);

        return Inertia::render('StatusManagement/Index', [
            'estimates' => EstimateResource::collection($paginator->getCollection()),
            'pagination' => [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'filters' => $request->filtersForView(),
            'options' => [
                'subCate' => config('status_management.sub_cate'),
                'companySelectStatus' => config('status_management.company_select_status'),
                'completeStatus' => config('status_management.complete_status'),
            ],
        ]);
    }
}

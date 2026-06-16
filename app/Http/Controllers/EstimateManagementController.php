<?php

namespace App\Http\Controllers;

use App\Http\Requests\EstimateManagementRequest;
use App\Http\Resources\EstimateManagementResource;
use App\Services\EstimateManagementService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 見積管理（申請/承認専用）画面。
 *
 * felix_total 実行予算画面の「見積部分」のみを切り出し、業務（見積り依頼・発注業者選定 …）
 * ごとに状態を絞って申請/承認だけを行う。各画面はフロントの共通コンポーネントで描画する。
 */
class EstimateManagementController extends Controller
{
    private const PER_PAGE = 10;

    public function __construct(
        private readonly EstimateManagementService $estimateManagement,
    ) {}

    /** 見積り依頼（F→業者依頼前）。 */
    public function quoteRequest(EstimateManagementRequest $request): Response
    {
        return $this->renderScreen($request, 'EstimateManagement/QuoteRequest');
    }

    /** 発注業者選定（業者→F返答済）。 */
    public function vendorSelection(EstimateManagementRequest $request): Response
    {
        return $this->renderScreen($request, 'EstimateManagement/VendorSelection');
    }

    private function renderScreen(EstimateManagementRequest $request, string $page): Response
    {
        $paginator = $this->estimateManagement->paginate($request->filters(), self::PER_PAGE);

        return Inertia::render($page, [
            'projects' => EstimateManagementResource::collection($paginator->getCollection()),
            'pagination' => [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'filters' => $request->filtersForView(),
        ]);
    }
}

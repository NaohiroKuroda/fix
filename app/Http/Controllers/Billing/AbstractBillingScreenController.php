<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Quotation\AbstractQuotationScreenController;
use App\Http\Requests\QuotationManagementRequest;
use App\Http\Resources\BillingPartnerResource;
use App\Services\Billing\BillingQuotationService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 請求（もらい）系画面コントローラの共通処理。
 *
 * 一覧の Inertia 描画（projects / masters / pagination / filters の組み立て）を集約する。
 * 支払系の {@see AbstractQuotationScreenController} と同形。
 */
abstract class AbstractBillingScreenController extends Controller
{
    /** 1ページあたりの案件数（支払系と揃える）。 */
    protected const PER_PAGE = 10;

    /** 請求系画面の既定の区分（処理フロー H列「区分が請求」）。 */
    private const DEFAULT_KIND = 'billing';

    public function __construct(protected readonly BillingQuotationService $service) {}

    /**
     * 一覧を Inertia ページとして描画する。
     *
     * @param  string  $mode  画面モード（billing-quote-create など）
     * @param  string  $page  Inertia ページ名（例: quotation-management/billing-quote-create）
     */
    protected function renderScreen(QuotationManagementRequest $request, string $mode, string $page): Response
    {
        $paginator = $this->service->paginate($mode, $request->filters(self::DEFAULT_KIND), self::PER_PAGE);

        return Inertia::render($page, [
            'projects' => BillingPartnerResource::collection($paginator->items()),
            // 見積作成モーダルの選択肢（拠点 / 部署 / 単位）。
            'masters' => $this->service->masters(),
            'pagination' => [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'filters' => $request->filtersForView(self::DEFAULT_KIND),
        ]);
    }
}

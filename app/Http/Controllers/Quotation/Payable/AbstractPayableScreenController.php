<?php

namespace App\Http\Controllers\Quotation\Payable;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuotationManagementRequest;
use App\Http\Resources\PayablePartnerResource;
use App\Models\TBuilding;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 見積管理の各画面コントローラの共通処理。
 *
 * 画面ごとに Controller / Service を分けつつ、一覧の Inertia 描画（projects /
 * pagination / filters の組み立て）だけは重複を避けて本基底クラスに集約する。
 */
abstract class AbstractPayableScreenController extends Controller
{
    /** 1ページあたりの案件数。 */
    protected const PER_PAGE = 10;

    /**
     * 案件一覧を Inertia ページとして描画する。
     *
     * @param  QuotationManagementRequest  $request  絞り込み条件（filtersForView 用）
     * @param  string  $page  Inertia ページ名（例: quotation-management/quote-request）
     * @param  LengthAwarePaginator<int, TBuilding>  $paginator  案件のページネーション
     * @return Response projects / pagination / filters を渡した Inertia レスポンス
     */
    protected function renderScreen(QuotationManagementRequest $request, string $page, LengthAwarePaginator $paginator): Response
    {
        return Inertia::render($page, [
            'projects' => PayablePartnerResource::collection($paginator->items()),
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

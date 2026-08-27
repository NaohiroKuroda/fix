<?php

namespace App\Http\Controllers\Order\Payable;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderDeliveryFilterRequest;
use App\Http\Resources\OrderDeliveryResource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 発注〜納品〜請求フロー各画面コントローラの共通処理。
 */
abstract class AbstractOrderDeliveryScreenController extends Controller
{
    protected const PER_PAGE = 20;

    protected function renderScreen(OrderDeliveryFilterRequest $request, string $page, LengthAwarePaginator $paginator): Response
    {
        return Inertia::render($page, [
            'projects' => OrderDeliveryResource::collection($paginator->items()),
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

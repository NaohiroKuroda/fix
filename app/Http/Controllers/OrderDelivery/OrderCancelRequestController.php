<?php

namespace App\Http\Controllers\OrderDelivery;

use App\Http\Requests\OrderDeliveryActionRequest;
use App\Http\Requests\OrderDeliveryFilterRequest;
use App\Services\OrderDelivery\OrderService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/** 発注取消申請画面（担当者→部長）。 */
class OrderCancelRequestController extends AbstractOrderDeliveryScreenController
{
    public function __construct(private readonly OrderService $service) {}

    public function index(OrderDeliveryFilterRequest $request): Response
    {
        return $this->renderScreen(
            $request,
            'order-delivery/order-cancel-request',
            $this->service->paginate('order-cancel-request', $request->filters(), self::PER_PAGE),
        );
    }

    public function confirm(OrderDeliveryActionRequest $request): RedirectResponse
    {
        $count = $this->service->requestCancel($request->ids());

        return $count === 0
            ? back()->with('error', '発注取消を申請できませんでした。')
            : back()->with('success', "発注取消を申請しました。（{$count}件）");
    }
}

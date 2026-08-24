<?php

namespace App\Http\Controllers\OrderDelivery;

use App\Http\Requests\OrderDeliveryCancelActionRequest;
use App\Http\Requests\OrderDeliveryFilterRequest;
use App\Services\OrderDelivery\OrderService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/** 発注取消承認画面（部長→担当者）。見積管理の部長取消承認と同じく、理由必須で1件ずつ承認する。 */
class OrderCancelApprovalController extends AbstractOrderDeliveryScreenController
{
    public function __construct(private readonly OrderService $service) {}

    public function index(OrderDeliveryFilterRequest $request): Response
    {
        return $this->renderScreen(
            $request,
            'order-delivery/order-cancel-approval',
            $this->service->paginate('order-cancel-approval', $request->filters(), self::PER_PAGE),
        );
    }

    /** 取消承認（発注取消を承認）。理由を必須で受け取り、コメントに記録する。 */
    public function confirm(OrderDeliveryCancelActionRequest $request): RedirectResponse
    {
        $count = $this->service->approveCancelWithReason($request->ids(), $request->reason());

        return $count === 0
            ? back()->with('error', '発注取消を承認できませんでした。')
            : back()->with('success', "発注取消を承認しました。（{$count}件）");
    }
}

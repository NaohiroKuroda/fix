<?php

namespace App\Http\Controllers\OrderDelivery;

use App\Http\Requests\OrderDeliveryActionRequest;
use App\Http\Requests\OrderDeliveryCancelActionRequest;
use App\Http\Requests\OrderDeliveryFilterRequest;
use App\Services\OrderDelivery\OrderService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/** 業者承諾記録画面。 */
class OrderAcceptanceController extends AbstractOrderDeliveryScreenController
{
    public function __construct(private readonly OrderService $service) {}

    public function index(OrderDeliveryFilterRequest $request): Response
    {
        return $this->renderScreen(
            $request,
            'order-delivery/order-acceptance',
            $this->service->paginate('order-acceptance', $request->filters(), self::PER_PAGE),
        );
    }

    public function record(OrderDeliveryActionRequest $request): RedirectResponse
    {
        $count = $this->service->recordVendorAcceptances($request->ids());

        return $count === 0
            ? back()->with('error', '業者承諾を確認できませんでした。')
            : back()->with('success', "業者承諾を確認しました。（{$count}件）");
    }

    /** 取消申請（業者承諾待ちの発注を取消申請へ）。理由を必須で受け取り、コメントに記録する。 */
    public function cancelRequest(OrderDeliveryCancelActionRequest $request): RedirectResponse
    {
        $count = $this->service->requestCancelWithReason($request->ids(), $request->reason());

        return $count === 0
            ? back()->with('error', '取消申請を実行できませんでした。対象の発注をご確認ください。')
            : back()->with('success', "取消申請を実行しました。（{$count}件）");
    }
}

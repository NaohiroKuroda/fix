<?php

namespace App\Http\Controllers\OrderDelivery;

use App\Http\Requests\OrderDeliveryActionRequest;
use App\Http\Requests\OrderDeliveryFilterRequest;
use App\Http\Requests\RejectReasonRequest;
use App\Services\OrderDelivery\DeliveryReportService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/** 部長完了承認画面。 */
class DeliveryApprovalController extends AbstractOrderDeliveryScreenController
{
    public function __construct(private readonly DeliveryReportService $service) {}

    public function index(OrderDeliveryFilterRequest $request): Response
    {
        return $this->renderScreen(
            $request,
            'order-delivery/delivery-approval',
            $this->service->paginate('delivery-approval', $request->filters(), self::PER_PAGE),
        );
    }

    public function approve(OrderDeliveryActionRequest $request): RedirectResponse
    {
        $count = $this->service->approve($request->ids());

        return $count === 0
            ? back()->with('error', '納品を承認できませんでした。')
            : back()->with('success', "納品を承認しました。（{$count}件）");
    }

    public function reject(RejectReasonRequest $request): RedirectResponse
    {
        $count = $this->service->reject($request->targetId(), $request->reason());

        return $count === 0
            ? back()->with('error', '納品報告を否認できませんでした。')
            : back()->with('success', '納品報告を否認しました。');
    }
}

<?php

namespace App\Http\Controllers\OrderDelivery;

use App\Http\Requests\OrderDeliveryActionRequest;
use App\Http\Requests\OrderDeliveryFilterRequest;
use App\Http\Requests\RejectReasonRequest;
use App\Services\OrderDelivery\OrderService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/** 発注承認画面。 */
class OrderApprovalController extends AbstractOrderDeliveryScreenController
{
    public function __construct(private readonly OrderService $service) {}

    public function index(OrderDeliveryFilterRequest $request): Response
    {
        return $this->renderScreen(
            $request,
            'OrderDelivery/OrderApproval',
            $this->service->paginate('order-approval', $request->filters(), self::PER_PAGE),
        );
    }

    public function approve(OrderDeliveryActionRequest $request): RedirectResponse
    {
        $count = $this->service->approve($request->ids());

        return $count === 0
            ? back()->with('error', '発注を承認できませんでした。')
            : back()->with('success', "発注を承認しました。（{$count}件）");
    }

    public function reject(RejectReasonRequest $request): RedirectResponse
    {
        $count = $this->service->reject($request->targetId(), $request->reason());

        return $count === 0
            ? back()->with('error', '発注を否認できませんでした。')
            : back()->with('success', '発注を否認しました。');
    }
}

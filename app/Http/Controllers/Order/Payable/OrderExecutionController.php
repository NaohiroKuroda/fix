<?php

namespace App\Http\Controllers\Order\Payable;

use App\Http\Requests\OrderDeliveryActionRequest;
use App\Http\Requests\OrderDeliveryFilterRequest;
use App\Services\Order\Payable\OrderService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/** 発注実行画面。承認済み見積を選び、発注を作成する。 */
class OrderExecutionController extends AbstractOrderDeliveryScreenController
{
    public function __construct(private readonly OrderService $service) {}

    public function index(OrderDeliveryFilterRequest $request): Response
    {
        return $this->renderScreen(
            $request,
            'order-delivery/order-execution',
            $this->service->paginate('order-execution', $request->filters(), self::PER_PAGE),
        );
    }

    public function execute(OrderDeliveryActionRequest $request): RedirectResponse
    {
        $count = $this->service->execute($request->ids());

        return $count === 0
            ? back()->with('error', '発注を作成できませんでした。対象の見積をご確認ください。')
            : back()->with('success', "発注を作成しました。（{$count}件）");
    }
}

<?php

namespace App\Http\Controllers\Order\Payable;

use App\Http\Requests\OrderDeliveryFilterRequest;
use App\Services\Order\Payable\OrderService;
use Inertia\Response;

/**
 * 【支払】業者承諾確認画面。**表示のみ**（更新操作を持たない）。
 *
 * 業者の承諾は業者マイページ（felix_total）側の操作で記録され、本画面はその結果
 * （発注書の請負承認日時）を表示するだけ。
 *
 * @see docs/detailed-design/orders/01_支払_業者承諾確認_詳細設計.md
 */
class OrderAcceptanceController extends AbstractOrderDeliveryScreenController
{
    public function __construct(private readonly OrderService $service) {}

    public function index(OrderDeliveryFilterRequest $request): Response
    {
        return $this->renderScreen(
            $request,
            'order/payable/order-acceptance',
            $this->service->paginate('order-acceptance', $request->filters(), self::PER_PAGE),
        );
    }
}

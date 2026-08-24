<?php

namespace App\Http\Controllers\OrderDelivery;

use App\Http\Requests\OrderDeliveryActionRequest;
use App\Http\Requests\OrderDeliveryFilterRequest;
use App\Services\OrderDelivery\DeliveryReportService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/** 請求取消承認画面（部長）。完了確認画面での確認時に自動作成された請求書を選んで取消を確定する。 */
class InvoiceApprovalController extends AbstractOrderDeliveryScreenController
{
    public function __construct(private readonly DeliveryReportService $service) {}

    public function index(OrderDeliveryFilterRequest $request): Response
    {
        return $this->renderScreen(
            $request,
            'order-delivery/invoice-approval',
            $this->service->paginate('invoice-approval', $request->filters(), self::PER_PAGE),
        );
    }

    public function cancel(OrderDeliveryActionRequest $request): RedirectResponse
    {
        $count = $this->service->cancelInvoice($request->ids());

        return $count === 0
            ? back()->with('error', '請求取消を確定できませんでした。')
            : back()->with('success', "請求取消を確定しました。（{$count}件）");
    }
}

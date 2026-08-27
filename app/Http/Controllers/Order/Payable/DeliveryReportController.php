<?php

namespace App\Http\Controllers\Order\Payable;

use App\Http\Requests\OrderDeliveryActionRequest;
use App\Http\Requests\OrderDeliveryFilterRequest;
use App\Services\Order\Payable\DeliveryReportService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/** 報告書受領・納品承認申請画面。担当者が受領を確認して納品報告を作成する。 */
class DeliveryReportController extends AbstractOrderDeliveryScreenController
{
    public function __construct(private readonly DeliveryReportService $service) {}

    public function index(OrderDeliveryFilterRequest $request): Response
    {
        return $this->renderScreen(
            $request,
            'order/payable/delivery-report-submission',
            $this->service->paginate('delivery-report', $request->filters(), self::PER_PAGE),
        );
    }

    public function confirm(OrderDeliveryActionRequest $request): RedirectResponse
    {
        $count = $this->service->confirm($request->ids());

        return $count === 0
            ? back()->with('error', '報告書を確認できませんでした。')
            : back()->with('success', "報告書を確認し、納品承認を申請しました。（{$count}件）");
    }
}

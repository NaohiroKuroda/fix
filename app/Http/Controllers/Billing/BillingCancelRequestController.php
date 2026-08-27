<?php

namespace App\Http\Controllers\Billing;

use App\Http\Requests\BillingReasonActionRequest;
use App\Http\Requests\QuotationManagementRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/**
 * 【請求】見積取消申請画面（もらい運用フロー 取消系）。
 *
 * @see docs/detailed-design/quotations/08_請求_見積取消申請_詳細設計.md
 */
class BillingCancelRequestController extends AbstractBillingScreenController
{
    public function index(QuotationManagementRequest $request): Response
    {
        return $this->renderScreen($request, 'billing-cancel-request', 'quotation-management/billing-cancel-request');
    }

    /** 取消申請（1件ずつ・理由必須）。`APPROVED` → `CANCEL_APPLIED`。 */
    public function confirm(BillingReasonActionRequest $request): RedirectResponse
    {
        $count = $this->service->requestCancel($request->partnerId(), $request->reason());

        if ($count === 0) {
            return back()->with('error', '取消申請を実行できませんでした。対象の請求先をご確認ください。');
        }

        return back()->with('success', "取消申請を実行しました。（{$count}件）");
    }
}

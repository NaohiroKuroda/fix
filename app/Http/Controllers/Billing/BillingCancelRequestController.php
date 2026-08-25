<?php

namespace App\Http\Controllers\Billing;

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

    /** 取消申請。**モックのため状態更新は行わない。** */
    public function confirm(): RedirectResponse
    {
        return back()->with('success', '（モック）取消申請を実行しました。実データの更新は未実装です。');
    }
}

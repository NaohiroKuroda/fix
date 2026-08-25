<?php

namespace App\Http\Controllers\Billing;

use App\Http\Requests\QuotationManagementRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/**
 * 【請求】見積承認画面（もらい運用フロー ④）。
 *
 * @see docs/detailed-design/quotations/07_請求_見積承認_詳細設計.md
 */
class BillingQuoteApprovalController extends AbstractBillingScreenController
{
    public function index(QuotationManagementRequest $request): Response
    {
        return $this->renderScreen($request, 'billing-quote-approval', 'quotation-management/billing-quote-approval');
    }

    /** 見積承認。**モックのため状態更新・通知メール送信は行わない。** */
    public function confirm(): RedirectResponse
    {
        return back()->with('success', '（モック）見積承認を実行しました。業者への通知メールは未実装です。');
    }
}

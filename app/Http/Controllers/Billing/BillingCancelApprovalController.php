<?php

namespace App\Http\Controllers\Billing;

use App\Http\Requests\QuotationManagementRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/**
 * 【請求】見積取消承認画面（もらい運用フロー 取消系）。
 *
 * @see docs/detailed-design/quotations/09_請求_見積取消承認_詳細設計.md
 */
class BillingCancelApprovalController extends AbstractBillingScreenController
{
    public function index(QuotationManagementRequest $request): Response
    {
        return $this->renderScreen($request, 'billing-cancel-approval', 'quotation-management/billing-cancel-approval');
    }

    /** 取消承認。**モックのため状態更新・③への差し戻しは行わない。** */
    public function confirm(): RedirectResponse
    {
        return back()->with('success', '（モック）取消承認を実行しました。見積作成への差し戻しは未実装です。');
    }
}

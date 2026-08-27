<?php

namespace App\Http\Controllers\Billing;

use App\Http\Requests\QuotationManagementRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    /**
     * 取消申請の否認（却下）。取消を認めず、承認済み（`APPROVED`）のまま据え置く。
     * 否認理由は当該項目のやり取り（コメント）へ `【否認】{理由}` として残す想定。
     *
     * **モックのため状態更新・コメント登録は行わない。**
     */
    public function reject(Request $request): RedirectResponse
    {
        $request->validate([
            'partnerIds' => ['required', 'array', 'min:1'],
            'partnerIds.*' => ['integer'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        return back()->with('success', '（モック）取消申請を否認しました。ステータスの据え置きとコメント登録は未実装です。');
    }
}

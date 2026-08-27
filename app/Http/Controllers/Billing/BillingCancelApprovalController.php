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
     * 取消申請の否認。`CANCEL_APPLIED → CANCELLED` とし、
     * ③【請求】見積作成の対象（同画面は `DRAFT` / `CANCELLED` を操作対象にする）へ戻す。
     * 承認と同じ差し戻し先だが、否認理由を当該項目のやり取り（コメント）へ
     * `【否認】{理由}` として残す点が異なる。
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

        return back()->with('success', '（モック）取消申請を否認しました。見積作成への差し戻しとコメント登録は未実装です。');
    }
}

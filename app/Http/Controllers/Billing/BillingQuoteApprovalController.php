<?php

namespace App\Http\Controllers\Billing;

use App\Http\Requests\QuotationManagementRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    /**
     * 否認（見積作成へ差し戻し）。承認せず `APPLIED → CANCELLED` とし、
     * ③【請求】見積作成の対象（同画面は `DRAFT` / `CANCELLED` を操作対象にする）へ戻す。
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

        return back()->with('success', '（モック）見積を否認しました。見積作成への差し戻しとコメント登録は未実装です。');
    }
}

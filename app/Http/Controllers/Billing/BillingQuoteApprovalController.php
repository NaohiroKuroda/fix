<?php

namespace App\Http\Controllers\Billing;

use App\Http\Requests\BillingPartnerActionRequest;
use App\Http\Requests\BillingReasonActionRequest;
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

    /** 見積承認（一括）。`APPLIED` → `APPROVED`。 */
    public function confirm(BillingPartnerActionRequest $request): RedirectResponse
    {
        $count = $this->service->approve($request->partnerIds());

        if ($count === 0) {
            return back()->with('error', '見積承認を実行できませんでした。対象の請求先をご確認ください。');
        }

        return back()->with('success', "見積承認を実行しました。（{$count}件）");
    }

    /**
     * 否認（見積作成へ差し戻し）。承認せず `APPLIED → CANCELLED` とし、
     * ③【請求】見積作成の対象（同画面は `DRAFT` / `CANCELLED` を操作対象にする）へ戻す。
     * 否認理由は当該項目のやり取り（コメント）へ `【否認】{理由}` として残す。
     */
    public function reject(BillingReasonActionRequest $request): RedirectResponse
    {
        $count = $this->service->reject($request->partnerId(), $request->reason());

        if ($count === 0) {
            return back()->with('error', '否認できませんでした。対象の請求先をご確認ください。');
        }

        return back()->with('success', '見積を否認しました。見積作成へ差し戻しました。');
    }
}

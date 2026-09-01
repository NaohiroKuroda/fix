<?php

namespace App\Http\Controllers\Quotation\Billing;

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
        return $this->renderScreen($request, 'billing-quote-approval', 'quotation/billing/quote-approval');
    }

    /**
     * 見積承認（一括）。`APPLIED` → `APPROVED`。
     *
     * 承認と同時に発注書を発行し（サービス側）、承認した見積を現行 felix_total へ写したうえで、
     * 業者へ「見積確認・発注承諾のご依頼」メールを送る。
     * 現行への同期とメール送信は承認とは切り離しており、失敗しても承認は成立させたままにする。
     */
    public function confirm(BillingPartnerActionRequest $request): RedirectResponse
    {
        $partnerIds = $request->partnerIds();
        $count = $this->service->approve($partnerIds);

        if ($count === 0) {
            return back()->with('error', '見積承認を実行できませんでした。対象の請求先をご確認ください。');
        }

        // 業者マイページに見積書を出すため、承認した見積を現行の見積ファイルへ写す。
        if (! $this->service->syncQuotationToLegacy($partnerIds)) {
            return back()->with('success', "見積承認を実行しました。（{$count}件）※業者マイページへの見積反映に失敗しました。");
        }

        if (! $this->service->notifyQuoteConfirmed($partnerIds)) {
            return back()->with('success', "見積承認を実行しました。（{$count}件）※業者への確認依頼メールの送信に失敗しました。");
        }

        return back()->with('success', "見積承認を実行しました。（{$count}件）業者へ確認依頼メールを送信しました。");
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

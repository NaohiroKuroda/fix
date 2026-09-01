<?php

namespace App\Http\Controllers\Quotation\Billing;

use App\Http\Requests\BillingReasonActionRequest;
use App\Http\Requests\QuotationManagementRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/**
 * 【請求】見積取消承認画面（もらい運用フロー 取消系）。
 *
 * 承認・否認とも差し戻し先は ③【請求】見積作成で、ステータスは `CANCELLED`。
 * 違いは否認理由がコメントに `【否認】` として残る点（→ 09 §6.5）。
 *
 * @see docs/detailed-design/quotations/09_請求_見積取消承認_詳細設計.md
 */
class BillingCancelApprovalController extends AbstractBillingScreenController
{
    public function index(QuotationManagementRequest $request): Response
    {
        return $this->renderScreen($request, 'billing-cancel-approval', 'quotation/billing/cancel-approval');
    }

    /** 取消承認（1件ずつ・理由必須）。`CANCEL_APPLIED` → `CANCELLED`（③ 見積作成へ差し戻し）。 */
    public function confirm(BillingReasonActionRequest $request): RedirectResponse
    {
        $count = $this->service->approveCancel($request->partnerId(), $request->reason());

        if ($count === 0) {
            return back()->with('error', '取消承認を実行できませんでした。対象の請求先をご確認ください。');
        }

        return back()->with('success', "取消承認を実行しました。見積作成へ差し戻しました。（{$count}件）");
    }

    /**
     * 否認（1件ずつ・理由必須）。`CANCEL_APPLIED` → `APPROVED`（**承認済みのまま据え置く**）。
     *
     * 取消を認めないので見積作成へは差し戻さず、発行済みの発注書もそのまま残す。
     * 業者マイページ側もキャンセル表示が解け、発注承諾できる状態に戻る。
     */
    public function reject(BillingReasonActionRequest $request): RedirectResponse
    {
        $count = $this->service->rejectCancel($request->partnerId(), $request->reason());

        if ($count === 0) {
            return back()->with('error', '否認できませんでした。対象の請求先をご確認ください。');
        }

        return back()->with('success', '取消申請を否認しました。承認済みのまま据え置きます。');
    }
}

<?php

namespace App\Http\Controllers\Quotation\Billing;

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
        return $this->renderScreen($request, 'billing-cancel-request', 'quotation/billing/cancel-request');
    }

    /**
     * 取消申請（1件ずつ・理由必須）。`APPROVED` → `CANCEL_APPLIED`。
     *
     * もらいは業者が既に見積を確認できる状態のため、申請と同時に業者へ「発注取消のご連絡」を送る。
     * 業者マイページ側は取消申請中の見積をキャンセル扱いにし、発注承諾できないようにする。
     * メールは申請とは切り離しており、失敗しても申請は成立させたままにする。
     */
    public function confirm(BillingReasonActionRequest $request): RedirectResponse
    {
        $partnerId = $request->partnerId();
        $count = $this->service->requestCancel($partnerId, $request->reason());

        if ($count === 0) {
            return back()->with('error', '取消申請を実行できませんでした。対象の請求先をご確認ください。');
        }

        if (! $this->service->notifyCancelRequested([$partnerId])) {
            return back()->with('success', "取消申請を実行しました。（{$count}件）※業者への取消連絡メールの送信に失敗しました。");
        }

        return back()->with('success', "取消申請を実行しました。（{$count}件）業者へ取消のご連絡を送信しました。");
    }
}

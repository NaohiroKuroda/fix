<?php

namespace App\Http\Controllers\Quotation\Billing;

use App\Http\Requests\QuotationManagementRequest;
use App\Http\Requests\StoreBillingQuotationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Response;

/**
 * 【請求】見積作成画面（もらい運用フロー ①〜③）。
 *
 * @see docs/detailed-design/quotations/06_請求_見積作成_詳細設計.md
 */
class BillingQuoteCreateController extends AbstractBillingScreenController
{
    public function index(QuotationManagementRequest $request): Response
    {
        return $this->renderScreen($request, 'billing-quote-create', 'quotation-management/billing-quote-create');
    }

    /**
     * ③ 見積作成モーダルの保存。見積（＋明細）を新しい版として保存し、
     * 承認ステータスを `DRAFT` / `CANCELLED` → `APPLIED` へ進めて ④ 見積承認へ回す（§6.1）。
     */
    public function store(StoreBillingQuotationRequest $request): RedirectResponse
    {
        $quotation = $request->quotation();

        // 見積書ファイルが添付されていれば public ディスクへ保存し、その URL を持たせる。
        // 差し替えが無ければ既存の fileUrl をそのまま維持する。
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('billing-quotations/'.$request->partnerId(), 'public');
            $quotation['fileUrl'] = Storage::disk('public')->url($path);
        }

        $this->service->saveQuotation($request->partnerId(), $quotation, $request->details());
        $applied = $this->service->apply([$request->partnerId()]);

        if ($applied === 0) {
            return back()->with('success', '見積を保存しました。');
        }

        return back()->with('success', '見積を保存し、承認申請しました。');
    }
}

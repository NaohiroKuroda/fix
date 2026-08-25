<?php

namespace App\Http\Controllers\Billing;

use App\Http\Requests\QuotationManagementRequest;
use Illuminate\Http\RedirectResponse;
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

    /** ③ 見積作成モーダルの保存。**モックのため保存は行わず、成功トーストだけ返す。** */
    public function store(): RedirectResponse
    {
        return back()->with('success', '（モック）見積を保存しました。実データへの保存は未実装です。');
    }
}

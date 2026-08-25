<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuotationManagementRequest;
use App\Services\Billing\BillingMockService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 請求（もらい）系画面コントローラの共通処理。
 *
 * 現時点は **モック**（BillingMockService の固定データ）を描画する。
 * 実データ接続時は Service / Repository を差し替え、renderScreen の引数を
 * LengthAwarePaginator ベースへ寄せる（AbstractQuotationScreenController と同形）。
 */
abstract class AbstractBillingScreenController extends Controller
{
    public function __construct(protected readonly BillingMockService $service) {}

    /**
     * 一覧を Inertia ページとして描画する。
     *
     * @param  string  $mode  画面モード（billing-quote-create など）
     * @param  string  $page  Inertia ページ名（例: quotation-management/billing-quote-create）
     */
    protected function renderScreen(QuotationManagementRequest $request, string $mode, string $page): Response
    {
        $screen = $this->service->screen($mode, $request->filters());

        return Inertia::render($page, [
            'projects' => $screen['projects'],
            'pagination' => $screen['pagination'],
            'filters' => $request->filtersForView(),
        ]);
    }
}

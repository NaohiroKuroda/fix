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
    /** 請求系画面の既定の区分（処理フロー H列「区分が請求」）。 */
    private const DEFAULT_KIND = 'billing';

    protected function renderScreen(QuotationManagementRequest $request, string $mode, string $page): Response
    {
        $screen = $this->service->screen($mode, $request->filters(self::DEFAULT_KIND));

        return Inertia::render($page, [
            'projects' => $screen['projects'],
            // 見積作成モーダルの選択肢（拠点 / 部署 / 単位）。
            'masters' => $screen['masters'],
            'pagination' => $screen['pagination'],
            'filters' => $request->filtersForView(self::DEFAULT_KIND),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Quotation\Payable;

use App\Http\Requests\QuotationManagementRequest;
use App\Http\Requests\SendQuoteRequestRequest;
use App\Services\Quotation\Payable\QuoteRequestService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/**
 * 見積依頼（F→業者依頼前）画面。
 */
class QuoteRequestController extends AbstractPayableScreenController
{
    public function __construct(
        private readonly QuoteRequestService $service,
    ) {}

    /**
     * 一覧表示。
     *
     * @param  QuotationManagementRequest  $request  絞り込み条件（物件名 / 項目名 / 見積先）
     * @return Response Inertia ページ（projects / pagination / filters）
     */
    public function index(QuotationManagementRequest $request): Response
    {
        return $this->renderScreen(
            $request,
            'quotation-management/quote-request',
            $this->service->paginate($request->filters(), self::PER_PAGE),
        );
    }

    /**
     * 見積依頼送信（選択業者へ相見積依頼）。
     *
     * @param  SendQuoteRequestRequest  $request  送信対象の支払取引先（t_payable_partners）ID 配列
     * @return RedirectResponse 元画面へ戻し、成功 / エラーのフラッシュメッセージを表示
     */
    public function send(SendQuoteRequestRequest $request): RedirectResponse
    {
        // 例外時は Service がログ記録のうえ ServiceException を投げ、bootstrap/app.php が
        // 画面右上のトースト（flash.error）へ変換する。
        $count = $this->service->send($request->partnerIds());

        if ($count === 0) {
            return back()->with('error', '見積依頼を送信できませんでした。選択した見積先は既に依頼済みの可能性があります。');
        }

        return back()->with('success', "見積依頼を送信しました。（{$count}件）");
    }
}

<?php

namespace App\Http\Controllers\Quotation\Payable;

use App\Http\Requests\ConfirmVendorSelectionRequest;
use App\Http\Requests\QuotationManagementRequest;
use App\Services\Quotation\Payable\VendorSelectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * 業者選定（業者→F返答済）画面。
 */
class VendorSelectionController extends AbstractPayableScreenController
{
    public function __construct(
        private readonly VendorSelectionService $service,
    ) {}

    /**
     * 一覧表示。
     *
     * @param  QuotationManagementRequest  $request  絞り込み条件（物件名 / 項目名 / 見積先 / 全て表示）
     * @return Response Inertia ページ（projects / pagination / filters）
     */
    public function index(QuotationManagementRequest $request): Response
    {
        return $this->renderScreen(
            $request,
            'quotation/payable/vendor-selection',
            $this->service->paginate($request->filters(), self::PER_PAGE),
        );
    }

    /**
     * 業者選定の確定（選定業者を採用）。
     *
     * @param  ConfirmVendorSelectionRequest  $request  選定した支払取引先（t_payable_partners）ID 配列
     * @return RedirectResponse 元画面へ戻し、成功 / エラーのフラッシュメッセージを表示
     */
    public function confirm(ConfirmVendorSelectionRequest $request): RedirectResponse
    {
        $count = $this->service->confirm($request->partnerIds());

        if ($count === 0) {
            return back()->with('error', '発注業者を確定できませんでした。選定した見積先をご確認ください。');
        }

        return back()->with('success', "発注業者を確定しました。（{$count}件）");
    }

    /**
     * 仮選定の保存（チェック時点で即時保存。新スキーマ=t_payable_partners.is_drafted）。
     *
     * @param  Request  $request  companyId（見積先 ID）/ drafted（ON=true / OFF=false）
     * @return RedirectResponse 元画面へ戻し、成功 / エラーのフラッシュメッセージを表示
     */
    public function provisional(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'partnerId' => ['required', 'integer'],
            'drafted' => ['required', 'boolean'],
        ]);

        $drafted = (bool) $validated['drafted'];
        $count = $this->service->setProvisional((int) $validated['partnerId'], $drafted);

        if ($count === 0) {
            return back()->with('error', '仮選定の保存に失敗しました。対象の見積先をご確認ください。');
        }

        return back()->with('success', $drafted ? '仮選定しました。' : '仮選定を解除しました。');
    }
}

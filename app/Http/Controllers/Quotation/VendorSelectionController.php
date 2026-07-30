<?php

namespace App\Http\Controllers\Quotation;

use App\Http\Requests\ConfirmVendorSelectionRequest;
use App\Http\Requests\QuotationManagementRequest;
use App\Services\Quotation\VendorSelectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * 業者選定（業者→F返答済）画面。
 */
class VendorSelectionController extends AbstractQuotationScreenController
{
    public function __construct(
        private readonly VendorSelectionService $service,
    ) {}

    /**
     * 一覧表示。
     *
     * @param  QuotationManagementRequest  $request  絞り込み条件（物件名 / 項目名 / 見積先 / 全て表示）
     * @return Response  Inertia ページ（projects / pagination / filters）
     */
    public function index(QuotationManagementRequest $request): Response
    {
        return $this->renderScreen(
            $request,
            'QuotationManagement/VendorSelection',
            $this->service->paginate($request->filters(), self::PER_PAGE),
        );
    }

    /**
     * 業者選定の確定（選定業者を採用）。
     *
     * @param  ConfirmVendorSelectionRequest  $request  選定した見積先（EstimateUnitCompany）ID 配列
     * @return RedirectResponse  元画面へ戻し、成功 / エラーのフラッシュメッセージを表示
     */
    public function confirm(ConfirmVendorSelectionRequest $request): RedirectResponse
    {
        $count = $this->service->confirm($request->companyIds());

        if ($count === 0) {
            return back()->with('error', '発注業者を確定できませんでした。選定した見積先をご確認ください。');
        }

        return back()->with('success', "発注業者を確定しました。（{$count}件）");
    }

    /**
     * 仮選定の保存（チェック時点で即時保存。新スキーマ=t_cost_quotations.is_drafted）。
     *
     * @param  Request  $request  companyId（見積先 ID）/ drafted（ON=true / OFF=false）
     * @return RedirectResponse  元画面へ戻し、成功 / エラーのフラッシュメッセージを表示
     */
    public function provisional(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'companyId' => ['required', 'integer'],
            'drafted' => ['required', 'boolean'],
        ]);

        $drafted = (bool) $validated['drafted'];
        $count = $this->service->setProvisional((int) $validated['companyId'], $drafted);

        if ($count === 0) {
            return back()->with('error', '仮選定の保存に失敗しました。対象の見積先をご確認ください。');
        }

        return back()->with('success', $drafted ? '仮選定しました。' : '仮選定を解除しました。');
    }
}

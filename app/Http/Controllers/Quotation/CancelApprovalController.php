<?php

namespace App\Http\Controllers\Quotation;

use App\Http\Requests\CancelActionRequest;
use App\Http\Requests\QuotationManagementRequest;
use App\Services\Quotation\CancelApprovalService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/**
 * 部長取消承認（担当→部長取消申請）画面。
 */
class CancelApprovalController extends AbstractQuotationScreenController
{
    public function __construct(
        private readonly CancelApprovalService $service,
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
            'EstimateManagement/CancelApproval',
            $this->service->paginate($request->filters(), self::PER_PAGE),
        );
    }

    /**
     * 部長取消承認（取消申請を承認）。理由を必須で受け取り、コメントに記録する。
     *
     * @param  CancelActionRequest  $request  対象の見積先（t_cost_quotations）ID 配列 ＋ 理由
     * @return RedirectResponse 元画面へ戻し、成功 / エラーのフラッシュメッセージを表示
     */
    public function confirm(CancelActionRequest $request): RedirectResponse
    {
        $count = $this->service->confirm($request->companyIds(), $request->reason());

        if ($count === 0) {
            return back()->with('error', '取消承認を実行できませんでした。対象の見積先をご確認ください。');
        }

        return back()->with('success', "取消承認を実行しました。（{$count}件）");
    }
}

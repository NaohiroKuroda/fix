<?php

namespace App\Http\Controllers\Quotation\Payable;

use App\Http\Requests\CancelActionRequest;
use App\Http\Requests\QuotationManagementRequest;
use App\Http\Requests\RejectPayableRequest;
use App\Services\Quotation\Payable\CancelApprovalService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/**
 * 部長取消承認（担当→部長取消申請）画面。
 */
class CancelApprovalController extends AbstractPayableScreenController
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
            'quotation/payable/cancel-approval',
            $this->service->paginate($request->filters(), self::PER_PAGE),
        );
    }

    /**
     * 部長取消承認（取消申請を承認）。理由を必須で受け取り、コメントに記録する。
     *
     * @param  CancelActionRequest  $request  対象の支払取引先（t_payable_partners）ID 配列 ＋ 理由
     * @return RedirectResponse 元画面へ戻し、成功 / エラーのフラッシュメッセージを表示
     */
    public function confirm(CancelActionRequest $request): RedirectResponse
    {
        $count = $this->service->confirm($request->partnerIds(), $request->reason());

        if ($count === 0) {
            return back()->with('error', '取消承認を実行できませんでした。対象の見積先をご確認ください。');
        }

        return back()->with('success', "取消承認を実行しました。（{$count}件）");
    }

    /**
     * 否認（取消申請の却下）。取消を認めず、部長承認済み（APPROVED）のまま据え置く。
     *
     * @param  RejectPayableRequest  $request  対象の見積先 ID ＋ 否認理由
     * @return RedirectResponse 元画面へ戻し、成功 / エラーのフラッシュメッセージを表示
     */
    public function reject(RejectPayableRequest $request): RedirectResponse
    {
        // 却下＋否認理由のコメント記録は Service が担う。
        $count = $this->service->reject($request->partnerId(), $request->reason());

        if ($count === 0) {
            return back()->with('error', '否認できませんでした。対象の見積先をご確認ください。');
        }

        return back()->with('success', '否認しました。取消申請を却下し、承認済みのまま据え置きます。');
    }
}

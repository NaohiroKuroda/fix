<?php

namespace App\Http\Controllers\Quotation\Payable;

use App\Http\Requests\PayablePartnerActionRequest;
use App\Http\Requests\QuotationManagementRequest;
use App\Http\Requests\RejectPayableRequest;
use App\Services\Quotation\Payable\ManagerApprovalService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/**
 * 部長承認（F→業者選定済）画面。
 */
class ManagerApprovalController extends AbstractPayableScreenController
{
    public function __construct(
        private readonly ManagerApprovalService $service,
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
            'quotation-management/manager-approval',
            $this->service->paginate($request->filters(), self::PER_PAGE),
        );
    }

    /**
     * 部長承認（業者選定済みの見積先を承認）。
     *
     * @param  PayablePartnerActionRequest  $request  対象の支払取引先（t_payable_partners）ID 配列
     * @return RedirectResponse 元画面へ戻し、成功 / エラーのフラッシュメッセージを表示
     */
    public function confirm(PayablePartnerActionRequest $request): RedirectResponse
    {
        $count = $this->service->confirm($request->partnerIds());

        if ($count === 0) {
            return back()->with('error', '部長承認を実行できませんでした。対象の見積先をご確認ください。');
        }

        return back()->with('success', "部長承認を実行しました。（{$count}件）");
    }

    /**
     * 否認（業者選定へ差し戻し）。対象の見積先を未選定へ戻し、否認理由を記録する。
     *
     * @param  RejectPayableRequest  $request  対象の見積先 ID ＋ 否認理由
     * @return RedirectResponse 元画面へ戻し、成功 / エラーのフラッシュメッセージを表示
     */
    public function reject(RejectPayableRequest $request): RedirectResponse
    {
        // 否認（差し戻し）＋否認理由のコメント記録は Service が担う。
        $count = $this->service->reject($request->partnerId(), $request->reason());

        if ($count === 0) {
            return back()->with('error', '否認できませんでした。対象の見積先をご確認ください。');
        }

        return back()->with('success', '否認しました。業者選定へ差し戻しました。');
    }
}

<?php

namespace App\Http\Controllers\Quotation;

use App\Http\Requests\EstimateCompanyActionRequest;
use App\Http\Requests\QuotationManagementRequest;
use App\Http\Requests\RejectManagerApprovalRequest;
use App\Services\Quotation\ManagerApprovalService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/**
 * 部長承認（F→業者選定済）画面。
 */
class ManagerApprovalController extends AbstractQuotationScreenController
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
     * @param  EstimateCompanyActionRequest  $request  対象の見積先（EstimateUnitCompany）ID 配列
     * @return RedirectResponse 元画面へ戻し、成功 / エラーのフラッシュメッセージを表示
     */
    public function confirm(EstimateCompanyActionRequest $request): RedirectResponse
    {
        $count = $this->service->confirm($request->companyIds());

        if ($count === 0) {
            return back()->with('error', '部長承認を実行できませんでした。対象の見積先をご確認ください。');
        }

        return back()->with('success', "部長承認を実行しました。（{$count}件）");
    }

    /**
     * 否認（業者選定へ差し戻し）。対象の見積先を未選定へ戻し、否認理由を記録する。
     *
     * @param  RejectManagerApprovalRequest  $request  対象の見積先 ID ＋ 否認理由
     * @return RedirectResponse 元画面へ戻し、成功 / エラーのフラッシュメッセージを表示
     */
    public function reject(RejectManagerApprovalRequest $request): RedirectResponse
    {
        // 否認（差し戻し）＋否認理由のコメント記録は Service が担う。
        $count = $this->service->reject($request->companyId(), $request->reason());

        if ($count === 0) {
            return back()->with('error', '否認できませんでした。対象の見積先をご確認ください。');
        }

        return back()->with('success', '否認しました。業者選定へ差し戻しました。');
    }
}

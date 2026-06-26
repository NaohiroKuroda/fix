<?php

namespace App\Http\Controllers\Quotation;

use App\Http\Requests\EstimateCompanyActionRequest;
use App\Http\Requests\EstimateManagementRequest;
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
     * @param  EstimateManagementRequest  $request  絞り込み条件（物件名 / 項目名 / 見積先）
     * @return Response  Inertia ページ（projects / pagination / filters）
     */
    public function index(EstimateManagementRequest $request): Response
    {
        return $this->renderScreen(
            $request,
            'EstimateManagement/ManagerApproval',
            $this->service->paginate($request->filters(), self::PER_PAGE),
        );
    }

    /**
     * 部長承認（業者選定済みの見積先を承認）。
     *
     * @param  EstimateCompanyActionRequest  $request  対象の見積先（EstimateUnitCompany）ID 配列
     * @return RedirectResponse  元画面へ戻し、成功 / エラーのフラッシュメッセージを表示
     */
    public function confirm(EstimateCompanyActionRequest $request): RedirectResponse
    {
        $count = $this->service->confirm($request->companyIds());

        if ($count === 0) {
            return back()->with('error', '部長承認を実行できませんでした。対象の見積先をご確認ください。');
        }

        return back()->with('success', "部長承認を実行しました。（{$count}件）");
    }
}

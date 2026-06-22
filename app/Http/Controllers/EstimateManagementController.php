<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmDesignSelectionRequest;
use App\Http\Requests\ConfirmVendorSelectionRequest;
use App\Http\Requests\EstimateCompanyActionRequest;
use App\Http\Requests\EstimateManagementRequest;
use App\Http\Requests\SendQuoteRequestRequest;
use App\Http\Resources\EstimateManagementResource;
use App\Services\EstimateManagementService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 見積管理（申請/承認専用）画面。
 *
 * felix_total 実行予算画面の「見積部分」のみを切り出し、業務（見積り依頼・発注業者選定 …）
 * ごとに状態を絞って申請/承認だけを行う。各画面はフロントの共通コンポーネントで描画する。
 */
class EstimateManagementController extends Controller
{
    private const PER_PAGE = 10;

    public function __construct(
        private readonly EstimateManagementService $estimateManagement,
    ) {}

    /** 見積り依頼（F→業者依頼前）。 */
    public function quoteRequest(EstimateManagementRequest $request): Response
    {
        return $this->renderScreen($request, 'EstimateManagement/QuoteRequest');
    }

    /** 発注業者選定（業者→F返答済）。 */
    public function vendorSelection(EstimateManagementRequest $request): Response
    {
        return $this->renderScreen($request, 'EstimateManagement/VendorSelection');
    }

    /** 設計部選定（業者→F返答済）。 */
    public function designSelection(EstimateManagementRequest $request): Response
    {
        return $this->renderScreen($request, 'EstimateManagement/DesignSelection');
    }

    /** 部長承認（F→業者選定済）。 */
    public function managerApproval(EstimateManagementRequest $request): Response
    {
        return $this->renderScreen($request, 'EstimateManagement/ManagerApproval');
    }

    /** 部長取消申請（F→業者選定済）。 */
    public function cancelRequest(EstimateManagementRequest $request): Response
    {
        return $this->renderScreen($request, 'EstimateManagement/CancelRequest');
    }

    /** 部長取消承認（担当→部長取消申請）。 */
    public function cancelApproval(EstimateManagementRequest $request): Response
    {
        return $this->renderScreen($request, 'EstimateManagement/CancelApproval');
    }

    /**
     * 見積依頼送信（見積り依頼画面）。選択された見積先に相見積依頼を記録し、
     * 元の画面へ戻してフラッシュメッセージ（成功 / エラー）を表示する。
     */
    public function sendQuoteRequests(SendQuoteRequestRequest $request): RedirectResponse
    {
        $count = $this->estimateManagement->sendQuoteRequests($request->companyIds());

        if ($count === 0) {
            return back()->with('error', '見積依頼を送信できませんでした。選択した見積先は既に依頼済みの可能性があります。');
        }

        return back()->with('success', "見積依頼を送信しました。（{$count}件）");
    }

    /**
     * 発注業者選定の確定（発注業者選定画面）。ボタンで選定された見積先を採用業者として確定し、
     * 元の画面へ戻してフラッシュメッセージ（成功 / エラー）を表示する。
     */
    public function confirmVendorSelection(ConfirmVendorSelectionRequest $request): RedirectResponse
    {
        $count = $this->estimateManagement->confirmVendorSelections($request->companyIds());

        if ($count === 0) {
            return back()->with('error', '発注業者を確定できませんでした。選定した見積先をご確認ください。');
        }

        return back()->with('success', "発注業者を確定しました。（{$count}件）");
    }

    /**
     * 設計部選定の確定（設計部選定画面）。ボタンで選定された見積先を設計部選定済みとして確定し、
     * 元の画面へ戻してフラッシュメッセージ（成功 / エラー）を表示する。
     */
    public function confirmDesignSelection(ConfirmDesignSelectionRequest $request): RedirectResponse
    {
        $count = $this->estimateManagement->confirmDesignSelections($request->companyIds());

        if ($count === 0) {
            return back()->with('error', '設計部選定を確定できませんでした。選定した見積先をご確認ください。');
        }

        return back()->with('success', "設計部選定を確定しました。（{$count}件）");
    }

    /** 部長承認（発注業者選定済みの見積先を承認）。 */
    public function confirmManagerApproval(EstimateCompanyActionRequest $request): RedirectResponse
    {
        $count = $this->estimateManagement->confirmManagerApprovals($request->companyIds());

        if ($count === 0) {
            return back()->with('error', '部長承認を実行できませんでした。対象の見積先をご確認ください。');
        }

        return back()->with('success', "部長承認を実行しました。（{$count}件）");
    }

    /** 部長取消申請（選定の取消を申請）。 */
    public function confirmCancelRequest(EstimateCompanyActionRequest $request): RedirectResponse
    {
        $count = $this->estimateManagement->confirmCancelRequests($request->companyIds());

        if ($count === 0) {
            return back()->with('error', '取消申請を実行できませんでした。対象の見積先をご確認ください。');
        }

        return back()->with('success', "取消申請を実行しました。（{$count}件）");
    }

    /** 部長取消承認（取消申請を承認）。 */
    public function confirmCancelApproval(EstimateCompanyActionRequest $request): RedirectResponse
    {
        $count = $this->estimateManagement->confirmCancelApprovals($request->companyIds());

        if ($count === 0) {
            return back()->with('error', '取消承認を実行できませんでした。対象の見積先をご確認ください。');
        }

        return back()->with('success', "取消承認を実行しました。（{$count}件）");
    }

    private function renderScreen(EstimateManagementRequest $request, string $page): Response
    {
        $paginator = $this->estimateManagement->paginate($request->filters(), self::PER_PAGE);

        return Inertia::render($page, [
            'projects' => EstimateManagementResource::collection($paginator->getCollection()),
            'pagination' => [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'filters' => $request->filtersForView(),
        ]);
    }
}

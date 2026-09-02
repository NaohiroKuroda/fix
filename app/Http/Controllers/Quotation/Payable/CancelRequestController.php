<?php

namespace App\Http\Controllers\Quotation\Payable;

use App\Http\Requests\CancelActionRequest;
use App\Http\Requests\QuotationManagementRequest;
use App\Services\Quotation\Payable\CancelRequestService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/**
 * 部長取消申請（F→業者選定済）画面。
 */
class CancelRequestController extends AbstractPayableScreenController
{
    public function __construct(
        private readonly CancelRequestService $service,
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
            'quotation/payable/cancel-request',
            $this->service->paginate($request->filters(), self::PER_PAGE),
        );
    }

    /**
     * 部長取消申請（選定の取消を申請）。理由を必須で受け取り、コメントに記録する。
     *
     * @param  CancelActionRequest  $request  対象の支払取引先（t_payable_partners）ID 配列 ＋ 理由
     * @return RedirectResponse 元画面へ戻し、成功 / エラーのフラッシュメッセージを表示
     */
    public function confirm(CancelActionRequest $request): RedirectResponse
    {
        $count = $this->service->confirm($request->partnerIds(), $request->reason());

        if ($count === 0) {
            return back()->with('error', '取消申請を実行できませんでした。対象の見積先をご確認ください。');
        }

        // 業者マイページの発注書がキャンセル扱いになるため、その旨を業者へ連絡する（A4-3）。
        if (! $this->service->notifyCancelRequested($request->partnerIds())) {
            return back()->with('success', "取消申請を実行しました。（{$count}件）※業者への取消連絡メールの送信に失敗しました。");
        }

        return back()->with('success', "取消申請を実行しました。（{$count}件）業者へ取消のご連絡を送信しました。");
    }
}

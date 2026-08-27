<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Billing\BillingCancelApprovalController;
use App\Http\Controllers\Billing\BillingCancelRequestController;
use App\Http\Controllers\Billing\BillingOrderConfirmationController;
use App\Http\Controllers\Billing\BillingQuoteApprovalController;
use App\Http\Controllers\Billing\BillingQuoteCreateController;
use App\Http\Controllers\OrderDelivery\DeliveryApprovalController;
use App\Http\Controllers\OrderDelivery\DeliveryReportController;
use App\Http\Controllers\OrderDelivery\InvoiceApprovalController;
use App\Http\Controllers\OrderDelivery\OrderAcceptanceController;
use App\Http\Controllers\OrderDelivery\OrderApprovalController;
use App\Http\Controllers\OrderDelivery\OrderCancelApprovalController;
use App\Http\Controllers\OrderDelivery\OrderCancelRequestController;
use App\Http\Controllers\OrderDelivery\OrderExecutionController;
use App\Http\Controllers\Quotation\CancelApprovalController;
use App\Http\Controllers\Quotation\CancelRequestController;
use App\Http\Controllers\Quotation\CommentAttachmentController;
use App\Http\Controllers\Quotation\ManagerApprovalController;
use App\Http\Controllers\Quotation\QuotationMessageController;
use App\Http\Controllers\Quotation\QuoteRequestController;
use App\Http\Controllers\Quotation\VendorSelectionController;
use Illuminate\Support\Facades\Route;

// 未認証（ゲスト）— ログイン画面 / ログイン処理
Route::middleware('guest:admin')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// 認証必須（admin ガード = admin_users）
Route::middleware('auth:admin')->group(function () {
    // トップはサイドメニュー先頭（見積依頼）へ。
    Route::get('/', fn () => redirect()->route('quotation-management.quote-request'))->name('home');

    // 見積管理（申請/承認専用。felix_total 実行予算の見積部分を切り出した画面群）。
    // 画面ごとに Controller を分離（GET=一覧 index / POST=実行 send|confirm）。
    Route::get('/quotation-management/quote-request', [QuoteRequestController::class, 'index'])
        ->name('quotation-management.quote-request');     // 見積り依頼（F→業者依頼前）
    Route::get('/quotation-management/vendor-selection', [VendorSelectionController::class, 'index'])
        ->name('quotation-management.vendor-selection');  // 業者選定（業者→F返答済）
    Route::get('/quotation-management/manager-approval', [ManagerApprovalController::class, 'index'])
        ->name('quotation-management.manager-approval');  // 部長承認（F→業者選定済）
    Route::get('/quotation-management/cancel-request', [CancelRequestController::class, 'index'])
        ->name('quotation-management.cancel-request');    // 部長取消申請（F→業者選定済）
    Route::get('/quotation-management/cancel-approval', [CancelApprovalController::class, 'index'])
        ->name('quotation-management.cancel-approval');   // 部長取消承認（担当→部長取消申請）
    Route::post('/quotation-management/quote-request', [QuoteRequestController::class, 'send'])
        ->name('quotation-management.quote-request.send'); // 見積依頼送信（選択業者へ相見積依頼）
    Route::post('/quotation-management/vendor-selection', [VendorSelectionController::class, 'confirm'])
        ->name('quotation-management.vendor-selection.confirm'); // 業者選定の確定（選定業者を採用）
    Route::post('/quotation-management/vendor-selection/provisional', [VendorSelectionController::class, 'provisional'])
        ->name('quotation-management.vendor-selection.provisional'); // 仮選定の即時保存（is_drafted）
    Route::post('/quotation-management/manager-approval', [ManagerApprovalController::class, 'confirm'])
        ->name('quotation-management.manager-approval.confirm'); // 部長承認（選定業者を承認）
    Route::post('/quotation-management/manager-approval/reject', [ManagerApprovalController::class, 'reject'])
        ->name('quotation-management.manager-approval.reject'); // 部長承認の否認（業者選定へ差し戻し）
    Route::post('/quotation-management/cancel-request', [CancelRequestController::class, 'confirm'])
        ->name('quotation-management.cancel-request.confirm');   // 部長取消申請（選定の取消を申請）
    Route::post('/quotation-management/cancel-approval', [CancelApprovalController::class, 'confirm'])
        ->name('quotation-management.cancel-approval.confirm');  // 部長取消承認（取消申請を承認）
    Route::post('/quotation-management/cancel-approval/reject', [CancelApprovalController::class, 'reject'])
        ->name('quotation-management.cancel-approval.reject');   // 取消申請の否認（却下して承認済みへ据え置き）

    // 見積管理（請求＝もらい）。もらいは相見積・業者選定が無く、FELIX 側で見積を代理作成する。
    // ※ 現時点は**モック**（BillingMockService の固定データ。DB 未接続）。
    // 詳細設計: docs/detailed-design/quotations/06〜09_請求_*_詳細設計.md
    Route::get('/quotation-management/billing-quote-create', [BillingQuoteCreateController::class, 'index'])
        ->name('quotation-management.billing-quote-create');       // 【請求】見積作成（担当→業者）
    Route::post('/quotation-management/billing-quote-create', [BillingQuoteCreateController::class, 'store'])
        ->name('quotation-management.billing-quote-create.store'); // 見積作成モーダルの保存
    Route::get('/quotation-management/billing-quote-approval', [BillingQuoteApprovalController::class, 'index'])
        ->name('quotation-management.billing-quote-approval');     // 【請求】見積承認（部長→業者）
    Route::post('/quotation-management/billing-quote-approval', [BillingQuoteApprovalController::class, 'confirm'])
        ->name('quotation-management.billing-quote-approval.confirm');
    Route::post('/quotation-management/billing-quote-approval/reject', [BillingQuoteApprovalController::class, 'reject'])
        ->name('quotation-management.billing-quote-approval.reject'); // 見積の否認（見積作成へ差し戻し）
    Route::get('/quotation-management/billing-cancel-request', [BillingCancelRequestController::class, 'index'])
        ->name('quotation-management.billing-cancel-request');     // 【請求】見積取消申請（担当→部長）
    Route::post('/quotation-management/billing-cancel-request', [BillingCancelRequestController::class, 'confirm'])
        ->name('quotation-management.billing-cancel-request.confirm');
    Route::get('/quotation-management/billing-cancel-approval', [BillingCancelApprovalController::class, 'index'])
        ->name('quotation-management.billing-cancel-approval');    // 【請求】見積取消承認（部長→担当）
    Route::post('/quotation-management/billing-cancel-approval', [BillingCancelApprovalController::class, 'confirm'])
        ->name('quotation-management.billing-cancel-approval.confirm');
    Route::post('/quotation-management/billing-cancel-approval/reject', [BillingCancelApprovalController::class, 'reject'])
        ->name('quotation-management.billing-cancel-approval.reject'); // 取消申請の否認（却下して承認済みへ戻す）

    // 見積先（t_cost_quotations）単位のやり取り（チャット）。業者選定（部下）⇔部長承認（部長）。
    Route::get('/quotation-management/quotations/{quotation}/messages', [QuotationMessageController::class, 'index'])
        ->name('quotation-management.quotation-messages.index');
    Route::post('/quotation-management/quotations/{quotation}/messages', [QuotationMessageController::class, 'store'])
        ->name('quotation-management.quotation-messages.store');

    // コメント添付ファイル（t_attachments）の配信。認証付きで Laravel から直接ストリームする
    // （公開ストレージの静的配信は Web サーバ設定依存で 403 になり得るため）。
    Route::get('/quotation-management/comment-attachments/{attachment}/thumb', [CommentAttachmentController::class, 'thumb'])
        ->name('quotation-management.comment-attachments.thumb');
    Route::get('/quotation-management/comment-attachments/{attachment}/download', [CommentAttachmentController::class, 'download'])
        ->name('quotation-management.comment-attachments.download');

    // 発注〜納品〜請求フロー（見積管理に続くフェーズ。Felix担当者/部長/社長のみの画面）。
    Route::get('/order-delivery/order-execution', [OrderExecutionController::class, 'index'])
        ->name('order-delivery.order-execution');           // 発注実行
    Route::post('/order-delivery/order-execution', [OrderExecutionController::class, 'execute'])
        ->name('order-delivery.order-execution.execute');
    Route::get('/order-delivery/order-approval', [OrderApprovalController::class, 'index'])
        ->name('order-delivery.order-approval');            // 発注承認
    Route::post('/order-delivery/order-approval', [OrderApprovalController::class, 'approve'])
        ->name('order-delivery.order-approval.approve');
    Route::post('/order-delivery/order-approval/reject', [OrderApprovalController::class, 'reject'])
        ->name('order-delivery.order-approval.reject');
    Route::get('/order-delivery/order-cancel-request', [OrderCancelRequestController::class, 'index'])
        ->name('order-delivery.order-cancel-request');      // 発注取消申請
    Route::post('/order-delivery/order-cancel-request', [OrderCancelRequestController::class, 'confirm'])
        ->name('order-delivery.order-cancel-request.confirm');
    Route::get('/order-delivery/order-cancel-approval', [OrderCancelApprovalController::class, 'index'])
        ->name('order-delivery.order-cancel-approval');     // 発注取消承認（部長）
    Route::post('/order-delivery/order-cancel-approval', [OrderCancelApprovalController::class, 'confirm'])
        ->name('order-delivery.order-cancel-approval.confirm');
    Route::get('/order-delivery/order-acceptance', [OrderAcceptanceController::class, 'index'])
        ->name('order-delivery.order-acceptance');          // 業者承諾記録
    Route::get('/order-delivery/billing-order-confirmation', [BillingOrderConfirmationController::class, 'index'])
        ->name('order-delivery.billing-order-confirmation'); // 【請求】発注書確認（部長・モック）
    Route::post('/order-delivery/order-acceptance', [OrderAcceptanceController::class, 'record'])
        ->name('order-delivery.order-acceptance.record');
    Route::post('/order-delivery/order-acceptance/renotify', [OrderAcceptanceController::class, 'renotify'])
        ->name('order-delivery.order-acceptance.renotify');
    Route::post('/order-delivery/order-acceptance/cancel-request', [OrderAcceptanceController::class, 'cancelRequest'])
        ->name('order-delivery.order-acceptance.cancel-request');
    Route::get('/order-delivery/delivery-report', [DeliveryReportController::class, 'index'])
        ->name('order-delivery.delivery-report');           // 完了確認（提出日・確認日・請求日）
    Route::post('/order-delivery/delivery-report', [DeliveryReportController::class, 'confirm'])
        ->name('order-delivery.delivery-report.confirm');
    Route::get('/order-delivery/delivery-approval', [DeliveryApprovalController::class, 'index'])
        ->name('order-delivery.delivery-approval');         // 部長完了承認
    Route::post('/order-delivery/delivery-approval', [DeliveryApprovalController::class, 'approve'])
        ->name('order-delivery.delivery-approval.approve');
    Route::post('/order-delivery/delivery-approval/reject', [DeliveryApprovalController::class, 'reject'])
        ->name('order-delivery.delivery-approval.reject');
    Route::get('/order-delivery/invoice-approval', [InvoiceApprovalController::class, 'index'])
        ->name('order-delivery.invoice-approval');           // 請求取消承認
    Route::post('/order-delivery/invoice-approval', [InvoiceApprovalController::class, 'cancel'])
        ->name('order-delivery.invoice-approval.cancel');

    // ログアウト（安全のために POST で処理）
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

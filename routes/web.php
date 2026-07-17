<?php

use App\Http\Controllers\AuthController;
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

    // ログアウト（安全のために POST で処理）
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

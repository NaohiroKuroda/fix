<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Quotation\CancelApprovalController;
use App\Http\Controllers\Quotation\CancelRequestController;
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
    Route::get('/', fn () => redirect()->route('estimate-management.quote-request'))->name('home');

    // 見積管理（申請/承認専用。felix_total 実行予算の見積部分を切り出した画面群）。
    // 画面ごとに Controller を分離（GET=一覧 index / POST=実行 send|confirm）。
    Route::get('/estimate-management/quote-request', [QuoteRequestController::class, 'index'])
        ->name('estimate-management.quote-request');     // 見積り依頼（F→業者依頼前）
    Route::get('/estimate-management/vendor-selection', [VendorSelectionController::class, 'index'])
        ->name('estimate-management.vendor-selection');  // 業者選定（業者→F返答済）
    Route::get('/estimate-management/manager-approval', [ManagerApprovalController::class, 'index'])
        ->name('estimate-management.manager-approval');  // 部長承認（F→業者選定済）
    Route::get('/estimate-management/cancel-request', [CancelRequestController::class, 'index'])
        ->name('estimate-management.cancel-request');    // 部長取消申請（F→業者選定済）
    Route::get('/estimate-management/cancel-approval', [CancelApprovalController::class, 'index'])
        ->name('estimate-management.cancel-approval');   // 部長取消承認（担当→部長取消申請）
    Route::post('/estimate-management/quote-request', [QuoteRequestController::class, 'send'])
        ->name('estimate-management.quote-request.send'); // 見積依頼送信（選択業者へ相見積依頼）
    Route::post('/estimate-management/vendor-selection', [VendorSelectionController::class, 'confirm'])
        ->name('estimate-management.vendor-selection.confirm'); // 業者選定の確定（選定業者を採用）
    Route::post('/estimate-management/vendor-selection/provisional', [VendorSelectionController::class, 'provisional'])
        ->name('estimate-management.vendor-selection.provisional'); // 仮選定の即時保存（is_drafted）
    Route::post('/estimate-management/manager-approval', [ManagerApprovalController::class, 'confirm'])
        ->name('estimate-management.manager-approval.confirm'); // 部長承認（選定業者を承認）
    Route::post('/estimate-management/manager-approval/reject', [ManagerApprovalController::class, 'reject'])
        ->name('estimate-management.manager-approval.reject'); // 部長承認の否認（業者選定へ差し戻し）
    Route::post('/estimate-management/cancel-request', [CancelRequestController::class, 'confirm'])
        ->name('estimate-management.cancel-request.confirm');   // 部長取消申請（選定の取消を申請）
    Route::post('/estimate-management/cancel-approval', [CancelApprovalController::class, 'confirm'])
        ->name('estimate-management.cancel-approval.confirm');  // 部長取消承認（取消申請を承認）

    // 見積先（t_cost_quotations）単位のやり取り（チャット）。業者選定（部下）⇔部長承認（部長）。
    Route::get('/estimate-management/quotations/{quotation}/messages', [QuotationMessageController::class, 'index'])
        ->name('estimate-management.quotation-messages.index');
    Route::post('/estimate-management/quotations/{quotation}/messages', [QuotationMessageController::class, 'store'])
        ->name('estimate-management.quotation-messages.store');

    // ログアウト（安全のために POST で処理）
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

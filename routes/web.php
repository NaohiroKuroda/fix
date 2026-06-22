<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EstimateManagementController;
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

    // 見積管理（申請/承認専用。felix_total 実行予算の見積部分を切り出した画面群）
    Route::get('/estimate-management/quote-request', [EstimateManagementController::class, 'quoteRequest'])
        ->name('estimate-management.quote-request');     // 見積り依頼（F→業者依頼前）
    Route::get('/estimate-management/vendor-selection', [EstimateManagementController::class, 'vendorSelection'])
        ->name('estimate-management.vendor-selection');  // 発注業者選定（業者→F返答済）
    Route::get('/estimate-management/design-selection', [EstimateManagementController::class, 'designSelection'])
        ->name('estimate-management.design-selection');  // 設計部選定（業者→F返答済）
    Route::get('/estimate-management/manager-approval', [EstimateManagementController::class, 'managerApproval'])
        ->name('estimate-management.manager-approval');  // 部長承認（F→業者選定済）
    Route::get('/estimate-management/cancel-request', [EstimateManagementController::class, 'cancelRequest'])
        ->name('estimate-management.cancel-request');    // 部長取消申請（F→業者選定済）
    Route::get('/estimate-management/cancel-approval', [EstimateManagementController::class, 'cancelApproval'])
        ->name('estimate-management.cancel-approval');   // 部長取消承認（担当→部長取消申請）
    Route::post('/estimate-management/quote-request', [EstimateManagementController::class, 'sendQuoteRequests'])
        ->name('estimate-management.quote-request.send'); // 見積依頼送信（選択業者へ相見積依頼）
    Route::post('/estimate-management/vendor-selection', [EstimateManagementController::class, 'confirmVendorSelection'])
        ->name('estimate-management.vendor-selection.confirm'); // 発注業者選定の確定（選定業者を採用）
    Route::post('/estimate-management/design-selection', [EstimateManagementController::class, 'confirmDesignSelection'])
        ->name('estimate-management.design-selection.confirm'); // 設計部選定の確定（選定業者を設計部選定）
    Route::post('/estimate-management/manager-approval', [EstimateManagementController::class, 'confirmManagerApproval'])
        ->name('estimate-management.manager-approval.confirm'); // 部長承認（選定業者を承認）
    Route::post('/estimate-management/cancel-request', [EstimateManagementController::class, 'confirmCancelRequest'])
        ->name('estimate-management.cancel-request.confirm');   // 部長取消申請（選定の取消を申請）
    Route::post('/estimate-management/cancel-approval', [EstimateManagementController::class, 'confirmCancelApproval'])
        ->name('estimate-management.cancel-approval.confirm');  // 部長取消承認（取消申請を承認）

    // ログアウト（安全のために POST で処理）
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

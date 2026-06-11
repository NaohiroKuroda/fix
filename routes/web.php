<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EstimateDetailController;
use App\Http\Controllers\ExecutionBudgetController;
use App\Http\Controllers\StatusManagementController;
use Illuminate\Support\Facades\Route;

// 未認証（ゲスト）— ログイン画面 / ログイン処理
Route::middleware('guest:admin')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// 認証必須（admin ガード = admin_users）
Route::middleware('auth:admin')->group(function () {
    Route::get('/', [ExecutionBudgetController::class, 'index'])->name('home');

    // 実行予算一覧（felix_total の estimates-management/list を再現）
    Route::get('/execution-budgets', [ExecutionBudgetController::class, 'index'])
        ->name('execution-budgets.index');

    // 実行予算 詳細（felix_total の new-estimates-custom-edit?active=2 を再現）
    Route::get('/estimates/{estimate}', [EstimateDetailController::class, 'show'])
        ->whereNumber('estimate')
        ->name('estimates.show');

    // ステータス管理画面（業者選定承認・材料納品日・完了報告書）
    Route::get('/status-management', [StatusManagementController::class, 'index'])
        ->name('status-management.index');

    // ログアウト（安全のために POST で処理）
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

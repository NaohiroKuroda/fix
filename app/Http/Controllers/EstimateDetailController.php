<?php

namespace App\Http\Controllers;

use App\Http\Resources\EstimateDetailResource;
use App\Services\EstimateService;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EstimateDetailController extends Controller
{
    public function __construct(
        private readonly EstimateService $estimateService,
    ) {}

    /**
     * 実行予算 詳細（原価明細）。
     *
     * 旧 felix_total の new-estimates-custom-edit?id=&active=2（estimate_edit）を、
     * 原価明細をセクション別に表示する画面として再設計したもの。一覧の物件名から新規タブで開く。
     */
    public function show(int $estimate): Response
    {
        $model = $this->estimateService->findBudgetDetail($estimate);

        if ($model === null) {
            throw new NotFoundHttpException("実行予算 #{$estimate} が見つかりません。");
        }

        return Inertia::render('Estimate/Detail', [
            'budget' => new EstimateDetailResource($model),
        ]);
    }
}

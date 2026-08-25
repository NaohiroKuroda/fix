<?php

namespace App\Providers;

use App\Models\TBuildingBudgetItem;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Inertia props を素の配列で渡すため "data" ラッピングを無効化する。
        JsonResource::withoutWrapping();

        // ポリモーフィック型のマップ。
        // コメント（t_comments.commentable_type）と既読（t_comment_read_timestamps.readable_type）には
        // 旧クラス名 "App\Models\TBuildingCostItem" が保存済みで、2026-08 のスキーマ改訂で
        // モデルを TBuildingBudgetItem へ改称した。既存データを書き換えずに解決できるよう、
        // 旧 FQCN をそのまま別名（morph alias）として登録する。
        // ＝ 保存時も getMorphClass() が旧 FQCN を返すため、既存行と型文字列が揃う。
        Relation::morphMap([
            'App\Models\TBuildingCostItem' => TBuildingBudgetItem::class,
        ]);
    }
}

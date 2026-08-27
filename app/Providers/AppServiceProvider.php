<?php

namespace App\Providers;

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
        // 2026-08 のスキーマ改訂で費用項目のモデルを TBuildingCostItem → TBuildingBudgetItem へ改称した。
        // 既存データ（t_comments.commentable_type / t_comment_read_timestamps.readable_type）は
        // マイグレーション（2026_08_27_000000_rename_building_cost_item_morph_type）で
        // 新しい FQCN へ書き換え済みのため、**別名は登録しない**（getMorphClass() が実クラス名を返す）。
        //
        // 旧 FQCN を別名として登録すると、保存時にも旧クラス名が書き込まれ続けてしまう。
    }
}

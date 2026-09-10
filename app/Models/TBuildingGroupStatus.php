<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 建物見積グループ状態（t_building_group_statuses）
 *
 * 建物（実行予算）ごとに見積グループ A/B/C/D の4件を持ち、`is_completed` は
 * 「そのグループの設計ファイルが揃っているか」を表す。更新は現行側
 * （laravel-filemanager のイベント／`command:check_no_estimate`）。
 *
 * 新Fix は参照のみ。項目側の必要グループ（{@see TBuildingBudgetItemGroup}）と
 * 突き合わせて、見積依頼できるかの判定に使う。
 */
class TBuildingGroupStatus extends Model
{
    protected $table = 't_building_group_statuses';

    protected $casts = [
        'group_code' => 'integer',
        'is_completed' => 'boolean',
    ];

    /** 建物（実行予算）。 */
    public function building()
    {
        return $this->belongsTo(TBuilding::class, 'building_id');
    }
}

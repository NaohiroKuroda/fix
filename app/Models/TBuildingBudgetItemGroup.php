<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 建物予算項目見積グループ（t_building_budget_item_groups）
 *
 * 「この項目はどの見積グループの設計ファイルが要るか」を項目 × グループコードで持つ
 * （現行 `estimate_units.group` を分解したもの。1=A / 2=B / 3=C / 4=D）。
 * 充足しているかは建物側の {@see TBuildingGroupStatus} と突き合わせる。
 *
 * 新Fix は参照のみ（作成は現行の NewQuotationRegisterService）。
 */
class TBuildingBudgetItemGroup extends Model
{
    protected $table = 't_building_budget_item_groups';

    protected $casts = [
        'group_code' => 'integer',
    ];

    /** 建物予算項目。 */
    public function budgetItem()
    {
        return $this->belongsTo(TBuildingBudgetItem::class, 'building_budget_item_id');
    }
}

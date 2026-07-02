<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 費用（旧：estimate_units (step = 2)）
 *
 * @see database/migrations/2026_06_19_160425_create_t_building_cost_items_table.php
 */
class TBuildingCostItem extends Model
{
    use HasFactory;

    protected $table = 't_building_cost_items';

    protected $fillable = [
        'building_id',
        'item_kind',
        'sort',
        'item_name',
        'master_price',
        'budget_price',
        'quotation_amount',
        'created_at',
        'updated_at',
        'source_id',
    ];

    // 建物
    public function building()
    {
        return $this->belongsTo(TBuilding::class, 'building_id');
    }

    // 費用見積
    public function quotations()
    {
        return $this->hasMany(TCostQuotation::class, 'building_cost_item_id');
    }

    // コメント（やり取り）。項目（明細）単位で1スレッド。ポリモーフィック（commentable）。
    public function comments()
    {
        return $this->morphMany(TComment::class, 'commentable');
    }
}

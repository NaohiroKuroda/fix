<?php

namespace App\Models;

use App\Models\Concerns\HasBlameColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 建物（旧：estimates）
 *
 * @see database/migrations/2026_06_19_133637_create_t_buildings_table.php
 */
class TBuilding extends Model
{
    use HasBlameColumns, HasFactory;

    protected $table = 't_buildings';

    protected $fillable = [
        'building_name',
        'division_code',
        'created_at',
        'updated_at',
        'source_id',
    ];

    // 費用
    public function costItems()
    {
        return $this->hasMany(TBuildingCostItem::class, 'building_id');
    }
}

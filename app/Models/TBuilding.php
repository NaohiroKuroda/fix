<?php

namespace App\Models;

use App\Models\Concerns\HasBlameColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 建物（旧：estimates）
 *
 * 2026-08 のスキーマ改訂で建物名の列が `building_name` → `name` に変わっている
 * （テーブル定義書に準拠）。
 */
class TBuilding extends Model
{
    use HasBlameColumns, HasFactory;

    protected $table = 't_buildings';

    protected $fillable = [
        'name',
        'division_code',
        'created_at',
        'updated_at',
        'source_id',
    ];

    // 建物予算項目
    public function budgetItems()
    {
        return $this->hasMany(TBuildingBudgetItem::class, 'building_id');
    }
}

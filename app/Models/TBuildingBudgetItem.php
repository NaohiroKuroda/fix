<?php

namespace App\Models;

use App\Models\Concerns\HasBlameColumns;
use App\Providers\AppServiceProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 建物予算項目（旧：estimate_units (step = 2, sub_cate = 2)）
 *
 * 2026-08 のスキーマ改訂で `t_building_cost_items` → `t_building_budget_items` へ改称され、
 * 項目名の列も `item_name` → `name` になった（テーブル定義書に準拠）。
 * コメント（t_comments）の commentable_type には旧 FQCN が保存済みのため、
 * {@see AppServiceProvider} のモーフマップで旧文字列を本クラスへ解決する。
 */
class TBuildingBudgetItem extends Model
{
    use HasBlameColumns, HasFactory;

    protected $table = 't_building_budget_items';

    protected $fillable = [
        'building_id',
        'sort',
        'is_enabled',
        'name',
        'is_shared',
        'cost_unit_id',
        'master_price',
        'budget_price',
        'quotation_amount',
        'created_at',
        'updated_at',
        'source_id',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_shared' => 'boolean',
    ];

    // 建物
    public function building()
    {
        return $this->belongsTo(TBuilding::class, 'building_id');
    }

    /** 支払取引先（はらい）。 */
    public function payablePartners()
    {
        return $this->hasMany(TPayablePartner::class, 'building_budget_item_id');
    }

    /** 請求取引先（もらい）。支払系画面では「表示のみ」で参照する。 */
    public function billingPartners()
    {
        return $this->hasMany(TBillingPartner::class, 'building_budget_item_id');
    }

    // コメント（やり取り）。項目（明細）単位で1スレッド。ポリモーフィック（commentable）。
    public function comments()
    {
        return $this->morphMany(TComment::class, 'commentable');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 費用見積（旧：estimate_unit_companies）
 *
 * @see database/migrations/2026_06_19_173151_create_t_cost_quotations_table.php
 */
class TCostQuotation extends Model
{
    use HasFactory;

    protected $table = 't_cost_quotations';

    protected $fillable = [
        'building_cost_item_id',
        'company_id',
        'branch_company_id',
        'counter_company_id',
        'is_drafted',
        'approval_status',
        'created_at',
        'updated_at',
        'source_id',
    ];

    protected $casts = [
        'is_drafted' => 'boolean',
    ];

    // 費用
    public function costItem()
    {
        return $this->belongsTo(TBuildingCostItem::class, 'building_cost_item_id');
    }

    // 見積先
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    // 見積先支店
    public function branchCompany()
    {
        return $this->belongsTo(Company::class, 'branch_company_id');
    }

    // 見積先窓口
    public function counterCompany()
    {
        return $this->belongsTo(Company::class, 'counter_company_id');
    }

    // 費用見積履歴
    public function histories()
    {
        return $this->hasMany(TCostQuotationHistory::class, 'cost_quotation_id');
    }

    // 最新の費用見積履歴（is_latest = true）
    public function latestHistory()
    {
        return $this->hasOne(TCostQuotationHistory::class, 'cost_quotation_id')
            ->where('is_latest', true);
    }
}

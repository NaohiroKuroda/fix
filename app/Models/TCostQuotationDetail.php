<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 費用見積明細（旧：cost_unit_estimate_units）
 *
 * @see database/migrations/2026_06_22_130823_create_t_cost_quotation_details_table.php
 */
class TCostQuotationDetail extends Model
{
    use HasFactory;

    protected $table = 't_cost_quotation_details';

    protected $fillable = [
        'cost_quotation_history_id',
        'is_memo',
        'branch_code',
        'department_id',
        'name',
        'quantity',
        'unit_id',
        'unit_price',
        'tax_class',
        'tax_rate',
        'is_tax_included',
        'price',
        'is_changed',
        'created_at',
        'updated_at',
        'source_id',
    ];

    protected $casts = [
        'is_memo' => 'boolean',
        'is_tax_included' => 'boolean',
        'is_changed' => 'boolean',
    ];

    // 費用見積履歴
    public function history()
    {
        return $this->belongsTo(TCostQuotationHistory::class, 'cost_quotation_history_id');
    }

    // 部署
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    // 単位マスター
    public function unit()
    {
        return $this->belongsTo(MasterUnit::class, 'unit_id');
    }
}

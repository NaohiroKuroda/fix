<?php

namespace App\Models;

use App\Models\Concerns\HasBlameColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 支払見積明細（旧：cost_unit_estimate_units）
 *
 * 2026-08 のスキーマ改訂で `t_cost_quotation_details` → `t_payable_quotation_details` へ改称。
 * 列名も `tax_class` → `tax_type` / `is_tax_included` → `is_tax_inclusive` に変わっている。
 * 消費税率（tax_rate）は DECIMAL のため **string のまま**扱い、計算は BCMath で行う
 * （docs/architecture/backend.md §5.5）。
 */
class TPayableQuotationDetail extends Model
{
    use HasBlameColumns, HasFactory;

    protected $table = 't_payable_quotation_details';

    protected $fillable = [
        'payable_quotation_id',
        'is_memo',
        'branch_code',
        'department_id',
        'name',
        'quantity',
        'unit_id',
        'unit_price',
        'tax_type',
        'tax_rate',
        'is_tax_inclusive',
        'price',
        'is_changed',
        'created_at',
        'updated_at',
        'source_id',
    ];

    protected $casts = [
        'is_memo' => 'boolean',
        'is_tax_inclusive' => 'boolean',
        'is_changed' => 'boolean',
    ];

    // 支払見積
    public function quotation()
    {
        return $this->belongsTo(TPayableQuotation::class, 'payable_quotation_id');
    }
}

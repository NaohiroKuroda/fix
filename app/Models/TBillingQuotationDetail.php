<?php

namespace App\Models;

use App\Models\Concerns\HasBlameColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 請求見積明細（もらい）
 *
 * 列は felix_total の業者マイページ「見積」タブを踏襲する
 * （docs/detailed-design/quotations/06_請求_見積作成_詳細設計.md §6.2）。
 * `is_changed` は felix_total の fix_flg 相当で、**画面には true の行だけを表示する**。
 * 消費税率（tax_rate）は DECIMAL のため string のまま扱い、計算は BCMath で行う。
 */
class TBillingQuotationDetail extends Model
{
    use HasBlameColumns, HasFactory;

    protected $table = 't_billing_quotation_details';

    protected $fillable = [
        'billing_quotation_id',
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

    // 請求見積
    public function quotation()
    {
        return $this->belongsTo(TBillingQuotation::class, 'billing_quotation_id');
    }
}

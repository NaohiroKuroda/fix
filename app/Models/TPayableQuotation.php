<?php

namespace App\Models;

use App\Models\Concerns\HasBlameColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 支払見積（旧：estimate_unit_company_files (type = 1)）
 *
 * 2026-08 のスキーマ改訂で `t_cost_quotation_histories` → `t_payable_quotations` へ改称。
 * 外部キーも `cost_quotation_id` → `payable_partner_id` に変わっている。
 */
class TPayableQuotation extends Model
{
    use HasBlameColumns, HasFactory;

    protected $table = 't_payable_quotations';

    protected $fillable = [
        'payable_partner_id',
        'is_latest',
        'file_url',
        'quotation_date',
        'amount_excluding_tax',
        'tax_adjust',
        'withholding_income_tax',
        'comment',
        'created_at',
        'updated_at',
        'source_id',
    ];

    protected $casts = [
        'is_latest' => 'boolean',
    ];

    // 支払取引先
    public function partner()
    {
        return $this->belongsTo(TPayablePartner::class, 'payable_partner_id');
    }

    // 支払見積明細
    public function details()
    {
        return $this->hasMany(TPayableQuotationDetail::class, 'payable_quotation_id');
    }
}

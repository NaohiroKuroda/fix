<?php

namespace App\Models;

use App\Models\Concerns\HasBlameColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 請求見積（もらい。FELIX が請求取引先へ送るために作成した見積）
 *
 * 支払側は {@see TPayableQuotation}。
 */
class TBillingQuotation extends Model
{
    use HasBlameColumns, HasFactory;

    protected $table = 't_billing_quotations';

    protected $fillable = [
        'billing_partner_id',
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

    // 請求取引先
    public function partner()
    {
        return $this->belongsTo(TBillingPartner::class, 'billing_partner_id');
    }

    // 請求見積明細
    public function details()
    {
        return $this->hasMany(TBillingQuotationDetail::class, 'billing_quotation_id');
    }
}

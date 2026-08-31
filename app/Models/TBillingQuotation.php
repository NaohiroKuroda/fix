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
        // 2026-08 のスキーマ改訂で `amount_excluding_tax` → `subtotal_amount`（税別合計）に改称され、
        // 消費税額（`tax_amount`）が別列で追加された。
        'subtotal_amount',
        'tax_amount',
        'tax_adjust',
        'withholding_income_tax',
        'comment',
        'created_at',
        'updated_at',
        'source_id',
    ];

    protected $casts = [
        'is_latest' => 'boolean',
        // 業者が見積を承諾した日時（もらいの ⑥ 発注承諾）。未承諾は null。
        'accepted_at' => 'datetime',
    ];

    // 請求取引先
    public function partner()
    {
        return $this->belongsTo(TBillingPartner::class, 'billing_partner_id');
    }

    // この見積を元に発行した発注書（1見積＝1発注）
    public function order()
    {
        return $this->hasOne(TBillingOrder::class, 'billing_quotation_id');
    }

    // 請求見積明細
    public function details()
    {
        return $this->hasMany(TBillingQuotationDetail::class, 'billing_quotation_id');
    }
}

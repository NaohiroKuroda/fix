<?php

namespace App\Models;

use App\Models\Concerns\HasBlameColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 請求発注（もらい。業者が FELIX へ発行する発注書に相当）
 *
 * もらいは相見積・業者選定が無く「見積＝発注＝請求」で同額のため、
 * 【請求】見積承認（部長）の時点で本レコードを発行する
 * （→ docs/detailed-design/quotations/07_請求_見積承認_詳細設計.md §8.5）。
 * 業者が発注を承諾すると `contract_approved_at`（請負承認日時）が入る。
 *
 * 支払（はらい）側は {@see TPayableOrder}。
 *
 * @see docs/detailed-design/orders/02_請求_発注書確認_詳細設計.md §4
 */
class TBillingOrder extends Model
{
    use HasBlameColumns, HasFactory;

    protected $table = 't_billing_orders';

    protected $fillable = [
        'billing_quotation_id',
        'billing_partner_id',
        'issued_at',
        'subtotal_amount',
        'tax_amount',
        'tax_adjust',
        'status',
        'withholding_tax',
        'contract_approved_at',
        'created_at',
        'updated_at',
        'source_id',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        // 業者の請負承認（＝発注承諾）日時。未承諾は null。
        'contract_approved_at' => 'datetime',
    ];

    // 請求取引先
    public function partner()
    {
        return $this->belongsTo(TBillingPartner::class, 'billing_partner_id');
    }

    // 発注の元になった請求見積（最新版）
    public function quotation()
    {
        return $this->belongsTo(TBillingQuotation::class, 'billing_quotation_id');
    }

    // 発注明細
    public function details()
    {
        return $this->hasMany(TBillingOrderDetail::class, 'billing_order_id');
    }
}

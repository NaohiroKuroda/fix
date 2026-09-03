<?php

namespace App\Models;

use App\Models\Concerns\HasBlameColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 支払発注（はらい。FELIX が業者へ発行した発注書）
 *
 * 2026-08 のスキーマ改訂で追加されたテーブル。発注書1通＝1レコードで、
 * 金額（税別合計・消費税）と業者の請負承認日時（`contract_approved_at`）を持つ。
 * 請求（もらい）側は {@see TBillingOrder}。
 *
 * `status` は発行済みを表す `ISSUED` 固定で、進行状態は持たない。
 * 発注書の発行（＝【支払】部長承認）が発注確定であり、段階的な発注承認フローは廃止した
 * （旧 t_orders.order_status。2026-09 にテーブルごと廃止）。
 *
 * @see docs/detailed-design/orders/01_支払_業者承諾確認_詳細設計.md §4
 */
class TPayableOrder extends Model
{
    use HasBlameColumns, HasFactory;

    protected $table = 't_payable_orders';

    protected $fillable = [
        'payable_quotation_id',
        'payable_partner_id',
        'issued_at',
        'department_id',
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

    // 支払取引先
    public function partner()
    {
        return $this->belongsTo(TPayablePartner::class, 'payable_partner_id');
    }

    // 発注の元になった支払見積（最新版）
    public function quotation()
    {
        return $this->belongsTo(TPayableQuotation::class, 'payable_quotation_id');
    }

    // 発注明細
    public function details()
    {
        return $this->hasMany(TPayableOrderDetail::class, 'payable_order_id');
    }
}

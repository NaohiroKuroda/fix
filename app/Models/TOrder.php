<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 発注。
 *
 * @see database/migrations/2026_07_06_130000_create_t_orders_table.php
 */
class TOrder extends Model
{
    use HasFactory;

    protected $table = 't_orders';

    protected $fillable = [
        'cost_quotation_id',
        'order_status',
        'amount',
        'order_date',
        'vendor_accepted_at',
        'deny_comment',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'vendor_accepted_at' => 'datetime',
    ];

    // 元となった費用見積
    public function quotation()
    {
        // 2026-08 のスキーマ改訂で t_cost_quotations は t_payable_partners へ改称。
        // t_orders 側の列名（cost_quotation_id）は変更されていない。
        return $this->belongsTo(TPayablePartner::class, 'cost_quotation_id');
    }

    // 発注承認履歴
    public function approvalActions()
    {
        return $this->hasMany(TOrderApprovalAction::class, 'order_id');
    }

    // 納品報告
    public function deliveryReport()
    {
        return $this->hasOne(TDeliveryReport::class, 'order_id');
    }
}

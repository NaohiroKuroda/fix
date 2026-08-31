<?php

namespace App\Models;

use App\Models\Concerns\HasBlameColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 請求発注明細（発注書の明細行）。発行時に請求見積明細（{@see TBillingQuotationDetail}）から複製する。
 */
class TBillingOrderDetail extends Model
{
    use HasBlameColumns, HasFactory;

    protected $table = 't_billing_order_details';

    protected $fillable = [
        'billing_order_id',
        'name',
        'quantity',
        'unit_id',
        'unit_price',
        'tax_type',
        'tax_rate',
        'is_tax_inclusive',
        'price',
        'created_at',
        'updated_at',
        'source_id',
    ];

    protected $casts = [
        'is_tax_inclusive' => 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(TBillingOrder::class, 'billing_order_id');
    }
}

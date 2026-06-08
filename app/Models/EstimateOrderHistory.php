<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 相見積の送信履歴（依頼）。`estimate_order_histories`。
 *
 * @property int $estimate_unit_company_id
 */
class EstimateOrderHistory extends Model
{
    protected $table = 'estimate_order_histories';

    protected $guarded = [];
}

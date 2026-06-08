<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 発注書。`orders`。発注書送付日・ステータスに使用。
 *
 * @property int|null $estimate_unit_company_id
 * @property string|null $date
 * @property int|null $status
 * @property int|null $adoption_flg
 */
class Order extends Model
{
    protected $table = 'orders';

    protected $guarded = [];
}

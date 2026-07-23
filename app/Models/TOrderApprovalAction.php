<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 発注承認履歴。
 *
 * @see database/migrations/2026_07_06_130100_create_t_order_approval_actions_table.php
 */
class TOrderApprovalAction extends Model
{
    protected $table = 't_order_approval_actions';

    protected $fillable = [
        'order_id',
        'step_name',
        'action_type',
        'operator_id',
        'action_at',
    ];

    // created_at / updated_at を持たないテーブル
    public $timestamps = false;

    protected $casts = [
        'action_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(TOrder::class, 'order_id');
    }

    public function operator()
    {
        return $this->belongsTo(AdminUser::class, 'operator_id');
    }
}

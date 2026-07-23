<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 納品報告承認履歴。
 *
 * @see database/migrations/2026_07_06_130300_create_t_delivery_report_approval_actions_table.php
 */
class TDeliveryReportApprovalAction extends Model
{
    protected $table = 't_delivery_report_approval_actions';

    protected $fillable = [
        'delivery_report_id',
        'step_name',
        'action_type',
        'operator_id',
        'action_at',
    ];

    public $timestamps = false;

    protected $casts = [
        'action_at' => 'datetime',
    ];

    public function deliveryReport()
    {
        return $this->belongsTo(TDeliveryReport::class, 'delivery_report_id');
    }

    public function operator()
    {
        return $this->belongsTo(AdminUser::class, 'operator_id');
    }
}

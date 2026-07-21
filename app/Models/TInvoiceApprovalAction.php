<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 請求承認履歴。
 *
 * @see database/migrations/2026_07_06_130500_create_t_invoice_approval_actions_table.php
 */
class TInvoiceApprovalAction extends Model
{
    protected $table = 't_invoice_approval_actions';

    protected $fillable = [
        'invoice_id',
        'step_name',
        'action_type',
        'operator_id',
        'action_at',
    ];

    public $timestamps = false;

    protected $casts = [
        'action_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(TInvoice::class, 'invoice_id');
    }

    public function operator()
    {
        return $this->belongsTo(AdminUser::class, 'operator_id');
    }
}

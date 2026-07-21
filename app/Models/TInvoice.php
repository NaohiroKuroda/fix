<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * 請求。
 *
 * @see database/migrations/2026_07_06_130400_create_t_invoices_table.php
 */
class TInvoice extends Model
{
    use HasFactory;

    protected $table = 't_invoices';

    protected $fillable = [
        'delivery_report_id',
        'invoice_status',
        'amount',
        'submitted_at',
        'closed_at',
        'deny_comment',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function deliveryReport()
    {
        return $this->belongsTo(TDeliveryReport::class, 'delivery_report_id');
    }

    public function approvalActions()
    {
        return $this->hasMany(TInvoiceApprovalAction::class, 'invoice_id');
    }

    // 請求書ファイル（t_attachments のポリモーフィック）
    public function attachments(): MorphMany
    {
        return $this->morphMany(TAttachment::class, 'attachable');
    }

    // コメント（t_comments のポリモーフィック）
    public function comments(): MorphMany
    {
        return $this->morphMany(TComment::class, 'commentable');
    }
}

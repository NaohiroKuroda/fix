<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * 納品報告。
 *
 * @see database/migrations/2026_07_06_130200_create_t_delivery_reports_table.php
 */
class TDeliveryReport extends Model
{
    use HasFactory;

    protected $table = 't_delivery_reports';

    protected $fillable = [
        'order_id',
        'report_status',
        'submitted_at',
        'deny_comment',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(TOrder::class, 'order_id');
    }

    public function approvalActions()
    {
        return $this->hasMany(TDeliveryReportApprovalAction::class, 'delivery_report_id');
    }

    public function invoice()
    {
        return $this->hasOne(TInvoice::class, 'delivery_report_id');
    }

    // 報告書ファイル（t_attachments のポリモーフィック）
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

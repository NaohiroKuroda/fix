<?php

namespace App\Models;

use App\Models\Concerns\HasBlameColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 費用見積承認履歴
 *
 * @see database/migrations/2026_06_22_165123_create_t_cost_quotation_approval_actions_table.php
 */
class TCostQuotationApprovalAction extends Model
{
    use HasBlameColumns, HasFactory;

    protected $table = 't_cost_quotation_approval_actions';

    protected $fillable = [
        'cost_quotation_history_id',
        'step_name',
        'action_type',
        'operator_id',
        'action_at',
        'source_id',
    ];

    // created_at / updated_at を持たないテーブル
    public $timestamps = false;

    protected $casts = [
        'action_at' => 'datetime',
    ];

    // 費用見積履歴
    public function history()
    {
        return $this->belongsTo(TCostQuotationHistory::class, 'cost_quotation_history_id');
    }

    // ユーザー
    public function operator()
    {
        return $this->belongsTo(AdminUser::class, 'operator_id');
    }
}

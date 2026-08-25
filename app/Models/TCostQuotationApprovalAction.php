<?php

namespace App\Models;

use App\Models\Concerns\HasBlameColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 費用見積承認履歴（旧スキーマの名残）。
 *
 * 2026-08 のスキーマ改訂で承認履歴は `t_approval_requests` / `t_approval_actions` に置き換わり、
 * 親テーブル `t_cost_quotation_histories` は廃止された（本テーブル自体は DB に残っている）。
 * 現在どの画面からも参照していないため、参照する場合は新テーブルへの移行可否を先に確認すること。
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

    // ユーザー
    public function operator()
    {
        return $this->belongsTo(AdminUser::class, 'operator_id');
    }
}

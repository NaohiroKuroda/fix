<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 完了報告の承認履歴。`complete_approval_histories`。
 * status=2/3/4 が第一/第二/第三承認の承認済を表す。
 *
 * @property int $estimate_unit_company_id
 * @property int|null $status
 */
class CompleteApprovalHistory extends Model
{
    protected $table = 'complete_approval_histories';

    protected $guarded = [];
}

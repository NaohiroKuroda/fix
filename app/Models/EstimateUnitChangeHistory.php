<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 選定ステータスの変更履歴。`estimate_unit_change_histories`。
 * type='tmp_company'|'design_company'|'fix_company' ごとに最新 created_at が選定日。
 *
 * @property int $estimate_unit_company_id
 * @property string|null $type
 */
class EstimateUnitChangeHistory extends Model
{
    protected $table = 'estimate_unit_change_histories';

    protected $guarded = [];
}

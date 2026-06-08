<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 完了報告書。`end_reports`。否認（denial_flg）判定に使用。
 *
 * @property int $estimate_unit_company_id
 * @property int|null $status
 * @property int|null $denial_flg
 */
class EndReport extends Model
{
    protected $table = 'end_reports';

    protected $guarded = [];
}

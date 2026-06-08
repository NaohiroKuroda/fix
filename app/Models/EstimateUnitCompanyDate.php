<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 業者の希望/回答日。`estimate_unit_company_dates`。承認納期日に使用。
 *
 * @property int $estimate_unit_company_id
 * @property string|null $reply_date
 * @property int|null $adoption_flg
 */
class EstimateUnitCompanyDate extends Model
{
    protected $table = 'estimate_unit_company_dates';

    protected $guarded = [];
}

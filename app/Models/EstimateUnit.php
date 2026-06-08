<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 見積明細ユニット。`estimate_units` テーブルにマッピングする。
 *
 * @property int $id
 * @property int $estimate_id
 * @property int $step
 * @property string|null $label
 * @property int|null $sub_cate
 * @property int $use_flg
 * @property float|null $master_price
 * @property float|null $tmp_unit_price
 * @property float|null $unit_price
 * @property int|null $company_select_status
 * @property int|null $complete_status
 * @property string|null $tmp_construct_at
 * @property string|null $tmp_completion_at
 * @property string|null $ordered_at
 */
class EstimateUnit extends Model
{
    protected $table = 'estimate_units';

    protected $guarded = [];

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class, 'estimate_id');
    }

    /** このユニットに紐づく発注先（業者）。 */
    public function companies(): HasMany
    {
        return $this->hasMany(EstimateUnitCompany::class, 'estimate_unit_id');
    }
}

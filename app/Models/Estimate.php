<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 案件（見積）。既存 felix_total の `estimates` テーブルにマッピングする。
 *
 * @property int $id
 * @property string $name
 * @property int|null $department_id
 * @property int|null $status
 */
class Estimate extends Model
{
    protected $table = 'estimates';

    protected $guarded = [];

    /** 本画面の対象となる「本見積（step=2）」の明細ユニット。 */
    public function units(): HasMany
    {
        return $this->hasMany(EstimateUnit::class, 'estimate_id');
    }

    /** 実行予算一覧で表示する集計値（キー値）。 */
    public function aggregates(): HasMany
    {
        return $this->hasMany(EstimateAggregate::class, 'estimate_id');
    }
}

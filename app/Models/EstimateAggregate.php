<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 実行予算の集計値（キー値）。`estimate_aggregates`。
 * column ごとに date / aggregate(数値) / summary(テキスト) のいずれかを持つ。
 *
 * @property int $estimate_id
 * @property string $column
 * @property string|null $date
 * @property float|null $aggregate
 * @property string|null $summary
 */
class EstimateAggregate extends Model
{
    protected $table = 'estimate_aggregates';

    protected $guarded = [];
}

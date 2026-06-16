<?php

namespace App\Http\Resources;

use App\Models\Estimate;
use App\Utils\Format;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 実行予算（案件）1件分。
 *
 * 旧 estimate_list を踏襲し、estimate_aggregates（キー値）を column ごとに
 * { d: 日付, v: 数値, s: テキスト } へ整形して渡す。
 *
 * @mixin Estimate
 */
class ExecutionBudgetResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name ?? '',
            'hasTmpBudget' => ! empty($this->tmp_budget_id),
            'agg' => $this->aggregateMap(),
        ];
    }

    /**
     * column => { d: 'Y/m/d'|null, v: int|null, s: string|null } のマップ。
     *
     * @return array<string, array{d: ?string, v: ?int, s: ?string}>
     */
    private function aggregateMap(): array
    {
        if (! $this->relationLoaded('aggregates')) {
            return [];
        }

        $map = [];
        foreach ($this->aggregates as $row) {
            $map[$row->column] = [
                'd' => Format::date($row->date) ?: null,
                'v' => $row->aggregate !== null ? (int) round((float) $row->aggregate) : null,
                's' => Format::plain($row->summary),
            ];
        }

        return $map;
    }
}

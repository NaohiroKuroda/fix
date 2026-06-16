<?php

namespace App\Http\Resources;

use App\Models\Estimate;
use App\Models\EstimateUnit;
use App\Utils\Format;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * 実行予算 詳細（旧 new-estimates-custom-edit?active=2 の原価明細画面を踏襲）。
 *
 * 明細ユニットを原価セクション（土地仕入 / 建設 / 金融 / 販売 など）へ分類して渡す。
 *
 * @mixin Estimate
 */
class EstimateDetailResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name ?? '',
            'hasTmpBudget' => ! empty($this->tmp_budget_id),
            'summary' => $this->summary(),
            'sections' => $this->sections(),
        ];
    }

    /** ヘッダーの財務サマリ（estimate_aggregates 由来）。 */
    private function summary(): array
    {
        $agg = $this->relationLoaded('aggregates')
            ? $this->aggregates->keyBy('column')
            : collect();

        $num = fn (string $key): ?int => ($v = $agg->get($key)?->aggregate) !== null ? (int) round((float) $v) : null;
        $date = fn (string $key): string => Format::date($agg->get($key)?->date);
        $text = fn (string $key): ?string => Format::plain($agg->get($key)?->summary);

        return [
            'address' => $text('address'),
            'deliveryDate' => $date('delivery_date'),
            'sellTotal' => $num('sell_total_price'),
            'costTotal' => $num('cost_total_price'),
            'profitTotal' => $num('gross_profit_price'),
            'profitRate' => $num('gross_profit_margin'),
        ];
    }

    /**
     * 明細ユニットを原価セクションへ分類する。
     *
     * @return list<array{key: string, title: string, count: int, units: mixed}>
     */
    private function sections(): array
    {
        if (! $this->relationLoaded('units')) {
            return [];
        }

        /** @var Collection<int, EstimateUnit> $units */
        $units = $this->units;

        $defs = [
            ['key' => 'sale_in', 'title' => '土地建物販売（入金）', 'match' => fn (EstimateUnit $u) => in_array((int) $u->cate, [11, 12, 13, 14], true)],
            ['key' => 'sale_pay', 'title' => '土地建物販売（支払）', 'match' => fn (EstimateUnit $u) => (int) $u->cate === 1 && (int) $u->sub_cate === 3],
            ['key' => 'land_purchase', 'title' => '土地仕入（支払）', 'match' => fn (EstimateUnit $u) => (int) $u->cate === 1 && (int) $u->sub_cate === 1],
            ['key' => 'finance_pay', 'title' => '金融機関（支払）', 'match' => fn (EstimateUnit $u) => (int) $u->sub_cate === 4],
            ['key' => 'finance_loan', 'title' => '金融機関（融資）', 'match' => fn (EstimateUnit $u) => (int) $u->cate === 5],
            ['key' => 'construction', 'title' => '建設（支払）', 'match' => fn (EstimateUnit $u) => (int) $u->cate === 2 && (int) $u->sub_cate === 2],
            ['key' => 'non_sale', 'title' => '土地建物販売外（入出金）', 'match' => fn (EstimateUnit $u) => (int) $u->sub_cate === 0],
        ];

        $sections = [];
        $assigned = [];
        foreach ($defs as $def) {
            $matched = $units->filter(fn (EstimateUnit $u) => ! isset($assigned[$u->id]) && $def['match']($u));
            foreach ($matched as $u) {
                $assigned[$u->id] = true;
            }
            if ($matched->isNotEmpty()) {
                $sections[] = [
                    'key' => $def['key'],
                    'title' => $def['title'],
                    'count' => $matched->count(),
                    'units' => EstimateUnitResource::collection($matched->values()),
                ];
            }
        }

        // どのセクションにも入らない明細はまとめて「その他」へ。
        $rest = $units->filter(fn (EstimateUnit $u) => ! isset($assigned[$u->id]));
        if ($rest->isNotEmpty()) {
            $sections[] = [
                'key' => 'other',
                'title' => 'その他',
                'count' => $rest->count(),
                'units' => EstimateUnitResource::collection($rest->values()),
            ];
        }

        return $sections;
    }
}

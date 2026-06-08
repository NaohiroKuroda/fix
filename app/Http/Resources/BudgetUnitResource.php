<?php

namespace App\Http\Resources;

use App\Models\EstimateUnit;
use App\Support\Format;
use App\Support\StatusLabel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 実行予算の見積明細（ユニット）1行分。
 *
 * @mixin EstimateUnit
 */
class BudgetUnitResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $adopted = $this->relationLoaded('companies') ? $this->companies->first() : null;

        return [
            'id' => $this->id,
            'label' => $this->label ?? '',
            'subCateLabel' => StatusLabel::subCate($this->sub_cate),
            'useFlg' => (int) $this->use_flg,
            'amount' => $this->amount !== null ? (float) $this->amount : null,
            'unitPrice' => Format::yen($this->unit_price),
            'price' => Format::yen($this->price),
            'masterPrice' => Format::yen($this->master_price),
            'estimatePrice' => Format::yen($this->tmp_unit_price),
            'company' => $adopted?->company?->company_name,
            'companySelectLabel' => StatusLabel::companySelect($this->company_select_status),
            'completeLabel' => StatusLabel::complete($this->complete_status),
            'constructAt' => Format::date($this->tmp_construct_at),
            'completionAt' => Format::date($this->tmp_completion_at),
        ];
    }
}

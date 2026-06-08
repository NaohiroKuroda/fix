<?php

namespace App\Http\Resources;

use App\Models\Estimate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 案件（見積）1件分の表示用データ。
 *
 * @mixin Estimate
 */
class EstimateResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name ?? '',
            'units' => EstimateUnitResource::collection($this->whenLoaded('units')),
        ];
    }
}

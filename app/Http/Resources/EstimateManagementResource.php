<?php

namespace App\Http\Resources;

use App\Models\Estimate;
use App\Utils\Format;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 見積管理画面の案件1件分（案件 → 項目 → 見積先のフラット行）。
 *
 * 見積り依頼・発注業者選定など複数画面で共通利用する。画面ごとの列の出し分けは
 * フロント（EstimateManagementScreen.vue）が mode で行う。
 *
 * @mixin Estimate
 */
class EstimateManagementResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'no' => (int) $this->id,
            'name' => (string) $this->name,
            'rows' => $this->buildRows(),
        ];
    }

    /**
     * 項目（ユニット）×見積先（業者）をフラットな行に展開する。
     * 同一項目の2行目以降は itemLabel を空にして「項目は1回だけ表示」を表現する。
     *
     * @return list<array<string, mixed>>
     */
    private function buildRows(): array
    {
        $units = $this->relationLoaded('units') ? $this->units : collect();
        $rows = [];

        foreach ($units as $unit) {
            $companies = $unit->relationLoaded('companies') ? $unit->companies : collect();

            if ($companies->isEmpty()) {
                $rows[] = $this->row($unit, null, true);

                continue;
            }

            $first = true;
            foreach ($companies as $company) {
                $rows[] = $this->row($unit, $company, $first);
                $first = false;
            }
        }

        return $rows;
    }

    /** @return array<string, mixed> */
    private function row(object $unit, ?object $company, bool $isItemFirst): array
    {
        return [
            'unitId' => (int) $unit->id,
            'companyId' => $company ? (int) $company->id : null,
            // 項目名は同一項目の先頭行だけに表示する。
            'itemLabel' => $isItemFirst ? (string) ($unit->label ?? '') : '',
            'vendorName' => $this->vendorName($company),
            // 標準単価(税抜) = ユニットの単価表価格
            'masterPrice' => Format::yen($unit->master_price),
            // 予算単価(税抜) = ユニットの予算単価
            'budgetPrice' => Format::yen($unit->unit_price),
            // 相見積(税抜) = 当該業者の最新見積書(type=1)の金額
            'quotePrice' => Format::yen($this->latestQuotePrice($company)),
            // 見積依頼状態（相見積送信履歴の有無）
            'requested' => $this->isRequested($company),
            // 発注業者選定（採用フラグ = 1）
            'selected' => $company !== null && (int) $company->adoption_flg === 1,
            // 設計部選定（design_status = 1）
            'designSelected' => $company !== null && (int) $company->design_status === 1,
            // 部長承認（常務承認フラグ fix_status = 1）
            'approved' => $company !== null && (int) $company->fix_status === 1,
            // 取消申請中（cancel_flg >= 1）／取消承認済み（cancel_flg >= 2）
            'cancelRequested' => $company !== null && (int) $company->cancel_flg >= 1,
            'cancelApproved' => $company !== null && (int) $company->cancel_flg >= 2,
        ];
    }

    private function vendorName(?object $company): string
    {
        if ($company === null) {
            return '—';
        }

        return (string) ($company->company?->company_name ?? '（業者未設定）');
    }

    /** type=1（見積書）の最新ファイルの金額。 */
    private function latestQuotePrice(?object $company): int|float|null
    {
        if ($company === null || ! $company->relationLoaded('files')) {
            return null;
        }

        $file = $company->files
            ->where('type', 1)
            ->sortByDesc('date')
            ->sortByDesc('id')
            ->first();

        return $file?->price;
    }

    private function isRequested(?object $company): bool
    {
        if ($company === null || ! $company->relationLoaded('orderHistories')) {
            return false;
        }

        return $company->orderHistories->isNotEmpty();
    }
}

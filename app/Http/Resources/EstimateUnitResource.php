<?php

namespace App\Http\Resources;

use App\Models\EstimateUnit;
use App\Models\EstimateUnitCompany;
use App\Utils\Format;
use App\Utils\StatusLabel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 見積明細ユニット1件分の表示用データ。
 *
 * @mixin EstimateUnit
 */
class EstimateUnitResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $adopted = $this->adoptedCompany();

        return [
            'id' => $this->id,
            'label' => $this->label ?? '',
            'subCateLabel' => StatusLabel::subCate($this->sub_cate),
            'useFlg' => (int) $this->use_flg,

            // 金額（税抜）
            'masterPrice' => Format::yen($this->master_price),    // 単価表
            'estimatePrice' => Format::yen($this->tmp_unit_price), // 概算

            // 工期設定（tmp 優先、無ければ fix）
            'construct' => $this->periodCell($this->tmp_construct_at, $this->fix_construct_at, '着工'),
            'completion' => $this->periodCell($this->tmp_completion_at, $this->fix_completion_at, '完了'),

            // 承認納期日（採用業者の回答日）
            'replyDate' => $this->replyDateCell($adopted),

            // 発注書送付日（採用業者の発注書）
            'orderDate' => $this->orderDateCell($adopted),

            // 必須ファイル（採用業者の type=2 ファイル）
            'requiredFile' => $this->requiredFileCell($adopted),

            'companies' => EstimateUnitCompanyResource::collection($this->whenLoaded('companies')),
        ];
    }

    private function adoptedCompany(): ?EstimateUnitCompany
    {
        if (! $this->relationLoaded('companies')) {
            return null;
        }

        return $this->companies->firstWhere('adoption_flg', 1)
            ?? $this->companies->first();
    }

    /** 工期セル: 日付（tmp→fix フォールバック）+ ラベル + 設定済みフラグ。 */
    private function periodCell(?string $tmp, ?string $fix, string $label): array
    {
        $date = Format::date($tmp) ?: Format::date($fix);

        return ['date' => $date, 'label' => $label, 'set' => $date !== ''];
    }

    /** 承認納期日: 採用業者の回答日（estimate_unit_company_dates）。 */
    private function replyDateCell(?EstimateUnitCompany $adopted): array
    {
        if ($adopted === null || ! $adopted->relationLoaded('dates')) {
            return ['date' => '', 'state' => 'none', 'label' => ''];
        }

        $replied = $adopted->dates
            ->filter(fn ($d) => ! empty($d->reply_date))
            ->sortByDesc('reply_date')
            ->first();

        if ($replied !== null) {
            $approved = (int) ($replied->adoption_flg ?? 0) === 1;

            return [
                'date' => Format::date($replied->reply_date),
                'state' => $approved ? 'approved' : 'replied',
                'label' => $approved ? '承認済' : '回答済',
            ];
        }

        return ['date' => '', 'state' => 'none', 'label' => '未回答'];
    }

    /** 発注書送付日: 採用業者の発注書（orders）。 */
    private function orderDateCell(?EstimateUnitCompany $adopted): array
    {
        if ($adopted === null || ! $adopted->relationLoaded('orders')) {
            return ['date' => '', 'state' => 'none', 'label' => ''];
        }

        $order = $adopted->orders->sortByDesc('id')->first();

        if ($order === null) {
            return ['date' => '', 'state' => 'none', 'label' => '未発行'];
        }

        $status = (int) ($order->status ?? 0);
        if ($status === 99) {
            return ['date' => Format::date($order->date), 'state' => 'canceled', 'label' => '取消済'];
        }
        if ($status >= 11 || (int) ($order->adoption_flg ?? 0) === 1) {
            return ['date' => Format::date($order->date), 'state' => 'sent', 'label' => '送付済'];
        }

        return ['date' => Format::date($order->date), 'state' => 'pending', 'label' => '送付待ち'];
    }

    /** 必須ファイル: 採用業者の type=2 ファイル件数。 */
    private function requiredFileCell(?EstimateUnitCompany $adopted): array
    {
        if ($adopted === null || ! $adopted->relationLoaded('files')) {
            return ['count' => 0, 'state' => 'none', 'label' => '—'];
        }

        $count = $adopted->files->where('type', 2)->count();

        return [
            'count' => $count,
            'state' => $count > 0 ? 'has' : 'none',
            'label' => $count > 0 ? $count.'件' : '未',
        ];
    }
}

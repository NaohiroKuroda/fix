<?php

namespace App\Http\Resources;

use App\Models\TBuilding;
use App\Utils\Format;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * 請求（もらい）系画面の案件1件分を、フロント（BillingScreen.vue）が扱う
 * 「案件 → 項目 → 請求取引先」のフラット行に整形する。
 *
 * 支払側は {@see PayablePartnerResource}。区分「全て」のときは支払取引先も
 * `displayPartners` に混ざるため、区分に依存しない形で読めるようにしてある。
 *
 * @mixin TBuilding
 */
class BillingPartnerResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            // 見出しの「No.」は felix_total（旧）実行予算 ID（t_buildings.source_id）を表示する。
            'no' => $this->source_id !== null ? (int) $this->source_id : null,
            'name' => (string) $this->name,
            'rows' => $this->buildRows(),
        ];
    }

    /**
     * 項目 × 取引先をフラットな行に展開する。
     *
     * @return list<array<string, mixed>>
     */
    private function buildRows(): array
    {
        $items = $this->relationLoaded('budgetItems') ? $this->budgetItems : collect();
        $rows = [];

        foreach ($items as $item) {
            $partners = $item->relationLoaded('displayPartners') ? $item->displayPartners : collect();
            foreach ($partners as $partner) {
                $rows[] = $this->row($item, $partner);
            }
        }

        return $rows;
    }

    /**
     * @param  object  $item  建物予算項目（TBuildingBudgetItem）
     * @param  object  $partner  請求取引先（TBillingPartner）または支払取引先（TPayablePartner）
     * @return array<string, mixed>
     */
    private function row(object $item, object $partner): array
    {
        // 見積は請求側にしか無い（支払行は latestQuotation を読み込んでいない）。
        $isBilling = (bool) ($partner->billing_target ?? false);
        $quotation = $isBilling ? $partner->latestQuotation : null;

        return [
            'partnerId' => (int) $partner->id,
            'itemName' => (string) ($item->name ?? ''),
            'vendorName' => (string) ($partner->company?->company_name ?? '（取引先未設定）'),
            // felix_total（旧画面）リンクは source_id で組む。
            'vendorDetailUrl' => $this->felixUrl('felix.vendor_detail_url', $partner->source_id),
            'addVendorUrl' => $this->felixUrl('felix.add_vendor_url', $item->source_id),
            'approvalStatus' => (string) ($partner->approval_status ?? 'DRAFT'),
            // 区分（請求＝もらい / 支払＝はらい）。行の地色・バッジに使う。
            'billingTarget' => $isBilling,
            // 金額は BCMath 前提のため文字列で渡す（frontend.md §4.9）。未作成は null。
            'quotationAmount' => $quotation?->amount_excluding_tax === null
                ? null
                : (string) $quotation->amount_excluding_tax,
            // Format::date は未設定を '' で返すため、画面契約（null）に揃える。
            'quotationDate' => Format::date($quotation?->quotation_date) ?: null,
            // 業者の発注承諾日（t_billing_quotations.accepted_at）。未承諾は null。
            'acceptedAt' => Format::date($quotation?->accepted_at) ?: null,
            // やり取り（コメント）のメタ情報（項目単位。リポジトリが付与）。
            'messageCount' => (int) ($partner->comments_count ?? 0),
            'hasComments' => (bool) ($partner->has_comments ?? false),
            'unreadCount' => (int) ($partner->unread_count ?? 0),
            // 見積作成モーダルの初期値（「見積修正」で開いたときに使う）。未作成は null。
            'quotation' => $quotation === null ? null : $this->quotation($quotation),
            // この画面で操作できる行か（処理フロー J列）。false は一覧に出すが操作させない（K列）。
            'operable' => (bool) ($partner->operable ?? false),
        ];
    }

    /**
     * 請求見積（最新版）＋明細をモーダルの初期値の形に整える。
     *
     * @return array<string, mixed>
     */
    private function quotation(object $quotation): array
    {
        $details = $quotation->relationLoaded('details') ? $quotation->details : collect();

        return [
            'id' => (int) $quotation->id,
            // input[type=date] にそのまま入れるため `Y-m-d`（未設定は空文字）。
            'quotationDate' => $this->isoDate($quotation->quotation_date),
            'amountExcludingTax' => (string) $quotation->amount_excluding_tax,
            'taxAdjust' => (string) ($quotation->tax_adjust ?? '0'),
            'withholdingIncomeTax' => $quotation->withholding_income_tax === null
                ? null
                : (string) $quotation->withholding_income_tax,
            'comment' => (string) ($quotation->comment ?? ''),
            'fileUrl' => (string) ($quotation->file_url ?? ''),
            'details' => $details->map(fn (object $d) => [
                'id' => (int) $d->id,
                'isMemo' => (bool) $d->is_memo,
                'branchCode' => $d->branch_code === null ? null : (int) $d->branch_code,
                'departmentId' => $d->department_id === null ? null : (int) $d->department_id,
                'name' => (string) ($d->name ?? ''),
                'quantity' => $d->quantity === null ? null : (int) $d->quantity,
                'unitId' => $d->unit_id === null ? null : (int) $d->unit_id,
                'unitPrice' => $d->unit_price === null ? null : (string) $d->unit_price,
                'taxType' => (string) ($d->tax_type ?? 'TAXABLE'),
                'taxRate' => (string) ($d->tax_rate ?? '0.10'),
                'isTaxInclusive' => (bool) $d->is_tax_inclusive,
                'price' => $d->price === null ? null : (string) $d->price,
                // 保存済みの明細は常に「使用中」。空の予備行は保存しないため。
                'isChanged' => true,
            ])->values()->all(),
        ];
    }

    /** `YYYY-MM-DD`（input[type=date] 用）。未設定は空文字。 */
    private function isoDate(mixed $value): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00') {
            return '';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return '';
        }
    }

    /** config テンプレートの {id} を source_id に置換した felix_total URL（未設定/不在は null）。 */
    private function felixUrl(string $configKey, int|string|null $sourceId): ?string
    {
        $template = config($configKey);

        if ($sourceId === null || $sourceId === '' || ! $template) {
            return null;
        }

        return str_replace('{id}', (string) $sourceId, (string) $template);
    }
}

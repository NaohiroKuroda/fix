<?php

namespace App\Http\Resources;

use App\Models\TBuilding;
use App\Utils\Format;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 新スキーマ（t_buildings → t_building_cost_items → t_cost_quotations）の案件1件分を、
 * フロント（QuotationManagementScreen.vue）が扱う「案件 → 項目 → 見積先」のフラット行に整形する。
 *
 * felix_total（旧）画面へのリンクは source_id（旧 id）で組み立てる。
 *
 * @mixin TBuilding
 */
class BuildingQuotationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            // 見出しの「No.」は felix_total（旧）実行予算 ID（t_buildings.source_id = estimates.id）を表示する。
            'no' => $this->source_id !== null ? (int) $this->source_id : null,
            'name' => (string) $this->building_name,
            'rows' => $this->buildRows(),
        ];
    }

    /**
     * 項目 × 見積先をフラットな行に展開する。
     *
     * @return list<array<string, mixed>>
     */
    private function buildRows(): array
    {
        $items = $this->relationLoaded('costItems') ? $this->costItems : collect();
        $rows = [];

        foreach ($items as $item) {
            $quotations = $item->relationLoaded('quotations') ? $item->quotations : collect();

            if ($quotations->isEmpty()) {
                $rows[] = $this->row($item, null);

                continue;
            }

            foreach ($quotations as $quotation) {
                $rows[] = $this->row($item, $quotation);
            }
        }

        return $rows;
    }

    /**
     * @param  object  $item  明細項目（BuildingCostItem）
     * @param  object|null  $quotation  見積先（CostQuotation）
     * @return array<string, mixed>
     */
    private function row(object $item, ?object $quotation): array
    {
        $status = $quotation?->approval_status;
        // 見積依頼の送信回数（t_cost_quotation_requests の件数）。リポジトリが withCount で付与。
        // 0=未依頼。見積依頼以外の画面では未取得のため 0 になる。
        $sendCount = (int) ($quotation?->requests_count ?? 0);

        return [
            'unitId' => (int) $item->id,
            'companyId' => $quotation ? (int) $quotation->id : null,
            // 項目名は全行に持たせ、表示側で「表示中の先頭行」にのみ出す（フィルタで先頭行が
            // 消えても項目名が失われないようにするため）。
            'itemName' => (string) ($item->item_name ?? ''),
            'vendorName' => $quotation
                ? (string) ($quotation->company?->company_name ?? '（業者未設定）')
                : '—',
            // felix_total（旧画面）リンクは source_id（旧 estimate_unit_companies.id / estimate_units.id）で組む。
            'vendorDetailUrl' => $this->felixUrl('felix.vendor_detail_url', $quotation?->source_id),
            'vendorUrl' => $this->felixUrl('felix.estimate_edit_url', $quotation?->source_id),
            'addVendorUrl' => $this->felixUrl('felix.add_vendor_url', $item->source_id),
            'masterPrice' => Format::yen($item->master_price),
            'budgetPrice' => Format::yen($item->budget_price),
            // 表示用見積額（リポジトリが mode に応じて付与：業者選定=相見積 / それ以外=確定見積）。
            'quotePrice' => Format::yen($quotation?->display_quote),
            // 見積依頼済み（送信回数が 1 回以上）。未依頼＝送信回数 0。
            'requested' => $sendCount > 0,
            // 見積依頼の送信回数（一覧の列・未依頼絞り込みに使用）。
            'sendCount' => $sendCount,
            // 最終依頼日時（t_cost_quotation_requests.requested_at の最大値）。リポジトリが withMax で付与。
            // 未依頼（0 件）および見積依頼以外の画面（未取得）は null。
            'lastRequestedAt' => Format::dateTime($quotation?->requests_max_requested_at),
            // やり取り（コメント）の件数（費用項目単位。業者選定・部長承認の「やり取り」列に表示）。
            'messageCount' => (int) ($quotation?->comments_count ?? 0),
            // やり取り（コメント）が1件以上あるか。コメントボタンの配色（選定ボタンと同色）に使う。
            'hasComments' => (bool) ($quotation?->has_comments ?? false),
            // やり取りの未読数（ログインユーザーの最終既読より新しい他者コメント）。0=未読なし。
            'unreadCount' => (int) ($quotation?->unread_count ?? 0),
            // 選定済み（業者未選定でない）。
            'selected' => $status !== null && $status !== 'UNSELECTED',
            // 部長承認済み（部長承認済 / 完了）。
            'approved' => in_array($status, ['MANAGER_APPROVED', 'APPROVED'], true),
            // 取消申請中。
            'cancelRequested' => $status === 'CANCEL_REQUESTED',
            // 取消承認済み（完了）。
            'cancelApproved' => $status === 'APPROVED',
            // 仮選定（t_cost_quotations.is_drafted）。
            'provisional' => $quotation !== null && (int) $quotation->is_drafted === 1,
            // 請求先（t_cost_quotations.is_billing_target）。業者追加時に「請求先とする」をONにした業者。
            'billingTarget' => $quotation !== null && (int) $quotation->is_billing_target === 1,
            // 部長承認で否認され業者選定へ差し戻された（deny_comment あり）。ボタンの赤色表示に使う。
            'denied' => $quotation !== null && $quotation->deny_comment !== null,
        ];
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

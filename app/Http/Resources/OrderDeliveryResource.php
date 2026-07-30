<?php

namespace App\Http\Resources;

use App\Models\TBuilding;
use App\Utils\Format;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 発注〜納品〜請求フローの案件1件分を、見積管理（{@see BuildingQuotationResource}）と同じ
 * 「案件 → 項目 → 見積先」のフラット行に整形する。フロント（OrderDeliveryScreen /
 * OrderProjectCard）が見積管理とほぼ同じUIで扱えるようにする。
 *
 * @mixin TBuilding
 */
class OrderDeliveryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'no' => (int) $this->id,
            'name' => (string) $this->building_name,
            // 発注書（発注実行・発注承認画面のボタン）で開く felix_total の発注書確認画面 URL。
            // 物件（t_buildings.source_id = 旧 estimates.id）で絞り込む。移行元が無ければ null。
            'orderDocumentUrl' => $this->felixUrl('felix.order_document_url', $this->source_id),
            // 報告書提出日リンク（完了確認・部長完了承認画面）で開く felix_total の必須ファイルタブ URL。
            'completionReportUrl' => $this->felixUrl('felix.completion_report_url', $this->source_id),
            'rows' => $this->buildRows(),
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

    /** @return list<array<string, mixed>> */
    private function buildRows(): array
    {
        $items = $this->relationLoaded('costItems') ? $this->costItems : collect();
        $rows = [];

        foreach ($items as $item) {
            $quotations = $item->relationLoaded('quotations') ? $item->quotations : collect();

            if ($quotations->isEmpty()) {
                continue;
            }

            foreach ($quotations as $quotation) {
                $rows[] = $this->row($item, $quotation);
            }
        }

        return $rows;
    }

    /**
     * @param  object  $item  明細項目（TBuildingCostItem）
     * @param  object  $quotation  見積先（TCostQuotation）
     * @return array<string, mixed>
     */
    private function row(object $item, object $quotation): array
    {
        $order = $quotation->order;
        $report = $order?->deliveryReport;
        $invoice = $report?->invoice;

        return [
            'unitId' => (int) $item->id,
            // チャット・アクションの単位＝見積先ID（t_cost_quotations.id）。見積管理と同じ。
            'companyId' => (int) $quotation->id,
            'itemName' => (string) ($item->item_name ?? ''),
            'vendorName' => (string) ($quotation->company?->company_name ?? '（業者未設定）'),
            // 区分（もらい＝請求 / 払い＝支払）。見積依頼画面と同じ t_cost_quotations.is_billing_target。
            // 業者承諾確認画面の「区分」列に出す（{@see \App\Http\Resources\BuildingQuotationResource}）。
            'billingTarget' => (int) $quotation->is_billing_target === 1,
            // 金額列。発注実行=標準単価/予算単価/相見積、発注承認以降=予算単価/見積/発注。
            'masterPrice' => Format::yen($item->master_price),
            'budgetPrice' => Format::yen($item->budget_price),
            // 見積（相見積）＝業者の見積額（最新の相見積履歴）。
            'quotePrice' => Format::yen(optional($quotation->latestHistory)->amount_excluding_tax),
            // 発注＝発注金額（発注前は null）。
            'orderPrice' => Format::yen($order?->amount),
            // 承諾の残り期限（日数）。発注日 + 承諾期限（10日）- 今日。発注前は null。
            'deadlineDays' => $order?->order_date
                ? (int) now()->startOfDay()->diffInDays($order->order_date->copy()->addDays(10)->startOfDay(), false)
                : null,
            // 進捗の補助表示（発注日・承諾日時・提出日時）。
            'orderDate' => optional($order?->order_date)->format('Y-m-d'),
            'vendorAcceptedAt' => optional($order?->vendor_accepted_at)->format('Y-m-d H:i'),
            'submittedAt' => optional($report?->submitted_at)->format('Y-m-d H:i'),
            // 完了確認画面（請求）：請求書は確認と同時に自動作成される。未作成なら null。
            'invoiceAmount' => Format::yen($invoice?->amount),
            'invoiceStatus' => $invoice?->invoice_status,
            'invoiceSubmittedAt' => optional($invoice?->submitted_at)->format('Y-m-d H:i'),
            'invoiceApprovedAt' => optional($invoice?->closed_at)->format('Y-m-d H:i'),
            // やり取り（コメント）メタ（費用項目単位）。
            'messageCount' => (int) ($quotation->comments_count ?? 0),
            'hasComments' => (bool) ($quotation->has_comments ?? false),
            'unreadCount' => (int) ($quotation->unread_count ?? 0),
        ];
    }
}

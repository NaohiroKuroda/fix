<?php

namespace App\Http\Resources;

use App\Models\TBuilding;
use App\Utils\Format;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 発注〜納品〜請求フローの案件1件分を、見積管理（{@see PayablePartnerResource}）と同じ
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
            'name' => (string) $this->name,
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
        // 2026-08 のスキーマ改訂で t_building_cost_items → t_building_budget_items、
        // t_cost_quotations → t_payable_partners へ改称された（リレーション名も budgetItems /
        // payablePartners）。旧名（costItems / quotations）のままだと明細が常に空になる。
        $items = $this->relationLoaded('budgetItems') ? $this->budgetItems : collect();
        $rows = [];

        foreach ($items as $item) {
            $partners = $item->relationLoaded('payablePartners') ? $item->payablePartners : collect();

            if ($partners->isEmpty()) {
                continue;
            }

            foreach ($partners as $partner) {
                $rows[] = $this->row($item, $partner);
            }
        }

        return $rows;
    }

    /**
     * @param  object  $item  建物予算項目（TBuildingBudgetItem）
     * @param  object  $quotation  支払取引先（TPayablePartner）
     * @return array<string, mixed>
     */
    private function row(object $item, object $quotation): array
    {
        // 発注書（t_payable_orders）。金額・業者の承諾日時はこれだけを見る。
        $payableOrder = $quotation->payableOrder;

        return [
            'unitId' => (int) $item->id,
            // チャット・アクションの単位＝見積先ID（t_payable_partners.id）。見積管理と同じ。
            'companyId' => (int) $quotation->id,
            'itemName' => (string) ($item->name ?? ''),
            'vendorName' => (string) ($quotation->company?->company_name ?? '（業者未設定）'),
            // 区分（もらい＝請求 / 払い＝支払）。2026-08 のスキーマ改訂で請求は t_billing_partners へ
            // 分離されたため、支払テーブル由来の本画面の行は全て支払。
            // 業者承諾確認画面の「区分」列に出す（{@see \App\Http\Resources\PayablePartnerResource}）。
            'billingTarget' => false,
            // 金額列。発注実行=標準単価/予算単価/相見積、発注承認以降=予算単価/見積/発注。
            'masterPrice' => Format::yen($item->master_price),
            'budgetPrice' => Format::yen($item->budget_price),
            // 見積（相見積）＝業者の見積額（最新の相見積 = t_payable_quotations.is_latest）。
            // リポジトリが `latestQuotation` を eager load している（存在しない `latestHistory` を
            // 読んでいたため、この列は常に空になっていた）。
            'quotePrice' => Format::yen(optional($quotation->latestQuotation)->subtotal_amount),
            // 発注金額＝発注書の税別合計（t_payable_orders.subtotal_amount）。発注前は null。
            'orderPrice' => Format::yen($payableOrder?->subtotal_amount),
            // 承諾の残り期限（日数）。発注日 + 承諾期限（10日）- 今日。
            // 発注日は旧 t_orders.order_date にしか無く、テーブル廃止に伴い出せなくなった。
            'deadlineDays' => null,
            // 進捗の補助表示（発注日・承諾日時）。発注日は上記のとおり保持先が無い。
            'orderDate' => null,
            // 業者の承諾日時（完了確認の「報告書提出日」に使うため時刻まで持つ）。未承諾は null。
            'vendorAcceptedAt' => optional($payableOrder?->contract_approved_at)->format('Y-m-d H:i'),
            // 発注承諾日（業者承諾確認の列）。日付だけを出す（請求側の同名列と表記を揃える）。
            'orderAcceptedAt' => Format::date($payableOrder?->contract_approved_at) ?: null,
            // 完了報告・請求の各項目は、旧 t_delivery_reports / t_invoices の廃止に伴い保持先が無い。
            // 停止中の完了確認・請求取消承認画面が参照するキーなので、形だけ残して null を返す。
            'submittedAt' => null,
            'invoiceAmount' => null,
            'invoiceStatus' => null,
            'invoiceSubmittedAt' => null,
            'invoiceApprovedAt' => null,
            // やり取り（コメント）メタ（費用項目単位）。
            'messageCount' => (int) ($quotation->comments_count ?? 0),
            'hasComments' => (bool) ($quotation->has_comments ?? false),
            'unreadCount' => (int) ($quotation->unread_count ?? 0),
        ];
    }
}

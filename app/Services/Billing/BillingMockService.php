<?php

namespace App\Services\Billing;

/**
 * 請求（もらい）系画面のモックデータ提供。
 *
 * 画面（サイドメニュー・レイアウト・操作フロー）の確認用に、固定のダミーデータを返す。
 * DB（t_billing_partners / t_billing_quotations / t_billing_quotation_ditails）へは未接続。
 * 実データ接続時は Repository を挟んだ BillingQuotationService へ置き換える。
 *
 * @see docs/detailed-design/quotations/06_請求_見積作成_詳細設計.md
 * @see docs/operations/もらい_運用フロー.drawio
 */
final class BillingMockService
{
    /** 1ページあたりの案件数（実装時は AbstractQuotationScreenController::PER_PAGE と揃える）。 */
    private const PER_PAGE = 10;

    /**
     * 画面モード → 表示対象の承認ステータス。
     * 値はテーブル定義書（t_billing_partners.approval_status）に準拠する。
     *
     * @var array<string, list<string>>
     */
    private const MODE_STATUSES = [
        'billing-quote-create' => ['UNSELECTED'],
        'billing-quote-approval' => ['STAFF_APPROVED'],
        'billing-cancel-request' => ['APPROVED'],
        'billing-cancel-approval' => ['CANCEL_APPLIED'],
        'billing-order-confirmation' => ['APPROVED'],
    ];

    /**
     * 画面表示用の projects / pagination を返す。
     *
     * @param  string  $mode  画面モード（MODE_STATUSES のキー）
     * @param  array<string, mixed>  $filters  絞り込み条件（keyword / itemLabel / vendor）
     * @return array{projects: list<array<string, mixed>>, pagination: array<string, int|null>}
     */
    public function screen(string $mode, array $filters): array
    {
        $statuses = self::MODE_STATUSES[$mode] ?? [];
        $projects = [];

        foreach ($this->buildings() as $building) {
            $rows = [];
            foreach ($building['rows'] as $row) {
                if (! in_array($row['approvalStatus'], $statuses, true)) {
                    continue;
                }
                if (! $this->matches($building['name'], $row, $filters)) {
                    continue;
                }
                $rows[] = $row;
            }

            if ($rows !== []) {
                $projects[] = [
                    'id' => $building['id'],
                    'no' => $building['no'],
                    'name' => $building['name'],
                    'rows' => $rows,
                ];
            }
        }

        return [
            'projects' => $projects,
            'pagination' => [
                'currentPage' => 1,
                'lastPage' => 1,
                'perPage' => self::PER_PAGE,
                'total' => count($projects),
                'from' => $projects === [] ? null : 1,
                'to' => $projects === [] ? null : count($projects),
            ],
        ];
    }

    /**
     * 絞り込み条件に一致するか（部分一致・大文字小文字は区別しない）。
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $filters
     */
    private function matches(string $buildingName, array $row, array $filters): bool
    {
        $like = static fn (?string $needle, string $haystack): bool => $needle === null || $needle === ''
            || mb_stripos($haystack, $needle) !== false;

        return $like($filters['keyword'] ?? null, $buildingName)
            && $like($filters['itemLabel'] ?? null, (string) $row['itemName'])
            && $like($filters['vendor'] ?? null, (string) $row['vendorName']);
    }

    /**
     * モックの案件（実行予算）一覧。
     *
     * 金額は BCMath 前提のため **文字列**で持つ（docs/architecture/backend.md §5.5）。
     *
     * @return list<array<string, mixed>>
     */
    private function buildings(): array
    {
        return [
            [
                'id' => 9001,
                'no' => 1,
                'name' => '（モック）レジデンス青葉台 新築工事',
                'rows' => [
                    $this->row(1, '外構工事', '青葉ランドスケープ株式会社', 'UNSELECTED', null, null, null, 2, 1),
                    $this->row(2, '外構工事', '緑化サービス株式会社', 'UNSELECTED', null, null, null, 0, 0),
                    $this->row(3, '電気設備工事', '東邦電設株式会社', 'STAFF_APPROVED', '1850000', '2026/08/18', null, 3, 0),
                    $this->row(4, '給排水衛生設備工事', '大栄設備工業株式会社', 'APPROVED', '2460000', '2026/08/12', '2026/08/20', 1, 0),
                ],
            ],
            [
                'id' => 9002,
                'no' => 2,
                'name' => '（モック）パークサイド中央 大規模修繕',
                'rows' => [
                    $this->row(5, '足場仮設工事', '中央仮設工業株式会社', 'STAFF_APPROVED', '980000', '2026/08/19', null, 0, 0),
                    $this->row(6, '防水工事', '日新防水株式会社', 'APPROVED', '3120000', '2026/08/05', null, 2, 1),
                    $this->row(7, '塗装工事', '彩光塗装株式会社', 'CANCEL_APPLIED', '1740000', '2026/08/01', null, 4, 2),
                ],
            ],
            [
                'id' => 9003,
                'no' => 3,
                'name' => '（モック）グランメゾン港南 内装工事',
                'rows' => [
                    $this->row(8, '内装仕上工事', '港南インテリア株式会社', 'UNSELECTED', null, null, null, 0, 0),
                    $this->row(9, '建具工事', '湘南建具製作所', 'CANCEL_APPLIED', '640000', '2026/07/28', null, 1, 1),
                ],
            ],
        ];
    }

    /**
     * モックの明細行（請求取引先1件）。
     *
     * @param  int  $partnerId  t_billing_partners.id 相当
     * @param  string|null  $amount  請求見積の税抜合計（文字列。未作成は null）
     * @param  string|null  $quotationDate  見積日（Y/m/d。未作成は null）
     * @param  string|null  $acceptedAt  業者の発注承諾日（Y/m/d。未承諾は null）
     * @return array<string, mixed>
     */
    private function row(
        int $partnerId,
        string $itemName,
        string $vendorName,
        string $approvalStatus,
        ?string $amount,
        ?string $quotationDate,
        ?string $acceptedAt,
        int $messageCount,
        int $unreadCount,
    ): array {
        return [
            'partnerId' => $partnerId,
            'itemName' => $itemName,
            'vendorName' => $vendorName,
            // モックのためリンク先は持たせない（実装時は config('felix.vendor_detail_url') を使う）。
            'vendorDetailUrl' => null,
            'addVendorUrl' => null,
            'approvalStatus' => $approvalStatus,
            'quotationAmount' => $amount,
            'quotationDate' => $quotationDate,
            'acceptedAt' => $acceptedAt,
            'messageCount' => $messageCount,
            'hasComments' => $messageCount > 0,
            'unreadCount' => $unreadCount,
        ];
    }
}

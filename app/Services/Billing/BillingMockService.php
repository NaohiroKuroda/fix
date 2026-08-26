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
     * 画面表示用の projects / masters / pagination を返す。
     *
     * @param  string  $mode  画面モード（MODE_STATUSES のキー）
     * @param  array<string, mixed>  $filters  絞り込み条件（keyword / itemLabel / vendor）
     * @return array{projects: list<array<string, mixed>>, masters: array<string, mixed>, pagination: array<string, int|null>}
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
            'masters' => $this->masters(),
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
                    // 下書き保存済み（見積はあるが承認申請していない）。ボタンは「見積修正」になる。
                    $this->row(2, '外構工事', '緑化サービス株式会社', 'UNSELECTED', '740000', '2026/08/22', null, 0, 0),
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
            // 作成済みの請求見積（見積修正でモーダルを開いたときの初期値）。未作成は null。
            'quotation' => $amount === null ? null : $this->quotation($amount, $quotationDate),
        ];
    }

    /**
     * モックの請求見積（t_billing_quotations ＋ t_billing_quotation_details の最新版）。
     *
     * 明細には **is_changed = false の行も混ぜてある**。画面側が「使用中の行だけを表示する」
     * 挙動（felix_total の fix_flg と同じ）を確認できるようにするため。
     *
     * @return array<string, mixed>
     */
    private function quotation(string $amount, ?string $quotationDate): array
    {
        $date = $quotationDate === null ? '' : str_replace('/', '-', $quotationDate);
        $half = (string) intdiv((int) $amount, 2);

        return [
            'id' => null,
            'quotationDate' => $date,
            'amountExcludingTax' => $amount,
            'taxAdjust' => '0',
            'withholdingIncomeTax' => null,
            'comment' => '（モック）TEL にて金額調整済み。',
            'fileUrl' => '',
            'details' => [
                [
                    'id' => null, 'isMemo' => true, 'branchCode' => null, 'departmentId' => null,
                    'name' => '【モック】現地調査ののち、下記の通りお見積りいたします。',
                    'quantity' => null, 'unitId' => null, 'unitPrice' => null,
                    'taxType' => 'TAXABLE', 'taxRate' => '0.10', 'isTaxInclusive' => false,
                    'price' => null, 'isChanged' => true,
                ],
                [
                    'id' => null, 'isMemo' => false, 'branchCode' => 1, 'departmentId' => 11,
                    'name' => '（モック）材料費', 'quantity' => 1, 'unitId' => 1,
                    'unitPrice' => $half, 'taxType' => 'TAXABLE', 'taxRate' => '0.10',
                    'isTaxInclusive' => false, 'price' => $half, 'isChanged' => true,
                ],
                [
                    'id' => null, 'isMemo' => false, 'branchCode' => 1, 'departmentId' => 11,
                    'name' => '（モック）施工費', 'quantity' => 1, 'unitId' => 1,
                    'unitPrice' => (string) ((int) $amount - (int) $half), 'taxType' => 'TAXABLE', 'taxRate' => '0.10',
                    'isTaxInclusive' => false, 'price' => (string) ((int) $amount - (int) $half), 'isChanged' => true,
                ],
                [
                    // is_changed = false の空行。画面には表示されない。
                    'id' => null, 'isMemo' => false, 'branchCode' => null, 'departmentId' => null,
                    'name' => '', 'quantity' => null, 'unitId' => null, 'unitPrice' => null,
                    'taxType' => 'TAXABLE', 'taxRate' => '0.10', 'isTaxInclusive' => false,
                    'price' => null, 'isChanged' => false,
                ],
            ],
        ];
    }

    /**
     * モーダルの選択肢（拠点 / 部署 / 単位）。
     *
     * 実装時は 拠点＝config('constant.branch_list')、部署＝departments、単位＝master_units から引く。
     * ここでは DB 未接続のため代表的な値を返す。
     *
     * @return array<string, mixed>
     */
    private function masters(): array
    {
        return [
            'branches' => [
                ['code' => 99, 'name' => '全社'],
                ['code' => 1, 'name' => '名古屋'],
                ['code' => 6, 'name' => '東京'],
                ['code' => 2, 'name' => '静岡'],
                ['code' => 5, 'name' => '松本'],
                ['code' => 3, 'name' => '三河'],
                ['code' => 4, 'name' => '豊橋'],
            ],
            'departments' => [
                ['id' => 11, 'name' => '建設部'],
                ['id' => 14, 'name' => '設計部'],
                ['id' => 12, 'name' => '不動産事業部'],
                ['id' => 1, 'name' => 'プロパティ事業部'],
                ['id' => 10, 'name' => '共通部門'],
            ],
            'units' => [
                ['id' => 1, 'name' => '式'], ['id' => 2, 'name' => '個'], ['id' => 3, 'name' => '㎡'],
                ['id' => 4, 'name' => 'm'], ['id' => 5, 'name' => 'セット'], ['id' => 6, 'name' => '本'],
                ['id' => 8, 'name' => '台'], ['id' => 9, 'name' => '枚'], ['id' => 17, 'name' => 'ヶ月'],
                ['id' => 18, 'name' => 'ヶ所'], ['id' => 19, 'name' => '回'], ['id' => 30, 'name' => '日'],
            ],
        ];
    }
}

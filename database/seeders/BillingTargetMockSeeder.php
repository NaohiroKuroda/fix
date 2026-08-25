<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * 【廃止】見積依頼画面に「請求（もらい）」行を出すためのダミーデータ投入。
 *
 * 2026-08 のスキーマ改訂で見積先が支払／請求で分割され（支払＝`t_payable_partners` /
 * 請求＝`t_billing_partners`）、本シーダーが立てていた `t_cost_quotations.is_billing_target`
 * 列そのものが無くなったため、処理を停止している。
 *
 * 請求（もらい）のダミーデータが必要になったら、`t_billing_partners` /
 * `t_billing_quotations` へ投入するシーダーとして作り直すこと
 * （仕様: docs/detailed-design/quotations/06_請求_見積作成_詳細設計.md）。
 */
class BillingTargetMockSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->warn(
            '[BillingTargetMockSeeder] 廃止済みです。'
            .'請求（もらい）は t_billing_partners へ分離されたため、本シーダーは何もしません。'
        );
    }
}

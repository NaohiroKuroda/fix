<?php

namespace Database\Seeders;

use App\Http\Resources\PayablePartnerResource;
use App\Models\TBuilding;
use App\Models\TBuildingBudgetItem;
use App\Models\TPayablePartner;
use App\Models\TPayableQuotation;
use App\Models\TPayableQuotationRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 見積依頼（quote-request）画面用のダミーデータを、ローカル felix_total の実データから作る。
 *
 * 見積依頼画面は t_cost_quotations.source_id が NOT NULL（felix_total 移行済み）の見積先のみ並べ、
 * 画面上のリンク（見積先名＝業者詳細 / 見積編集 / 業者追加）は source_id を felix_total の
 * estimate_unit_companies.id / estimate_units.id に埋め込んで組み立てる
 * （{@see PayablePartnerResource}、{@see config/felix.php}）。
 *
 * source_id を実在する estimate_unit_companies.id 等に一致させることで、リンク・ボタンが
 * ローカル felix_total の「既存Fix画面」を実際に開けるようにする。
 *
 * マッピング:
 *   t_buildings.source_id          = estimates.id
 *   t_building_cost_items.source_id = estimate_units.id
 *   t_cost_quotations.source_id    = estimate_unit_companies.id
 */
class QuoteRequestMockSeeder extends Seeder
{
    /** 見積依頼画面に載せる実在の estimates.id（ローカル felix_total）。 */
    private array $estimateIds = [732, 731, 730, 729, 728, 727];

    /** 1物件あたりの項目（estimate_units）数と、1項目あたりの見積先（会社）数の上限。 */
    private int $unitsPerBuilding = 3;

    private int $companiesPerUnit = 3;

    public function run(): void
    {
        // 再実行に備え、前回の見積依頼デモ物件（division_code = 'QR-*'）を掃除する。
        $oldBuildingIds = TBuilding::where('division_code', 'like', 'QR-%')->pluck('id');
        if ($oldBuildingIds->isNotEmpty()) {
            $oldItemIds = TBuildingBudgetItem::whereIn('building_id', $oldBuildingIds)->pluck('id');
            $oldQuotationIds = TPayablePartner::whereIn('building_budget_item_id', $oldItemIds)->pluck('id');
            TPayableQuotation::whereIn('payable_partner_id', $oldQuotationIds)->forceDelete();
            TPayableQuotationRequest::whereIn('payable_partner_id', $oldQuotationIds)->forceDelete();
            TPayablePartner::whereIn('id', $oldQuotationIds)->forceDelete();
            TBuildingBudgetItem::whereIn('id', $oldItemIds)->forceDelete();
            TBuilding::whereIn('id', $oldBuildingIds)->forceDelete();
        }

        $made = 0;

        foreach ($this->estimateIds as $bi => $estimateId) {
            $estimate = DB::table('estimates')->where('id', $estimateId)->whereNull('deleted_at')->first();
            if (! $estimate) {
                continue;
            }

            // この見積（estimate）配下で、会社（company_id>0）が付いている項目（estimate_units）を選ぶ。
            $units = DB::table('estimate_units as u')
                ->join('estimate_unit_companies as c', 'c.estimate_unit_id', '=', 'u.id')
                ->where('u.estimate_id', $estimateId)
                ->whereNull('u.deleted_at')->whereNull('c.deleted_at')
                ->where('c.company_id', '>', 0)
                ->whereNotNull('u.label')->where('u.label', '!=', '')
                ->groupBy('u.id', 'u.label', 'u.master_price', 'u.price')
                ->orderByDesc(DB::raw('count(c.id)'))
                ->limit($this->unitsPerBuilding)
                ->get(['u.id', 'u.label', 'u.master_price', 'u.price']);

            if ($units->isEmpty()) {
                continue;
            }

            $building = TBuilding::create([
                'name' => (string) $estimate->name,
                'division_code' => sprintf('QR-%03d', $bi + 1),
                'source_id' => (int) $estimate->id,
                'created_at' => now()->subDays(rand(5, 60)),
                'updated_at' => now(),
            ]);

            // この物件で作った見積先（払い）を集めておき、後で1〜2件だけ「もらい（請求先）」に転換する。
            // 実際の発生率（1物件につき1件、多くて2件）に合わせるため、項目ごとではなく物件単位で選ぶ。
            $buildingQuotations = [];

            foreach ($units as $sort => $unit) {
                $master = (int) ($unit->master_price > 0 ? $unit->master_price : ($unit->price > 0 ? $unit->price : rand(500, 5000) * 10000));

                $item = new TBuildingBudgetItem;
                $item->forceFill([
                    'building_id' => $building->id,
                    'item_kind' => 6,
                    'sort' => $sort + 1,
                    'is_enabled' => true,
                    'name' => (string) $unit->label,
                    'master_price' => $master,
                    'budget_price' => (int) ($master * 0.95),
                    'quotation_amount' => null,
                    'source_id' => (int) $unit->id,
                    'created_at' => now()->subDays(rand(5, 50)),
                    'updated_at' => now(),
                ]);
                $item->save();

                // この項目の見積先（会社）を実データから採用。source_id = estimate_unit_companies.id。
                $companies = DB::table('estimate_unit_companies')
                    ->where('estimate_unit_id', $unit->id)
                    ->whereNull('deleted_at')
                    ->where('company_id', '>', 0)
                    ->orderBy('id')
                    ->limit($this->companiesPerUnit)
                    ->get(['id', 'company_id']);

                foreach ($companies as $euc) {
                    $quotation = TPayablePartner::create([
                        'building_budget_item_id' => $item->id,
                        'company_id' => (int) $euc->company_id,
                        'branch_company_id' => null,
                        'counter_company_id' => null,
                        'is_drafted' => true,
                        'approval_status' => 'UNSELECTED',
                        'source_id' => (int) $euc->id,
                        'created_at' => now()->subDays(rand(1, 40)),
                        'updated_at' => now(),
                    ]);
                    $made++;
                    $buildingQuotations[] = ['quotation' => $quotation, 'master' => $master];
                }
            }

            // 物件単位で1件（多くて2件）だけ「もらい（請求先）」に転換する
            // （業者追加時の invoice_flg 相当。金額3列・仮選定は画面側で「ー」表示になる）。
            $billingCount = min(count($buildingQuotations), rand(1, 10) <= 7 ? 1 : 2);
            $billingQuotationIds = $billingCount > 0
                ? collect($buildingQuotations)->random($billingCount)->pluck('quotation.id')->all()
                : [];

            foreach ($buildingQuotations as $bq) {
                $quotation = $bq['quotation'];
                $master = $bq['master'];

                if (in_array($quotation->id, $billingQuotationIds, true)) {
                    // 請求先（もらい）は t_billing_partners へ分離されたため、ここでは支払のみを作る。
                    $quotation->update(['is_drafted' => false]);

                    continue;
                }

                // 約6割は業者回答あり（最新の相見積履歴を持つ＝回答済み）。
                if (rand(1, 10) <= 6) {
                    TPayableQuotation::create([
                        'payable_partner_id' => $quotation->id,
                        'is_latest' => true,
                        'file_url' => null,
                        'quotation_date' => now()->subDays(rand(1, 30))->toDateString(),
                        'amount_excluding_tax' => (int) ($master > 0 ? $master * (rand(80, 110) / 100) : rand(100, 3000) * 10000),
                        'tax_adjust' => 0,
                        'withholding_income_tax' => null,
                        'comment' => '御見積を提出いたします。',
                        'created_at' => now()->subDays(rand(1, 30)),
                        'updated_at' => now(),
                    ]);
                }

                // 約3割は依頼済み（残りは未依頼）。画面のトグルで切替確認できるよう混在させる。
                if (rand(1, 10) <= 3) {
                    TPayableQuotationRequest::create([
                        'payable_partner_id' => $quotation->id,
                        'requested_at' => now()->subDays(rand(1, 20)),
                    ]);
                }
            }
        }

        $this->command?->info("見積依頼画面用のダミーデータ（ローカル felix_total 実データ連携）を投入しました。見積先 {$made} 件。");
    }
}

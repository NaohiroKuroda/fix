<?php

namespace Database\Seeders;

use App\Models\TBuilding;
use App\Models\TCostQuotation;
use Illuminate\Database\Seeder;

/**
 * 見積依頼（quote-request）画面の「区分」列に **請求（もらい）** 行を出すためのダミーデータ投入。
 *
 * 区分列は {@see \App\Http\Resources\BuildingQuotationResource} の `billingTarget`
 * ＝ `t_cost_quotations.is_billing_target` をそのまま出しており、1 なら「請求」、0 なら「支払」と表示する
 * （{@see resources/js/components/estimate-management/EstimateProjectCard.vue}）。
 *
 * felix_total 側の対応列は `estimate_unit_companies.invoice_flg`（業者追加時の「請求先とする」）だが、
 * ローカルへ取り込み済みの見積先（t_cost_quotations.source_id が指す EUC）には invoice_flg=1 の行が
 * 1件も含まれていないため、実データからの引き当てでは請求行が 0 件になる。そこで本シーダーで
 * 既存の見積先の一部を「もらい（請求先）」へ転換する。
 *
 * 転換ルールは {@see QuoteRequestMockSeeder}（物件につき1件、多くて2件）に合わせる。
 * 請求先行は見積の対象ではないため `is_drafted` を false に落とす
 * （画面側では金額3列・仮選定が「ー」表示になり、押下即送信のボタンに変わる）。
 *
 * 実行:
 *   php artisan db:seed --class=Database\\Seeders\\BillingTargetMockSeeder
 *
 * 再実行しても結果が変わらないよう、選定は物件ID・項目ID順の決定的な処理にしている。
 */
class BillingTargetMockSeeder extends Seeder
{
    /** 1物件あたりの請求先の上限（実際の発生率に合わせ、原則1件・一部の物件だけ2件）。 */
    private const MAX_PER_BUILDING = 2;

    public function run(): void
    {
        // 再実行に備え、前回の請求先転換を戻す（支払＝通常の見積先に一旦揃える）。
        TCostQuotation::where('is_billing_target', true)
            ->update(['is_billing_target' => false]);

        $made = 0;

        /** @var TBuilding $building */
        foreach (TBuilding::query()->orderBy('id')->cursor() as $building) {
            $target = $building->id % 3 === 0 ? self::MAX_PER_BUILDING : 1;
            $made += $this->convertForBuilding($building, $target);
        }

        $this->command?->info("見積依頼画面の区分列に「請求」を表示するダミーデータを投入しました。請求先 {$made} 件。");
    }

    /**
     * 物件配下の見積先から請求先を選び、`is_billing_target` を立てる。
     *
     * 選定条件は「見積依頼画面に並ぶ行であること」と「まだ何もやり取りが無いこと」の2点。
     * - `source_id` あり: 見積依頼画面は移行済み（source_id あり）の見積先しか並べない
     *   （{@see \App\Repositories\BuildingQuotationRepository::forEstimateManagement()}）。
     * - 見積回答（histories）も依頼送信（requests）も無い: 請求先は相見積の対象ではないため、
     *   既に回答や送信回数を持つ行を請求先へ転換すると画面表示と矛盾する。
     *
     * 請求先／支払の境界線（EstimateProjectCard の isBillingGroupStart）を確認できるよう、
     * 見積先が2件以上ある項目から選ぶ。2件作る物件では別々の項目から1件ずつ選ぶ。
     *
     * @param  int  $target  この物件で作る請求先の件数
     * @return int 実際に転換した件数
     */
    private function convertForBuilding(TBuilding $building, int $target): int
    {
        $items = $building->costItems()
            ->withCount('quotations')
            ->having('quotations_count', '>=', 2)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        $made = 0;

        foreach ($items as $item) {
            if ($made >= $target) {
                break;
            }

            $quotation = $item->quotations()
                ->whereNotNull('source_id')
                ->whereDoesntHave('histories')
                ->whereDoesntHave('requests')
                ->orderBy('id')
                ->first();

            if ($quotation === null) {
                continue;
            }

            // 請求先は見積（相見積）の対象ではないため、仮選定も落としておく。
            $quotation->update(['is_billing_target' => true, 'is_drafted' => false]);
            $made++;
        }

        return $made;
    }
}

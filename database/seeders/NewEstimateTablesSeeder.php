<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\Company;
use App\Models\TAttachment;
use App\Models\TBuilding;
use App\Models\TBuildingCostItem;
use App\Models\TComment;
use App\Models\TCommentReadTimestamp;
use App\Models\TCostQuotation;
use App\Models\TCostQuotationApprovalAction;
use App\Models\TCostQuotationDetail;
use App\Models\TCostQuotationHistory;
use App\Models\TCostQuotationRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 【要改修】費用見積まわり（T_/M_テーブル）のダミーデータ投入。
 *
 * 2026-08 のスキーマ改訂（t_building_cost_items → t_building_budget_items /
 * t_cost_quotations → t_payable_partners・t_billing_partners /
 * t_cost_quotation_histories → t_payable_quotations 等）に追随していないため、処理を停止している。
 * 承認履歴も t_approval_requests / t_approval_actions へ置き換わっており、
 * 本シーダーが書いていた t_cost_quotation_approval_actions は旧設計の名残である。
 *
 * 作り直す場合は最新のテーブル定義書に合わせること。
 */
class NewEstimateTablesSeeder extends Seeder
{
    private array $itemKinds = [2, 3, 5, 6, 7]; // 項目区分（ER図の凡例）

    private array $approvalStatuses = [
        'UNSELECTED', 'STAFF_APPROVED', 'APPROVED', 'CANCEL_APPLIED', 'CANCEL_APPROVED',
    ];

    public function run(): void
    {
        $this->command?->warn(
            '[NewEstimateTablesSeeder] 2026-08 のスキーマ改訂に未追随のため停止中です。'
            .'テーブル定義書に合わせて作り直してください。'
        );

        return;

        // @phpstan-ignore-next-line 以降は旧スキーマ向けの実装（参照用に残している）。
        $adminUserIds = AdminUser::query()->inRandomOrder()->limit(10)->pluck('id')->all();
        $companyIds = Company::query()->inRandomOrder()->limit(30)->pluck('id')->all();

        if (empty($adminUserIds) || empty($companyIds)) {
            $this->command?->warn('admin_users / companies が空のため中断しました。');

            return;
        }

        // --- m_approval_flows / m_approval_flow_steps ---
        $flowId = DB::table('m_approval_flows')->insertGetId([
            'flow_code' => 'COST_QUOTATION',
            'flow_name' => '費用見積承認フロー',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $steps = [
            ['step_name_code' => 'STAFF', 'approver_type' => 'ROLE'],
            ['step_name_code' => 'MANAGER', 'approver_type' => 'ROLE'],
            ['step_name_code' => 'DESIGN', 'approver_type' => 'DEPT'],
            ['step_name_code' => 'CANCEL_SUBMIT', 'approver_type' => 'USER'],
            ['step_name_code' => 'CANCEL', 'approver_type' => 'ROLE'],
        ];
        foreach ($steps as $i => $step) {
            DB::table('m_approval_flow_steps')->insert([
                'approval_flow_id' => $flowId,
                'step_order' => $i + 1,
                'step_name_code' => $step['step_name_code'],
                'approver_type' => $step['approver_type'],
                'approver_target_id' => $adminUserIds[array_rand($adminUserIds)],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // --- t_buildings ---
        $baseBuildingNames = ['桜台レジデンス', '青葉ハイツ', 'グランドメゾン緑地', 'サンライズコート', '西新宿タワー', 'パークサイド柏', 'リバーフロント越谷', '光ヶ丘ハイム'];
        $buildingCount = 40; // 元の8棟から5倍
        $buildings = [];
        for ($i = 0; $i < $buildingCount; $i++) {
            $base = $baseBuildingNames[$i % count($baseBuildingNames)];
            $suffix = intdiv($i, count($baseBuildingNames)) + 1;
            $name = $suffix > 1 ? "{$base}（{$suffix}号棟）" : $base;

            $buildings[] = TBuilding::create([
                'building_name' => $name,
                'division_code' => sprintf('DIV-%03d', $i + 1),
                'created_at' => now()->subDays(rand(30, 400)),
                'updated_at' => now(),
            ]);
        }

        // --- t_building_cost_items ---
        $itemNamesByKind = [
            2 => '土地建物販売代金',
            3 => '土地仕入代金',
            5 => '金融機関手数料',
            6 => '建設工事費',
            7 => '諸経費',
        ];
        $costItems = [];
        foreach ($buildings as $building) {
            foreach (range(1, 2) as $sort) {
                $kind = $this->itemKinds[array_rand($this->itemKinds)];
                $master = rand(500, 5000) * 10000;
                $item = new TBuildingCostItem;
                $item->forceFill([
                    'building_id' => $building->id,
                    'item_kind' => $kind,
                    'sort' => $sort,
                    'is_enabled' => true,
                    'item_name' => $itemNamesByKind[$kind],
                    'master_price' => $master,
                    'budget_price' => (int) ($master * 0.95),
                    'quotation_amount' => null,
                    'created_at' => now()->subDays(rand(10, 300)),
                    'updated_at' => now(),
                ]);
                $item->save();
                $costItems[] = $item;
            }
        }

        // --- t_cost_quotations / histories / details / approval_actions / requests ---
        foreach ($costItems as $costItem) {
            $companyId = $companyIds[array_rand($companyIds)];
            $status = $this->approvalStatuses[array_rand($this->approvalStatuses)];

            $quotation = TCostQuotation::create([
                'building_cost_item_id' => $costItem->id,
                'company_id' => $companyId,
                'branch_company_id' => null,
                'counter_company_id' => null,
                'is_drafted' => $status === 'UNSELECTED',
                'approval_status' => $status,
                'deny_comment' => null,
                'created_at' => now()->subDays(rand(5, 200)),
                'updated_at' => now(),
            ]);

            $historyCount = rand(1, 2);
            for ($h = 0; $h < $historyCount; $h++) {
                $isLatest = $h === $historyCount - 1;
                $amount = rand(100, 3000) * 10000;

                $history = TCostQuotationHistory::create([
                    'cost_quotation_id' => $quotation->id,
                    'is_latest' => $isLatest,
                    'file_url' => null,
                    'quotation_date' => now()->subDays(rand(1, 180))->toDateString(),
                    'amount_excluding_tax' => $amount,
                    'tax_adjust' => 0,
                    'withholding_income_tax' => null,
                    'comment' => '見積内容の確認をお願いします。',
                    'created_at' => now()->subDays(rand(1, 180)),
                    'updated_at' => now(),
                ]);

                foreach (range(1, rand(1, 2)) as $d) {
                    $unitPrice = rand(1, 100) * 10000;
                    TCostQuotationDetail::create([
                        'cost_quotation_history_id' => $history->id,
                        'is_memo' => false,
                        'branch_code' => null,
                        'department_id' => null,
                        'name' => "明細項目{$d}",
                        'quantity' => rand(1, 10),
                        'unit_id' => null,
                        'unit_price' => $unitPrice,
                        'tax_class' => 'TAXABLE',
                        'tax_rate' => 10,
                        'is_tax_included' => false,
                        'price' => $unitPrice * rand(1, 10),
                        'is_changed' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // 承認フロー: STAFF選定 -> MANAGER承認（ステータスに応じて）
                TCostQuotationApprovalAction::create([
                    'cost_quotation_history_id' => $history->id,
                    'step_name' => 'STAFF',
                    'action_type' => 'SELECT',
                    'operator_id' => $adminUserIds[array_rand($adminUserIds)],
                    'action_at' => now()->subDays(rand(1, 150)),
                ]);
                if (in_array($status, ['STAFF_APPROVED', 'APPROVED', 'CANCEL_APPLIED', 'CANCEL_APPROVED'], true)) {
                    TCostQuotationApprovalAction::create([
                        'cost_quotation_history_id' => $history->id,
                        'step_name' => 'MANAGER',
                        'action_type' => $status === 'APPROVED' || $status === 'CANCEL_APPROVED' ? 'APPROVE' : 'SELECT',
                        'operator_id' => $adminUserIds[array_rand($adminUserIds)],
                        'action_at' => now()->subDays(rand(1, 100)),
                    ]);
                }
            }

            if (rand(0, 1) === 1) {
                TCostQuotationRequest::create([
                    'cost_quotation_id' => $quotation->id,
                    'requested_at' => now()->subDays(rand(1, 150)),
                ]);
            }
        }

        // --- t_comments / t_comment_read_timestamps / t_attachments ---
        foreach (array_slice($costItems, 0, 50) as $costItem) {
            $comment = TComment::create([
                'commentable_type' => TBuildingCostItem::class,
                'commentable_id' => $costItem->id,
                'user_id' => $adminUserIds[array_rand($adminUserIds)],
                'body' => '見積内容についてご確認をお願いします。',
            ]);

            TCommentReadTimestamp::create([
                'user_id' => $adminUserIds[array_rand($adminUserIds)],
                'readable_type' => TBuildingCostItem::class,
                'readable_id' => $costItem->id,
                'last_read_at' => now()->subDays(rand(0, 5)),
            ]);

            if (rand(0, 2) === 0) {
                TAttachment::create([
                    'attachable_type' => TComment::class,
                    'attachable_id' => $comment->id,
                    'file_path' => 'attachments/'.Str::uuid().'.pdf',
                    'original_name' => '見積書.pdf',
                    'mime_type' => 'application/pdf',
                    'size' => rand(10_000, 2_000_000),
                    'user_id' => $adminUserIds[array_rand($adminUserIds)],
                ]);
            }
        }

        $this->command?->info('新見積テーブル群にダミーデータを投入しました。');
    }
}

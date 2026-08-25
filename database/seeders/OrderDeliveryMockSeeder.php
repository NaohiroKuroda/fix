<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\TDeliveryReport;
use App\Models\TDeliveryReportApprovalAction;
use App\Models\TOrder;
use App\Models\TOrderApprovalAction;
use App\Models\TPayablePartner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * 会議デモ用：発注〜納品フローの各画面にデータが乗った状態を作る。
 *
 * 承認済み見積（t_cost_quotations.approval_status = APPROVED）を各ステージに振り分け、
 * それぞれの段階まで進めた状態のレコードを作る。
 */
class OrderDeliveryMockSeeder extends Seeder
{
    public function run(): void
    {
        // 既存のデモデータをクリア（承認履歴 → 明細 → 親の順）。
        TDeliveryReportApprovalAction::query()->delete();
        TDeliveryReport::query()->delete();
        TOrderApprovalAction::query()->delete();
        TOrder::query()->delete();

        $operatorId = (int) (AdminUser::query()->value('id') ?? 0);
        if ($operatorId === 0) {
            $this->command?->warn('admin_users が空のため中断しました。');

            return;
        }

        $quotations = TPayablePartner::query()
            ->where('approval_status', 'APPROVED')
            ->with('latestHistory')
            ->orderByDesc('id')
            ->get();

        // 先頭3件は「発注実行待ち」として発注を作らず残す。以降を各ステージへ配分。
        $pool = $quotations->slice(3)->values();

        $this->buildStage($pool->slice(0, 2), 'order-approval', $operatorId);        // 発注承認待ち
        $this->buildStage($pool->slice(2, 2), 'order-cancel-request', $operatorId);  // 取消申請可能（発注承認済み）
        $this->buildStage($pool->slice(4, 1), 'order-cancel-approval', $operatorId); // 取消申請中（部長取消承認待ち）
        $this->buildStage($pool->slice(5, 2), 'delivery-report', $operatorId);       // 報告書受領待ち
        $this->buildStage($pool->slice(7, 2), 'delivery-approval', $operatorId);     // 納品承認待ち

        $this->command?->info('発注〜納品フローのデモデータを各ステージに投入しました。');
    }

    /**
     * @param  Collection<int, TPayablePartner>  $quotations
     */
    private function buildStage($quotations, string $stage, int $operatorId): void
    {
        foreach ($quotations as $quotation) {
            $amount = optional($quotation->latestHistory)->amount_excluding_tax ?? rand(50, 500) * 10000;

            // --- 発注（全ステージ共通で作る） ---
            $order = TOrder::create([
                'cost_quotation_id' => $quotation->id,
                'order_status' => 'STAFF_APPROVED',
                'amount' => $amount,
                'order_date' => Carbon::now()->subDays(rand(5, 30))->toDateString(),
            ]);
            $this->action(TOrderApprovalAction::class, 'order_id', $order->id, 'STAFF', 'SELECT', $operatorId);

            if ($stage === 'order-approval') {
                continue; // 担当実行済み・部長承認待ちで止める。
            }

            // 発注承認
            $order->update(['order_status' => 'APPROVED']);
            $this->action(TOrderApprovalAction::class, 'order_id', $order->id, 'MANAGER', 'APPROVE', $operatorId);

            if ($stage === 'order-cancel-request') {
                continue; // 発注承認済み・取消申請可能で止める（業者承諾記録にも出る）。
            }

            if ($stage === 'order-cancel-approval') {
                // 取消申請中・部長取消承認待ちにする。
                $order->update(['order_status' => 'CANCEL_REQUESTED']);
                $this->action(TOrderApprovalAction::class, 'order_id', $order->id, 'STAFF', 'CANCEL_SUBMIT', $operatorId);

                continue;
            }

            // 業者承諾記録
            $order->update(['vendor_accepted_at' => Carbon::now()->subDays(rand(3, 15))]);

            if ($stage === 'delivery-report') {
                continue; // 承諾済み・報告書受領待ちで止める。
            }

            // --- 納品報告 ---
            $report = TDeliveryReport::create([
                'order_id' => $order->id,
                'report_status' => 'STAFF_APPROVED',
                'submitted_at' => Carbon::now()->subDays(rand(2, 10)),
            ]);
            $this->action(TDeliveryReportApprovalAction::class, 'delivery_report_id', $report->id, 'STAFF', 'SELECT', $operatorId);
            // delivery-approval はここで止める（報告書受領済み・部長承認待ち）。
        }
    }

    private function action(string $modelClass, string $fk, int $id, string $step, string $type, int $operatorId): void
    {
        $modelClass::create([
            $fk => $id,
            'step_name' => $step,
            'action_type' => $type,
            'operator_id' => $operatorId,
            'action_at' => Carbon::now()->subDays(rand(1, 20)),
        ]);
    }
}

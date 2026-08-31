<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 旧発注テーブル（t_orders）の承認済み発注から、発注書（t_payable_orders）を補完する。
 *
 * 2026-08 のスキーマ改訂で発注書テーブル（t_payable_orders / t_billing_orders）が追加された。
 * 【支払】業者承諾確認画面は発注書を表示元にするため、改訂前に発注承認まで進んでいたデータにも
 * 発注書が必要になる。以後の発注承認では OrderDeliveryRepository::approveOrders() が発行する。
 *
 * 冪等：既に発注書がある取引先はスキップする。見積（t_payable_quotations）が無い取引先は
 * payable_quotation_id が NOT NULL のため作成できないのでスキップする。
 *
 * @see docs/detailed-design/orders/01_支払_業者承諾確認_詳細設計.md §4
 */
return new class extends Migration
{
    public function up(): void
    {
        // 見積管理系のテーブルはマイグレーション管理外（felix_total と共有の既存 DB にある）ため、
        // テスト用の sqlite などテーブルが存在しない環境では何もしない。
        foreach (['t_orders', 't_payable_orders', 't_payable_quotations'] as $table) {
            if (! Schema::hasTable($table)) {
                return;
            }
        }

        $orders = DB::table('t_orders')
            ->where('order_status', 'APPROVED')
            ->whereNotIn('cost_quotation_id', DB::table('t_payable_orders')->select('payable_partner_id'))
            ->get();

        $now = now();

        foreach ($orders as $order) {
            $quotation = DB::table('t_payable_quotations')
                ->where('payable_partner_id', $order->cost_quotation_id)
                ->orderByDesc('is_latest')
                ->orderByDesc('id')
                ->first();

            if ($quotation === null) {
                continue;
            }

            DB::table('t_payable_orders')->insert([
                'payable_quotation_id' => $quotation->id,
                'payable_partner_id' => $order->cost_quotation_id,
                // 発行日時は旧発注日（無ければ作成日時）を引き継ぐ。
                'issued_at' => $order->order_date ?? $order->created_at ?? $now,
                'subtotal_amount' => $order->amount ?? $quotation->subtotal_amount ?? 0,
                'tax_amount' => $quotation->tax_amount ?? 0,
                'tax_adjust' => $quotation->tax_adjust ?? 0,
                'status' => 'ISSUED',
                // 旧テーブルの業者承諾日時＝発注書の請負承認日時。
                'contract_approved_at' => $order->vendor_accepted_at,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * 補完したデータだけを見分ける手掛かりが無い（source_id は移行元 ID 用）ため、巻き戻しはしない。
     * 発注書が不要になった場合は取消承認（発注の取り消し）で削除する。
     */
    public function down(): void {}
};

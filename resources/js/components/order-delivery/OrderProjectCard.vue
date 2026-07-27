<script setup lang="ts">
// 発注フロー1案件分の明細カード（タイトル帯＋テーブル）。見積管理の EstimateProjectCard を
// 発注フロー用に簡略化したもの（相見積比較・仮選定・業者追加・iframe は持たない）。
// 開閉・選択・否認・チャットは親へ emit で委譲。
import { computed } from 'vue';
import { ChevronDown, CheckCircle2, Check, Ban, MessageSquare, FileText, XCircle, ReceiptText } from 'lucide-vue-next';
import { useEstimateTheme } from '@/composables/useEstimateTheme';
// 区分ラベルは見積依頼画面と共通（見た目のずれを防ぐため実装を1か所に集約）。
import BillingKindBadge from '@/components/estimate-management/BillingKindBadge.vue';
import { orderRowKey } from '@/lib/order-delivery';
import type { OrderDeliveryModeConfig } from '@/lib/order-delivery';
import type { OrderDeliveryProject, OrderDeliveryRow } from '@/types/order-delivery';

const props = defineProps<{
    project: OrderDeliveryProject;
    config: OrderDeliveryModeConfig;
    open: boolean;
    glass?: boolean;
    /** 選択中の行キー集合。 */
    selectedKeys: Set<string>;
}>();
const emit = defineEmits<{
    (e: 'toggle'): void;
    (e: 'row-toggle', row: OrderDeliveryRow): void;
    (e: 'reject', row: OrderDeliveryRow): void;
    (e: 'cancel-request', row: OrderDeliveryRow): void;
    (e: 'open-chat', row: OrderDeliveryRow, buildingName: string): void;
    (e: 'open-iframe', payload: { url: string | null; title: string }): void;
    /** 完了確認画面：請求日クリック（請求情報モーダルを開く）。 */
    (e: 'open-invoice', row: OrderDeliveryRow): void;
}>();

const isThemed = computed(() => props.glass === true);
const isCheckbox = computed(() => props.config.kind === 'checkbox');
const isCompletionCheck = computed(() => props.config.isCompletionCheck === true);
const showCompletionColumns = computed(() => props.config.showCompletionColumns === true);

// 金額列の構成（見出しと行のキー）。
// pre-order=標準単価/予算単価/相見積、post-order=予算単価/見積/発注、order-only=発注のみ。
type PriceKey = 'masterPrice' | 'budgetPrice' | 'quotePrice' | 'orderPrice';
const priceColumns = computed<{ label: string; key: PriceKey }[]>(() => {
    if (props.config.priceMode === 'pre-order') {
        return [
            { label: '標準単価', key: 'masterPrice' },
            { label: '予算単価', key: 'budgetPrice' },
            { label: '相見積', key: 'quotePrice' },
        ];
    }
    if (props.config.priceMode === 'order-only') {
        return [{ label: '発注', key: 'orderPrice' }];
    }
    return [
        { label: '予算単価', key: 'budgetPrice' },
        { label: '見積', key: 'quotePrice' },
        { label: '発注', key: 'orderPrice' },
    ];
});
// 空行の colspan 用。項目/パートナー の2列＋提出日・確認日・請求日（該当画面のみ3列）＋操作列
// （isCompletionCheck の画面は操作列を持たない）＋金額列＋発注書＋発注日＋否認＋取消申請。
const columnCount = computed(
    () =>
        2 +
        (showCompletionColumns.value ? 3 : 0) +
        (isCompletionCheck.value ? 0 : 1) +
        priceColumns.value.length +
        (props.config.showReject ? 1 : 0) +
        (props.config.showOrderDocument ? 1 : 0) +
        (props.config.showOrderDate ? 1 : 0) +
        (props.config.showCancelRequest ? 1 : 0),
);
const { detailCardClass, cardHeadClass, tableHeadClass, rowBorderClass, cellTextClass, mutedTextClass } =
    useEstimateTheme(isThemed);

const yen = (value: number | null): string => (value === null || value === undefined ? '—' : `¥${value.toLocaleString()}`);
const isActive = (row: OrderDeliveryRow): boolean => props.selectedKeys.has(orderRowKey(row));

// 項目（unitId）ごとの先頭行キー。項目名・チャットボタンは先頭行にのみ出す。
const firstRowKeyByUnit = computed<Record<number, string>>(() => {
    const map: Record<number, string> = {};
    for (const row of props.project.rows) {
        if (!(row.unitId in map)) {
            map[row.unitId] = orderRowKey(row);
        }
    }
    return map;
});
const isItemFirstRow = (row: OrderDeliveryRow): boolean => firstRowKeyByUnit.value[row.unitId] === orderRowKey(row);

const btnShapeBase = 'relative mx-auto flex h-9 w-28 items-center justify-center whitespace-nowrap px-2 text-sm font-semibold transition';
// メインボタン（選択トグル）の配色。選択済＝ゴールドのベタ塗り、未選択＝淡色。
const mainBtnClass = (row: OrderDeliveryRow): string => {
    const active = isActive(row);
    if (!isThemed.value) {
        return `${btnShapeBase} rounded-md ${active ? 'bg-primary text-primary-foreground hover:opacity-90' : 'border bg-primary/5 text-muted-foreground hover:bg-accent'}`;
    }
    return `${btnShapeBase} rounded-xl border ${active
        ? 'border-[#c4a35b] bg-[#c4a35b] text-white shadow-sm hover:bg-[#b3923f]'
        : 'border-[#c4a35b]/40 bg-[#c4a35b]/10 text-[#8a6a25] shadow-sm backdrop-blur-md hover:border-[#c4a35b]/60 hover:bg-[#c4a35b]/20'}`;
};

// チャットボタン配色。コメントありは強調（選定ボタンと同色）。
const chatBtnClass = (row: OrderDeliveryRow): string => {
    const shape = 'relative inline-flex size-8 shrink-0 items-center justify-center rounded-xl border shadow-sm transition';
    if (row.hasComments) {
        return isThemed.value
            ? `${shape} border-[#c4a35b] bg-[#c4a35b] text-white hover:bg-[#b3923f]`
            : `${shape} border-primary bg-primary text-primary-foreground hover:opacity-90`;
    }
    return `${shape} border-slate-300 bg-white text-slate-700 hover:border-[#c4a35b] hover:bg-[#c4a35b]/10`;
};
</script>

<template>
    <div class="overflow-hidden" :class="detailCardClass">
        <button
            type="button"
            class="flex w-full items-center gap-2 border-b px-3 py-2.5 text-left text-sm font-semibold"
            :class="cardHeadClass"
            @click="emit('toggle')"
        >
            <span class="flex-1">No.{{ project.no }}　{{ project.name }}</span>
            <ChevronDown class="size-4 shrink-0 transition-transform" :class="open ? '' : '-rotate-90'" />
        </button>
        <div v-show="open" class="overflow-x-auto">
            <table class="w-full min-w-[880px] table-fixed text-[15px]" :class="cellTextClass">
                <colgroup>
                    <col style="width: 22%" />
                    <col v-if="config.showBillingKind" style="width: 8%" />
                    <col style="width: 22%" />
                    <col v-for="col in priceColumns" :key="col.key" style="width: 13%" />
                    <col v-if="config.showOrderDocument" style="width: 10%" />
                    <col v-if="config.showOrderDate" style="width: 10%" />
                    <template v-if="showCompletionColumns">
                        <col style="width: 14%" />
                        <col style="width: 14%" />
                        <col style="width: 14%" />
                    </template>
                    <col v-if="!isCompletionCheck" style="width: 14%" />
                    <col v-if="config.showReject" style="width: 10%" />
                    <col v-if="config.showCancelRequest" style="width: 10%" />
                </colgroup>
                <thead class="text-center" :class="tableHeadClass">
                    <tr class="border-b-2 border-slate-300 text-[15px] font-bold uppercase tracking-wider">
                        <th class="px-3 py-2.5">項目</th>
                        <!-- 区分（請求／支払）：業者承諾確認画面のみ。見積依頼画面と同じ位置（項目とパートナーの間）。 -->
                        <th v-if="config.showBillingKind" class="px-3 py-2.5">区分</th>
                        <th class="px-3 py-2.5">パートナー</th>
                        <th v-for="col in priceColumns" :key="col.key" class="px-3 py-2.5">{{ col.label }}<br />(税抜)</th>
                        <th v-if="config.showOrderDocument" class="px-3 py-2.5">発注書</th>
                        <th v-if="config.showOrderDate" class="px-3 py-2.5">発注日</th>
                        <template v-if="showCompletionColumns">
                            <th class="px-3 py-2.5">報告書提出日</th>
                            <th class="px-3 py-2.5">担当者確認日</th>
                            <th class="px-3 py-2.5">請求書作成日</th>
                        </template>
                        <th v-if="!isCompletionCheck" class="px-3 py-2.5">{{ config.columnLabel }}</th>
                        <th v-if="config.showReject" class="px-3 py-2.5">否認</th>
                        <th v-if="config.showCancelRequest" class="px-3 py-2.5">取消申請</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in project.rows"
                        :key="orderRowKey(row)"
                        class="border-t"
                        :class="[
                            rowBorderClass,
                            // 請求（もらい）行は見積依頼画面と同じく淡い青の地色を敷き、行単位で区別できるようにする。
                            config.showBillingKind && row.billingTarget ? 'bg-sky-50/70' : '',
                        ]"
                    >
                        <td class="px-3 py-2 font-medium">
                            <div class="flex items-center justify-between gap-2">
                                <span>{{ isItemFirstRow(row) ? row.itemName : '' }}</span>
                                <!-- 項目名の右隣：やり取り（チャット）。項目の先頭行に1つだけ出す。 -->
                                <button
                                    v-if="isItemFirstRow(row)"
                                    type="button"
                                    :class="chatBtnClass(row)"
                                    title="この見積についてやり取りする"
                                    @click="emit('open-chat', row, project.name)"
                                >
                                    <MessageSquare class="size-4.5" />
                                    <span
                                        v-if="row.unreadCount"
                                        class="absolute -right-1.5 -top-1.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold leading-none text-white tabular-nums"
                                    >{{ row.unreadCount > 99 ? '99+' : row.unreadCount }}</span>
                                </button>
                            </div>
                        </td>
                        <!-- 区分（もらい/請求・払い/支払）：クリック不可の表示のみラベル。業者承諾確認画面のみ。
                             見た目は見積依頼画面と共通（BillingKindBadge に集約）。 -->
                        <td v-if="config.showBillingKind" class="px-3 py-2 text-center">
                            <BillingKindBadge :billing-target="row.billingTarget" />
                        </td>
                        <td class="px-3 py-2 font-medium">{{ row.vendorName }}</td>
                        <td
                            v-for="(col, ci) in priceColumns"
                            :key="col.key"
                            class="px-3 py-2 text-right tabular-nums"
                            :class="ci === priceColumns.length - 1 ? 'font-semibold' : ''"
                        >{{ yen(row[col.key]) }}</td>
                        <!-- 発注書（発注実行・発注承認画面のみ）。押下で felix_total の発注書画面を iframe で開く。 -->
                        <td v-if="config.showOrderDocument" class="px-3 py-2 text-center">
                            <button
                                v-if="project.orderDocumentUrl"
                                type="button"
                                class="relative mx-auto flex h-9 w-24 items-center justify-center gap-1 whitespace-nowrap rounded-xl border border-[#c4a35b]/50 bg-white px-2 text-sm font-semibold text-[#8a6a25] shadow-sm transition hover:border-[#c4a35b] hover:bg-[#c4a35b] hover:text-white"
                                title="発注書を表示する"
                                @click="emit('open-iframe', { url: project.orderDocumentUrl, title: `発注書 - ${project.name}` })"
                            >
                                <FileText class="size-4" />発注書
                            </button>
                            <span v-else :class="mutedTextClass">—</span>
                        </td>
                        <!-- 発注日（業者承諾確認画面のみ・発注書の右隣）。 -->
                        <td v-if="config.showOrderDate" class="px-3 py-2 text-center text-sm tabular-nums" :class="cellTextClass">
                            {{ row.orderDate ?? '—' }}
                        </td>
                        <!-- 完了確認・部長完了承認：提出日／確認日／請求日の3列。操作列（承認等）と併用できる。 -->
                        <template v-if="showCompletionColumns">
                            <!-- 報告書提出日：押下で felix_total の必須ファイルタブを iframe で開く。 -->
                            <td class="px-3 py-2 text-center">
                                <button
                                    v-if="row.vendorAcceptedAt && project.completionReportUrl"
                                    type="button"
                                    class="relative mx-auto flex h-9 items-center justify-center gap-1 whitespace-nowrap rounded-xl border border-[#c4a35b]/50 bg-white px-2 text-sm font-semibold text-[#8a6a25] shadow-sm transition hover:border-[#c4a35b] hover:bg-[#c4a35b] hover:text-white"
                                    title="必須ファイルを表示する"
                                    @click="emit('open-iframe', { url: project.completionReportUrl, title: `必須ファイル - ${project.name}` })"
                                >
                                    <FileText class="size-4" />{{ row.vendorAcceptedAt }}
                                </button>
                                <span v-else class="text-sm tabular-nums" :class="cellTextClass">{{ row.vendorAcceptedAt ?? '—' }}</span>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span v-if="row.submittedAt" class="inline-flex items-center justify-center gap-1 text-sm tabular-nums" :class="cellTextClass">
                                    <CheckCircle2 class="size-4 shrink-0 text-emerald-600" />{{ row.submittedAt }}
                                </span>
                                <span v-else class="text-sm tabular-nums" :class="mutedTextClass">—</span>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <button
                                    v-if="row.invoiceSubmittedAt"
                                    type="button"
                                    class="relative mx-auto flex h-9 items-center justify-center gap-1 whitespace-nowrap rounded-xl border border-[#c4a35b]/50 bg-white px-2 text-sm font-semibold text-[#8a6a25] shadow-sm transition hover:border-[#c4a35b] hover:bg-[#c4a35b] hover:text-white"
                                    title="請求情報を表示する"
                                    @click="emit('open-invoice', row)"
                                >
                                    <ReceiptText class="size-4" />{{ row.invoiceSubmittedAt }}
                                </button>
                                <span v-else :class="mutedTextClass">—</span>
                            </td>
                        </template>
                        <td v-if="!isCompletionCheck" class="px-3 py-2 text-center">
                            <!-- 発注実行：チェックボックス（チップ）。複数選択でヘッダー一括発注。 -->
                            <label
                                v-if="isCheckbox"
                                class="mx-auto inline-flex h-9 w-28 cursor-pointer items-center justify-center gap-2 whitespace-nowrap rounded-xl border px-2 text-sm font-semibold shadow-sm backdrop-blur-md transition focus-within:ring-2 focus-within:ring-[#c4a35b]/40"
                                :class="isActive(row)
                                    ? (isThemed ? 'border-[#c4a35b] bg-[#c4a35b] text-white' : 'border-primary bg-primary text-primary-foreground')
                                    : (isThemed ? 'border-[#c4a35b]/40 bg-[#c4a35b]/10 text-[#8a6a25] hover:border-[#c4a35b]/60 hover:bg-[#c4a35b]/20' : 'border bg-primary/5 text-muted-foreground hover:bg-accent')"
                            >
                                <input type="checkbox" :checked="isActive(row)" class="sr-only" @change="emit('row-toggle', row)" />
                                <span
                                    class="flex size-4 shrink-0 items-center justify-center rounded-[4px] border-2 transition"
                                    :class="isActive(row) ? 'border-white bg-white/25 text-white' : 'border-[#c4a35b] bg-white text-transparent'"
                                >
                                    <Check class="size-3" :stroke-width="3" />
                                </span>
                                {{ isActive(row) ? config.activeLabel : config.idleLabel }}
                            </label>
                            <!-- 業者承諾確認：既に承諾済み（vendorAcceptedAt あり）の行はボタンの代わりに承諾日を表示する。 -->
                            <span
                                v-else-if="config.showAcceptedDate && row.vendorAcceptedAt"
                                class="inline-flex items-center justify-center gap-1 text-sm tabular-nums"
                                :class="cellTextClass"
                            >
                                <CheckCircle2 class="size-4 shrink-0 text-emerald-600" />{{ row.vendorAcceptedAt }}
                            </span>
                            <!-- 業者承諾確認：未承諾は表示のみ（承諾登録機能は廃止・ボタンなし）。 -->
                            <span v-else-if="config.showAcceptedDate" class="text-sm" :class="mutedTextClass">—</span>
                            <!-- 承認・確認・仮締め：選択トグル（ヘッダー確定で一括）。
                                 取消承認など isPerRowAction＝true の画面は、押下で直接理由入力モーダルを開く（単体実行）。 -->
                            <button
                                v-else
                                type="button"
                                :class="mainBtnClass(row)"
                                @click="config.isPerRowAction ? emit('cancel-request', row) : emit('row-toggle', row)"
                            >
                                <CheckCircle2 v-if="!config.isPerRowAction && isActive(row)" class="absolute left-2.5 size-4" />
                                {{ config.isPerRowAction ? config.idleLabel : (isActive(row) ? config.activeLabel : config.idleLabel) }}
                            </button>
                        </td>
                        <!-- 否認（右端・該当画面のみ）。押下で理由入力モーダル→前段へ差し戻し。 -->
                        <td v-if="config.showReject" class="px-3 py-2 text-center">
                            <button
                                type="button"
                                class="relative mx-auto flex h-9 w-24 items-center justify-center gap-1 whitespace-nowrap rounded-xl border border-red-400/60 bg-red-50 px-2 text-sm font-semibold text-red-600 shadow-sm transition hover:border-red-500 hover:bg-red-100"
                                title="否認して前段へ差し戻す"
                                @click="emit('reject', row)"
                            >
                                <Ban class="size-4" />否認
                            </button>
                        </td>
                        <!-- 取消申請（右端・業者承諾確認画面のみ）。押下で理由入力モーダル→取消申請。 -->
                        <td v-if="config.showCancelRequest" class="px-3 py-2 text-center">
                            <button
                                type="button"
                                class="relative mx-auto flex h-9 w-24 items-center justify-center gap-1 whitespace-nowrap rounded-xl border border-slate-400/60 bg-slate-50 px-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-slate-500 hover:bg-slate-100"
                                title="発注の取消を申請する"
                                @click="emit('cancel-request', row)"
                            >
                                <XCircle class="size-4" />取消申請
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!project.rows.length">
                        <td :colspan="columnCount" class="px-3 py-4 text-center" :class="mutedTextClass">対象データがありません。</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

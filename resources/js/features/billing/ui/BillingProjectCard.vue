<script setup lang="ts">
// 1案件分の明細カード（タイトル帯＋テーブル）。請求（もらい）専用。
// 支払側（QuotationProjectCard）との違い:
//   - もらいは相見積・業者選定が無いため、標準単価 / 予算単価 / 仮選定は「—」固定
//   - 区分は常に「請求」
//   - 操作列は mode（BILLING_MODE_CONFIG.kind）で出し分ける
import { computed } from 'vue';
import { ChevronDown, MessageSquare, Plus, FileText } from 'lucide-vue-next';
import { useFelixTheme } from '@/shared/lib/felix-theme';
import { yenString } from '@/shared/lib/format-money';
import { BillingKindBadge } from '@/shared/ui/billing-kind-badge';
import { BILLING_MODE_CONFIG } from '../model/billing-mode';
import { BILLING_STATUS_LABEL } from '../model/billing';
import type { BillingMode, BillingProject, BillingRow } from '../model/billing';

const props = defineProps<{
    project: BillingProject;
    mode: BillingMode;
    open: boolean;
    glass?: boolean;
    /** 選択中の行キー（partnerId）の集合。pick モードのみ使う。 */
    selectedKeys: Set<number>;
}>();
const emit = defineEmits<{
    (e: 'toggle'): void;
    /** pick モード：行の選択トグル。 */
    (e: 'row-toggle', row: BillingRow): void;
    /** modal / per-row / view モード：行ボタンの押下。 */
    (e: 'row-action', row: BillingRow): void;
    (e: 'open-chat', row: BillingRow, buildingName: string): void;
    (e: 'open-iframe', payload: { url: string | null; title: string }): void;
}>();

const isThemed = computed(() => props.glass === true);
// 項目名セルは rowspan で行グループ全体にまたがるため、カード地色を明示して行の地色を打ち消す。
const cardBgClass = computed(() => (isThemed.value ? 'bg-white' : 'bg-card'));
const config = computed(() => BILLING_MODE_CONFIG[props.mode]);
const isPick = computed(() => config.value.kind === 'pick');
const isQuoteCreate = computed(() => props.mode === 'billing-quote-create');
const { detailCardClass, cardHeadClass, tableHeadClass, rowBorderClass, cellTextClass, mutedTextClass } =
    useFelixTheme(isThemed);

const isActive = (row: BillingRow): boolean => props.selectedKeys.has(row.partnerId);
/** 状態バッジの文言（操作できない行に出す）。処理フロー K列「ステータス外表示形式」。 */
const statusLabel = (row: BillingRow): string => BILLING_STATUS_LABEL[row.approvalStatus];

/** 見積が作成済みか（見積作成画面のボタン文言の出し分けに使う）。 */
const hasQuotation = (row: BillingRow): boolean => row.quotationAmount !== null;

/** 表示用に組み立てた1行（項目名の rowspan 情報を持つ）。 */
interface DisplayRow {
    row: BillingRow;
    isUnitFirstRow: boolean;
    unitRowSpan: number;
    isUnitBoundary: boolean;
}
// 同一項目の行をまとめ、先頭行にだけ項目名セル（rowspan）を出す。
const displayRows = computed<DisplayRow[]>(() => {
    const result: DisplayRow[] = [];
    let index = 0;
    while (index < props.project.rows.length) {
        const name = props.project.rows[index].itemName;
        let span = 0;
        while (index + span < props.project.rows.length && props.project.rows[index + span].itemName === name) {
            span += 1;
        }
        for (let offset = 0; offset < span; offset += 1) {
            result.push({
                row: props.project.rows[index + offset],
                isUnitFirstRow: offset === 0,
                unitRowSpan: span,
                isUnitBoundary: offset === 0 && index > 0,
            });
        }
        index += span;
    }
    return result;
});

// 操作ボタン（金ベタ＝選択中／淡色＝未選択）。支払側と同じ2状態デザインに揃える。
const btnShapeBase =
    'relative mx-auto flex h-9 w-28 items-center justify-center gap-1 whitespace-nowrap px-2 text-sm font-semibold transition';
const actionBtnClass = (active: boolean): string => {
    if (!isThemed.value) {
        return `${btnShapeBase} rounded-md ${active ? 'bg-primary text-primary-foreground hover:opacity-90' : 'border bg-primary/5 text-muted-foreground hover:bg-accent'}`;
    }
    return active
        ? `${btnShapeBase} rounded-xl border border-[#c4a35b] bg-[#c4a35b] text-white shadow-sm hover:bg-[#b3923f]`
        : `${btnShapeBase} rounded-xl border border-[#c4a35b]/40 bg-[#c4a35b]/10 text-[#8a6a25] shadow-sm backdrop-blur-md hover:border-[#c4a35b]/60 hover:bg-[#c4a35b]/20`;
};

// コメント（やり取り）ボタンの配色。コメントが1件以上ある項目は操作ボタンと同色で強調する。
const chatBtnClass = (row: BillingRow): string => {
    const shape = 'relative inline-flex size-8 shrink-0 items-center justify-center rounded-xl border shadow-sm transition';
    if (row.hasComments) {
        return isThemed.value
            ? `${shape} border-[#c4a35b] bg-[#c4a35b] text-white hover:bg-[#b3923f]`
            : `${shape} border-primary bg-primary text-primary-foreground hover:opacity-90`;
    }
    return `${shape} border-slate-300 bg-white text-slate-700 hover:border-[#c4a35b] hover:bg-[#c4a35b]/10`;
};

/** 行ボタンの文言（見積作成のみ、作成済みなら「見積修正」）。 */
const rowButtonLabel = (row: BillingRow): string =>
    isQuoteCreate.value && hasQuotation(row) ? config.value.activeLabel : config.value.idleLabel;
</script>

<template>
    <div class="overflow-hidden" :class="detailCardClass">
        <button
            type="button"
            class="flex w-full items-center gap-2 border-b px-3 py-2.5 text-left text-sm font-semibold"
            :class="cardHeadClass"
            @click="emit('toggle')"
        >
            <span class="flex-1">No.{{ project.no ?? '—' }}　{{ project.name }}</span>
            <ChevronDown class="size-4 shrink-0 transition-transform" :class="open ? '' : '-rotate-90'" />
        </button>
        <div v-show="open" class="overflow-x-auto">
            <table class="w-full min-w-[960px] table-fixed text-[15px]" :class="cellTextClass">
                <!-- 全案件カードで列位置を揃えるため、固定レイアウト＋共通の列幅を指定する。 -->
                <colgroup>
                    <col style="width: 22%" />
                    <col style="width: 7%" />
                    <col style="width: 23%" />
                    <col style="width: 12%" />
                    <col style="width: 12%" />
                    <col style="width: 14%" />
                    <col style="width: 8%" />
                    <col v-if="config.showAcceptedAt" style="width: 12%" />
                    <col style="width: 15%" />
                </colgroup>
                <thead class="text-center" :class="tableHeadClass">
                    <tr class="border-b-2 border-slate-300 text-[15px] font-bold uppercase tracking-wider">
                        <th class="px-3 py-2.5">項目</th>
                        <th class="px-3 py-2.5">区分</th>
                        <th class="px-3 py-2.5">パートナー</th>
                        <th class="px-3 py-2.5">標準単価<br />(税抜)</th>
                        <th class="px-3 py-2.5">予算単価<br />(税抜)</th>
                        <th class="px-3 py-2.5">{{ config.amountColumnLabel }}<br />(税抜)</th>
                        <th class="px-3 py-2.5">仮選定</th>
                        <th v-if="config.showAcceptedAt" class="px-3 py-2.5">承諾日</th>
                        <th class="px-3 py-2.5">{{ config.columnLabel }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="{ row, isUnitFirstRow, unitRowSpan, isUnitBoundary } in displayRows"
                        :key="row.partnerId"
                        :class="[rowBorderClass, isUnitBoundary ? 'border-t-4 border-slate-300' : 'border-t', 'bg-sky-50/70']"
                    >
                        <!-- 項目名：同一項目の行数ぶん rowspan で1回だけ出す。 -->
                        <td v-if="isUnitFirstRow" :rowspan="unitRowSpan" class="px-3 py-2 font-medium" :class="cardBgClass">
                            <div class="flex items-center justify-between gap-2">
                                <span>{{ row.itemName }}</span>
                                <div class="flex shrink-0 items-center gap-1.5">
                                    <!-- やり取り（チャット）。押下でコメントスレッドを開く。 -->
                                    <button
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
                                    <!-- ② この項目に業者を追加（見積作成画面のみ）。「請求先にする」ON でもらいとして登録される。 -->
                                    <button
                                        v-if="isQuoteCreate"
                                        type="button"
                                        class="group inline-flex shrink-0 items-center gap-1 whitespace-nowrap rounded-full border border-teal-600/50 px-3 py-0.5 text-sm font-medium text-teal-700 transition-colors hover:border-teal-600 hover:bg-teal-600 hover:text-white"
                                        title="この項目に業者を追加"
                                        @click="emit('open-iframe', { url: row.addVendorUrl, title: '業者を追加' })"
                                    >
                                        <Plus class="size-3.5" />業者追加
                                    </button>
                                </div>
                            </div>
                        </td>
                        <!-- 区分：請求（もらい）固定。 -->
                        <td class="px-3 py-2 text-center">
                            <BillingKindBadge billing-target />
                        </td>
                        <!-- パートナー（見積先）。詳細は iframe で開く。 -->
                        <td class="px-3 py-2">
                            <button
                                v-if="row.vendorDetailUrl"
                                type="button"
                                class="text-left underline-offset-2 hover:underline"
                                @click="emit('open-iframe', { url: row.vendorDetailUrl, title: '見積先の詳細' })"
                            >
                                {{ row.vendorName }}
                            </button>
                            <span v-else>{{ row.vendorName }}</span>
                        </td>
                        <!-- 標準単価 / 予算単価 / 仮選定：もらいは使わないため「—」固定。 -->
                        <td class="px-3 py-2 text-center" :class="mutedTextClass">—</td>
                        <td class="px-3 py-2 text-center" :class="mutedTextClass">—</td>
                        <td class="px-3 py-2 text-right tabular-nums">
                            {{ yenString(row.quotationAmount) }}
                            <span v-if="row.quotationDate" class="block text-xs" :class="mutedTextClass">{{ row.quotationDate }}</span>
                        </td>
                        <td class="px-3 py-2 text-center" :class="mutedTextClass">—</td>
                        <td v-if="config.showAcceptedAt" class="px-3 py-2 text-center tabular-nums">
                            <span v-if="row.acceptedAt">{{ row.acceptedAt }}</span>
                            <span v-else :class="mutedTextClass">—</span>
                        </td>
                        <!-- 操作列：pick はトグル選択、それ以外は押下で親がモーダルを開く。 -->
                        <td class="px-3 py-2 text-center">
                            <!--
                                操作できない行（処理フロー K列）。一覧には出すが操作させず、
                                現在の承認ステータスをバッジで示す。
                            -->
                            <span
                                v-if="!row.operable"
                                class="mx-auto inline-flex h-9 w-28 items-center justify-center whitespace-nowrap rounded-xl border border-slate-300 bg-slate-100 px-2 text-sm font-semibold text-slate-500"
                                :title="`${statusLabel(row)}のため、この画面では操作できません`"
                            >
                                {{ statusLabel(row) }}
                            </span>
                            <button
                                v-else
                                type="button"
                                :class="actionBtnClass(isPick ? isActive(row) : hasQuotation(row) && isQuoteCreate)"
                                @click="isPick ? emit('row-toggle', row) : emit('row-action', row)"
                            >
                                <FileText v-if="config.kind === 'view'" class="size-4" />
                                {{ isPick && isActive(row) ? config.activeLabel : rowButtonLabel(row) }}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

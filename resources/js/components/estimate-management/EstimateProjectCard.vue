<script setup lang="ts">
// 1案件分の明細カード（タイトル帯＋テーブル）。開閉とチェックは親へ emit で委譲。
import { computed } from 'vue';
import { ChevronDown, CheckCircle2, Check, Plus, ExternalLink } from 'lucide-vue-next';
import { useEstimateTheme } from '@/composables/useEstimateTheme';
import { estimateRowKey, ESTIMATE_MODE_CONFIG } from '@/lib/estimate-management';
import type { EstimateManagementMode, EstimateManagementProject, EstimateManagementRow } from '@/types/estimate-management';

const props = defineProps<{
    project: EstimateManagementProject;
    mode: EstimateManagementMode;
    open: boolean;
    glass?: boolean;
    /** 有効な行キーの集合（見積依頼=チェック / それ以外=選定/選択中）。 */
    selectedKeys: Set<string>;
    /** 仮選定（FELIXが依頼したい業者の印）の行キー集合。業者選定画面のみ。 */
    provisionalKeys?: Set<string>;
}>();
const emit = defineEmits<{
    (e: 'toggle'): void;
    (e: 'row-toggle', row: EstimateManagementRow): void;
    (e: 'provisional-toggle', row: EstimateManagementRow): void;
    (e: 'open-iframe', payload: { url: string | null; title: string }): void;
}>();

const isThemed = computed(() => props.glass === true);
const config = computed(() => ESTIMATE_MODE_CONFIG[props.mode]);
const isCheckbox = computed(() => config.value.kind === 'checkbox');
// 見積依頼画面のみ、見積先名を業者マイページへのリンクにする。
const isQuoteRequest = computed(() => props.mode === 'quote-request');
// 業者選定画面のみ「仮選定」列を出す。
const isVendorSelection = computed(() => props.mode === 'vendor-selection');
const isToggleButton = computed(() => config.value.kind === 'toggle-button');
// 最終列＋（相見積を表示する場合の）相見積列＋（業者選定の）仮選定列。空行の colspan に使う。
const columnCount = computed(() => 5 + (config.value.showQuote ? 1 : 0) + (isVendorSelection.value ? 1 : 0));
const { detailCardClass, cardHeadClass, tableHeadClass, rowBorderClass, cellTextClass, mutedTextClass } =
    useEstimateTheme(isThemed);

const yen = (value: number | null): string =>
    value === null || value === undefined ? '—' : `¥${value.toLocaleString()}`;
const isActive = (row: EstimateManagementRow): boolean => props.selectedKeys.has(estimateRowKey(row));
// 仮選定（DB保存は将来。現状はフロントのローカル状態）。
const isProvisional = (row: EstimateManagementRow): boolean => props.provisionalKeys?.has(estimateRowKey(row)) ?? false;
// 見積回答あり（相見積に金額が入っている）。無い業者は業者選定を不可にする。
const hasQuoteAnswer = (row: EstimateManagementRow): boolean => row.quotePrice != null && row.quotePrice > 0;
// 行が「処理済み」か（mode ごとのサーバ側フラグ）。処理済みは静的バッジで表示し再操作不可。
const isApplied = (row: EstimateManagementRow): boolean => row[config.value.appliedKey] === true;

// 業者選定ボタンの配色（押下＝金/プライマリ「選定済」、未押下＝淡色「選定する」）。
const selectBtnClass = (row: EstimateManagementRow): string => {
    const active = isActive(row);
    if (!isThemed.value) {
        return active
            ? 'relative mx-auto flex h-9 w-28 items-center justify-center rounded-md bg-primary whitespace-nowrap px-2 text-sm font-semibold text-primary-foreground transition hover:opacity-90'
            : 'relative mx-auto flex h-9 w-28 items-center justify-center rounded-md border bg-primary/5 whitespace-nowrap px-2 text-sm font-semibold text-muted-foreground transition hover:bg-accent';
    }
    return active
        ? 'relative mx-auto flex h-9 w-28 items-center justify-center rounded-xl border border-[#c4a35b] bg-[#c4a35b] whitespace-nowrap px-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#b3923f]'
        : 'relative mx-auto flex h-9 w-28 items-center justify-center rounded-xl border border-[#c4a35b]/40 bg-[#c4a35b]/10 whitespace-nowrap px-2 text-sm font-semibold text-[#8a6a25] shadow-sm backdrop-blur-md transition hover:border-[#c4a35b]/60 hover:bg-[#c4a35b]/20';
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
            <table class="w-full min-w-[960px] table-fixed text-[15px]" :class="cellTextClass">
                <!-- 全案件カードで列位置を揃えるため、固定レイアウト＋共通の列幅を指定する。 -->
                <colgroup>
                    <col style="width: 22%" />
                    <col style="width: 23%" />
                    <col style="width: 13%" />
                    <col style="width: 13%" />
                    <col v-if="config.showQuote" style="width: 14%" />
                    <col v-if="isVendorSelection" style="width: 9%" />
                    <col style="width: 15%" />
                </colgroup>
                <thead class="text-center" :class="tableHeadClass">
                    <tr class="border-b-2 border-slate-300 text-[15px] font-bold uppercase tracking-wider">
                        <th class="px-3 py-2.5">項目</th>
                        <th class="px-3 py-2.5">見積先</th>
                        <th class="px-3 py-2.5">標準単価<br />(税抜)</th>
                        <th class="px-3 py-2.5">予算単価<br />(税抜)</th>
                        <th v-if="config.showQuote" class="px-3 py-2.5">{{ config.quoteColumnLabel ?? '相見積' }}<br />(税抜)</th>
                        <th v-if="isVendorSelection" class="px-3 py-2.5">仮選定</th>
                        <th class="px-3 py-2.5">{{ config.columnLabel }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in project.rows" :key="estimateRowKey(row)" class="border-t" :class="rowBorderClass">
                        <td class="px-3 py-2 font-medium">
                            <div class="flex items-center justify-between gap-2">
                                <span>{{ row.itemLabel }}</span>
                                <!-- 項目名の右隣（右寄せ）：この項目に業者を追加（見積依頼画面のみ・押すと iframe を起動）。 -->
                                <button
                                    v-if="isQuoteRequest && row.itemLabel"
                                    type="button"
                                    class="group inline-flex shrink-0 items-center gap-1 whitespace-nowrap rounded-full border border-teal-600/50 px-3 py-0.5 text-sm font-medium text-teal-700 transition-colors hover:border-teal-600 hover:bg-teal-600 hover:text-white"
                                    title="この項目に業者を追加"
                                    @click="emit('open-iframe', { url: row.addVendorUrl, title: '業者を追加' })"
                                >
                                    <Plus class="size-3.5" />業者追加
                                </button>
                            </div>
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex items-center justify-between gap-2">
                                <!-- 会社名：見積先の詳細を iframe で開く（URL 未設定時はプレーン表示）。 -->
                                <button
                                    v-if="row.vendorDetailUrl"
                                    type="button"
                                    class="text-left font-medium text-[#8a6a25] underline decoration-[#c4a35b]/50 underline-offset-2 transition hover:text-[#c4a35b] hover:decoration-[#c4a35b]"
                                    @click="emit('open-iframe', { url: row.vendorDetailUrl, title: '見積先の詳細' })"
                                >{{ row.vendorName }}</button>
                                <span v-else class="font-medium">{{ row.vendorName }}</span>
                                <!-- 右端：業者マイページを別タブで開く（ゴールドのゴーストピル）。 -->
                                <a
                                    v-if="row.vendorUrl"
                                    :href="row.vendorUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    title="業者マイページを別タブで開く"
                                    class="inline-flex shrink-0 items-center gap-1 whitespace-nowrap rounded-full border border-[#c4a35b]/50 px-2.5 py-0.5 text-xs font-semibold text-[#8a6a25] transition-colors hover:border-[#c4a35b] hover:bg-[#c4a35b] hover:text-white"
                                >
                                    <ExternalLink class="size-3" />業者マイページ
                                </a>
                            </div>
                        </td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ yen(row.masterPrice) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ yen(row.budgetPrice) }}</td>
                        <td v-if="config.showQuote" class="px-3 py-2 text-right tabular-nums">{{ yen(row.quotePrice) }}</td>
                        <!-- 仮選定（FELIXが依頼したい業者の印。現状はローカル状態、DB保存は将来）。 -->
                        <td v-if="isVendorSelection" class="px-3 py-2 text-center">
                            <label
                                v-if="row.companyId != null"
                                class="inline-flex cursor-pointer items-center justify-center"
                                title="仮選定（依頼したい業者にマーク）"
                            >
                                <input type="checkbox" :checked="isProvisional(row)" class="sr-only" @change="emit('provisional-toggle', row)" />
                                <span
                                    class="flex size-5 items-center justify-center rounded-[5px] border-2 transition"
                                    :class="isProvisional(row)
                                        ? 'border-[#c4a35b] bg-[#c4a35b] text-white'
                                        : 'border-[#c4a35b]/60 bg-white text-transparent hover:border-[#c4a35b]'"
                                >
                                    <Check class="size-3.5" :stroke-width="3" />
                                </span>
                            </label>
                            <span v-else :class="mutedTextClass">—</span>
                        </td>
                        <td class="px-3 py-2 text-center">
                            <!-- 業者選定（toggle）：サーバ状態を初期値に押下でトグル（業者の選び替え）。 -->
                            <template v-if="isToggleButton">
                                <button
                                    v-if="row.companyId != null"
                                    type="button"
                                    :class="[selectBtnClass(row), !hasQuoteAnswer(row) && 'pointer-events-none opacity-40']"
                                    :disabled="!hasQuoteAnswer(row)"
                                    :title="hasQuoteAnswer(row) ? '' : '見積回答が無いため選定できません'"
                                    @click="emit('row-toggle', row)"
                                >
                                    <CheckCircle2 v-if="isActive(row)" class="absolute left-2.5 size-4" />
                                    {{ isActive(row) ? config.activeLabel : config.idleLabel }}
                                </button>
                                <span v-else :class="mutedTextClass">—</span>
                            </template>
                            <!-- 見積依頼 / 部長承認 / 取消申請 / 取消承認（pick）。処理済みは静的バッジ（再操作不可）。 -->
                            <template v-else>
                                <span v-if="isApplied(row)" class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-600">
                                    <CheckCircle2 class="size-4" />{{ config.appliedLabel }}
                                </span>
                                <template v-else-if="row.companyId != null">
                                    <!-- 見積依頼：選択チップ（枠付き）。中身はネイティブ checkbox のままで多重選択を維持。 -->
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
                                    <!-- 部長承認 / 取消申請 / 取消承認：ボタン（押下で名称・色が切替） -->
                                    <button v-else type="button" :class="selectBtnClass(row)" @click="emit('row-toggle', row)">
                                        <CheckCircle2 v-if="isActive(row)" class="absolute left-2.5 size-4" />
                                        {{ isActive(row) ? config.activeLabel : config.idleLabel }}
                                    </button>
                                </template>
                                <span v-else :class="mutedTextClass">—</span>
                            </template>
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

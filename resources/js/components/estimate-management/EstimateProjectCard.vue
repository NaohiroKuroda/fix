<script setup lang="ts">
// 1案件分の明細カード（タイトル帯＋テーブル）。開閉とチェックは親へ emit で委譲。
import { computed } from 'vue';
import { ChevronDown, CheckCircle2, Check, Plus, ExternalLink, Ban, MessageSquare } from 'lucide-vue-next';
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
    /** 仮選定（FELIXが依頼したい業者の印）の行キー集合。見積依頼画面のみ。 */
    provisionalKeys?: Set<string>;
}>();
const emit = defineEmits<{
    (e: 'toggle'): void;
    (e: 'row-toggle', row: EstimateManagementRow): void;
    (e: 'row-action', row: EstimateManagementRow): void;
    (e: 'provisional-toggle', row: EstimateManagementRow): void;
    (e: 'reject', row: EstimateManagementRow): void;
    (e: 'open-chat', row: EstimateManagementRow, buildingName: string): void;
    (e: 'open-iframe', payload: { url: string | null; title: string }): void;
    /** 請求先行（billingTarget）：チェック不要で押下即座に見積を送信する。見積依頼画面のみ。 */
    (e: 'billing-send', row: EstimateManagementRow): void;
}>();

const isThemed = computed(() => props.glass === true);
const config = computed(() => ESTIMATE_MODE_CONFIG[props.mode]);
const isCheckbox = computed(() => config.value.kind === 'checkbox');
// 取消申請 / 取消承認：行ボタンで理由モーダルを開き1件ずつ実行する（選択トグルではない）。
const isPerRowAction = computed(() => props.mode === 'cancel-request' || props.mode === 'cancel-approval');
// 見積依頼画面のみ、見積先名を業者マイページへのリンクにする。
const isQuoteRequest = computed(() => props.mode === 'quote-request');
// 「仮選定」列は見積依頼画面のみ出す。
const showProvisional = computed(() => props.mode === 'quote-request');
// 「見積依頼送信回数」列も見積依頼画面のみ（右端）。
const showSendCount = computed(() => props.mode === 'quote-request');
// 「否認」列は部長承認画面のみ（右端）。否認＝業者選定へ差し戻し。
const showReject = computed(() => props.mode === 'manager-approval');
// やり取り（チャット）は全画面で行う。
// 列は設けず、項目名の右隣に吹き出しボタンとして表示する。
const showChat = computed(() => true);
const isToggleButton = computed(() => config.value.kind === 'toggle-button');
// 区分列（請求/支払バッジ）＋相見積列＋仮選定列＋送信回数列＋否認列。空行の colspan に使う。
const columnCount = computed(
    () =>
        5 +
        (isQuoteRequest.value ? 1 : 0) +
        (config.value.showQuote ? 1 : 0) +
        (showProvisional.value ? 1 : 0) +
        (showSendCount.value ? 1 : 0) +
        (showReject.value ? 1 : 0),
);
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
// ただし reselectable（見積依頼）はロックせず、常にチェックボックスを出す（何度でも依頼可）。
const isApplied = (row: EstimateManagementRow): boolean =>
    !config.value.reselectable && row[config.value.appliedKey] === true;

// 業者選定系ボタンの配色パレット（押下＝ベタ塗り「選定済」、未押下＝淡色「選定する」）。
// gold=通常 / green=仮選定・否認解消 / red=否認差し戻し。どの色も同じ2状態デザインに揃える。
// ※ Tailwind のクラス検出のため、色クラスは必ずリテラル文字列で持つ（動的生成しない）。
type ToggleColor = 'gold' | 'green' | 'red';
const TOGGLE_CLASSES: Record<ToggleColor, { themedActive: string; themedIdle: string; plainActive: string; plainIdle: string }> = {
    gold: {
        themedActive: 'border-[#c4a35b] bg-[#c4a35b] text-white shadow-sm hover:bg-[#b3923f]',
        themedIdle: 'border-[#c4a35b]/40 bg-[#c4a35b]/10 text-[#8a6a25] shadow-sm backdrop-blur-md hover:border-[#c4a35b]/60 hover:bg-[#c4a35b]/20',
        plainActive: 'bg-primary text-primary-foreground hover:opacity-90',
        plainIdle: 'border bg-primary/5 text-muted-foreground hover:bg-accent',
    },
    green: {
        themedActive: 'border-emerald-500 bg-emerald-500 text-white shadow-sm hover:bg-emerald-600',
        themedIdle: 'border-emerald-500/40 bg-emerald-500/10 text-emerald-700 shadow-sm backdrop-blur-md hover:border-emerald-500/60 hover:bg-emerald-500/20',
        plainActive: 'bg-emerald-500 text-white hover:bg-emerald-600',
        plainIdle: 'border border-emerald-500/40 bg-emerald-500/5 text-emerald-700 hover:bg-emerald-500/10',
    },
    red: {
        themedActive: 'border-red-500 bg-red-500 text-white shadow-sm hover:bg-red-600',
        themedIdle: 'border-red-500/40 bg-red-500/10 text-red-700 shadow-sm backdrop-blur-md hover:border-red-500/60 hover:bg-red-500/20',
        plainActive: 'bg-red-500 text-white hover:bg-red-600',
        plainIdle: 'border border-red-500/40 bg-red-500/5 text-red-700 hover:bg-red-500/10',
    },
};
const btnShapeBase = 'relative mx-auto flex h-9 w-28 items-center justify-center whitespace-nowrap px-2 text-sm font-semibold transition';
// 色 × 状態（選定済/選定する）から業者選定ボタンのクラスを組み立てる。
const toggleBtnClass = (color: ToggleColor, active: boolean): string => {
    const c = TOGGLE_CLASSES[color];
    if (!isThemed.value) {
        return `${btnShapeBase} rounded-md ${active ? c.plainActive : c.plainIdle}`;
    }
    return `${btnShapeBase} rounded-xl border ${active ? c.themedActive : c.themedIdle}`;
};

/** 表示用に組み立てた1行（払い/もらいの並び替え・境界線・項目の rowspan 情報を持つ）。 */
interface DisplayRow {
    row: EstimateManagementRow;
    /** もらい（請求先）→払い（通常）の境界の先頭行か。両方揃う項目にだけ線を引く。 */
    isBillingGroupStart: boolean;
    /** この項目（unitId）の表示上の先頭行か。項目名セルはここにだけ rowspan で出す。 */
    isUnitFirstRow: boolean;
    /** 項目名セルの rowspan 数（isUnitFirstRow の行でのみ使う＝同一項目の行数）。 */
    unitRowSpan: number;
    /** 直前の項目との境界（＝2つ目以降の項目の先頭行）か。項目間の区切り線に使う。 */
    isUnitBoundary: boolean;
}
// 見積依頼画面：項目（unitId）内で「もらい（請求先）」を先、「払い（通常）」を後にまとめ、
// 境界に線を引けるようにする（billingTarget 行の並び替えは quote-request のみ意味を持つが、
// 他画面には billingTarget 行が現れないため実質的に元の並びのままになる）。
// 項目名は「項目１つに複数の払い/もらいがある」ため、同一項目の行をまたいで rowspan で1回だけ出す
// （felix_total の実行予算編集画面と同じ考え方）。
const displayRows = computed<DisplayRow[]>(() => {
    const unitOrder: number[] = [];
    const byUnit = new Map<number, EstimateManagementRow[]>();
    for (const row of props.project.rows) {
        if (!byUnit.has(row.unitId)) {
            byUnit.set(row.unitId, []);
            unitOrder.push(row.unitId);
        }
        byUnit.get(row.unitId)!.push(row);
    }
    const result: DisplayRow[] = [];
    unitOrder.forEach((unitId, unitIndex) => {
        const rows = byUnit.get(unitId) ?? [];
        const payments = rows.filter((r) => !r.billingTarget);
        const billings = rows.filter((r) => r.billingTarget);
        const ordered = [...billings, ...payments];
        ordered.forEach((row, i) => {
            result.push({
                row,
                isBillingGroupStart: payments.length > 0 && billings.length > 0 && i === billings.length,
                isUnitFirstRow: i === 0,
                unitRowSpan: ordered.length,
                isUnitBoundary: i === 0 && unitIndex > 0,
            });
        });
    });
    return result;
});

// 否認差し戻し（deny_comment あり）の見積先か。
const isDenied = (row: EstimateManagementRow): boolean => row.denied === true;
// 同一項目のいずれかの見積先が選定（選択）されているか。否認差し戻しの解消判定に使う。
const itemHasSelection = (row: EstimateManagementRow): boolean =>
    props.project.rows.some((r) => r.unitId === row.unitId && isActive(r));
// メインボタンの状態色：
// - 赤  ：否認で差し戻され、まだ同一項目のどの業者も選定されていない。
// - 緑  ：仮選定済み、または否認差し戻しがいずれかの業者の選定で解消された（赤→緑）。
// - base：上記いずれでもない（現状のゴールド表示のまま）。
type RowColor = 'base' | 'green' | 'red';
const rowColor = (row: EstimateManagementRow): RowColor => {
    if (isDenied(row) && !itemHasSelection(row)) {
        return 'red';
    }
    if (isProvisional(row) || (isDenied(row) && itemHasSelection(row))) {
        return 'green';
    }
    return 'base';
};
// メインボタン（業者選定のトグル／承認・取消のボタン）の配色。
// 状態色（赤=否認差し戻し / 緑=仮選定・否認解消 / gold=通常）を選び、選定済/選定する で
// 淡色⇄ベタ塗りが切り替わる同一デザインにする。
const mainBtnClass = (row: EstimateManagementRow): string => {
    const color = rowColor(row);
    const toggleColor: ToggleColor = color === 'base' ? 'gold' : color;
    return toggleBtnClass(toggleColor, isActive(row));
};

// コメント（やり取り）ボタンの配色。コメントが1件以上ある項目は選定ボタンと同じ配色で強調する。
const chatBtnClass = (row: EstimateManagementRow): string => {
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
            <span class="flex-1">No.{{ project.no ?? '—' }}　{{ project.name }}</span>
            <ChevronDown class="size-4 shrink-0 transition-transform" :class="open ? '' : '-rotate-90'" />
        </button>
        <div v-show="open" class="overflow-x-auto">
            <table class="w-full min-w-[960px] table-fixed text-[15px]" :class="cellTextClass">
                <!-- 全案件カードで列位置を揃えるため、固定レイアウト＋共通の列幅を指定する。 -->
                <colgroup>
                    <col style="width: 22%" />
                    <col v-if="isQuoteRequest" style="width: 7%" />
                    <col style="width: 23%" />
                    <col style="width: 13%" />
                    <col style="width: 13%" />
                    <col v-if="config.showQuote" style="width: 14%" />
                    <col v-if="showProvisional" style="width: 9%" />
                    <col style="width: 15%" />
                    <col v-if="showSendCount" style="width: 11%" />
                    <col v-if="showReject" style="width: 11%" />
                </colgroup>
                <thead class="text-center" :class="tableHeadClass">
                    <tr class="border-b-2 border-slate-300 text-[15px] font-bold uppercase tracking-wider">
                        <th class="px-3 py-2.5">項目</th>
                        <th v-if="isQuoteRequest" class="px-3 py-2.5">区分</th>
                        <th class="px-3 py-2.5">パートナー</th>
                        <th class="px-3 py-2.5">標準単価<br />(税抜)</th>
                        <th class="px-3 py-2.5">予算単価<br />(税抜)</th>
                        <th v-if="config.showQuote" class="px-3 py-2.5">{{ config.quoteColumnLabel ?? '相見積' }}<br />(税抜)</th>
                        <th v-if="showProvisional" class="px-3 py-2.5">仮選定</th>
                        <th class="px-3 py-2.5">{{ config.columnLabel }}</th>
                        <th v-if="showSendCount" class="px-3 py-2.5">送信回数</th>
                        <th v-if="showReject" class="px-3 py-2.5">否認</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="{ row, isBillingGroupStart, isUnitFirstRow, unitRowSpan, isUnitBoundary } in displayRows"
                        :key="estimateRowKey(row)"
                        :class="[
                            rowBorderClass,
                            isUnitBoundary
                                ? 'border-t-4 border-slate-300'
                                : (isBillingGroupStart ? 'border-t-2 border-dashed border-slate-400' : 'border-t'),
                        ]"
                    >
                        <!-- 項目名：同一項目の行数ぶん rowspan で1回だけ出す（1項目に複数の払い/もらいがあるため）。 -->
                        <td v-if="isUnitFirstRow" :rowspan="unitRowSpan" class="px-3 py-2 font-medium">
                            <div class="flex items-center justify-between gap-2">
                                <span>{{ row.itemName }}</span>
                                <!-- 項目名の右隣（右寄せ）：やり取り（吹き出し）＋業者追加（見積依頼のみ・吹き出しの右隣）。 -->
                                <div class="flex shrink-0 items-center gap-1.5">
                                    <!-- やり取り（チャット）（全画面）。押下でチャットモーダル。 -->
                                    <!-- 項目に見積先が複数あっても、表示中の項目の先頭行に1つだけ出す。 -->
                                    <button
                                        v-if="showChat && row.companyId != null"
                                        type="button"
                                        :class="chatBtnClass(row)"
                                        title="この見積についてやり取りする"
                                        @click="emit('open-chat', row, project.name)"
                                    >
                                        <MessageSquare class="size-4.5" />
                                        <!-- 未読数バッジ（自分の役割から見た相手の新着）。0は非表示。 -->
                                        <span
                                            v-if="row.unreadCount"
                                            class="absolute -right-1.5 -top-1.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold leading-none text-white tabular-nums"
                                        >{{ row.unreadCount > 99 ? '99+' : row.unreadCount }}</span>
                                    </button>
                                    <!-- この項目に業者を追加（見積依頼画面のみ・押すと iframe を起動）。 -->
                                    <button
                                        v-if="isQuoteRequest"
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
                        <!-- 区分（もらい/請求・払い/支払）：クリック不可の表示のみバッジ。見積依頼画面のみ。色は付けず文言のみで区別する。 -->
                        <td v-if="isQuoteRequest" class="px-3 py-2 text-center">
                            <span
                                v-if="row.companyId != null"
                                class="inline-flex shrink-0 items-center justify-center whitespace-nowrap rounded-full border border-slate-400/60 bg-slate-50 px-3 py-0.5 text-sm font-medium text-slate-600"
                            >{{ row.billingTarget ? '請求' : '支払' }}</span>
                            <span v-else :class="mutedTextClass">—</span>
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
                        <!-- 請求先行（billingTarget）：金額3列は常に「ー」表示（見積の対象ではないため）。 -->
                        <td class="px-3 py-2 text-right tabular-nums">{{ row.billingTarget ? '—' : yen(row.masterPrice) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ row.billingTarget ? '—' : yen(row.budgetPrice) }}</td>
                        <td v-if="config.showQuote" class="px-3 py-2 text-right tabular-nums">{{ row.billingTarget ? '—' : yen(row.quotePrice) }}</td>
                        <!-- 仮選定（FELIXが依頼したい業者の印。現状はローカル状態、DB保存は将来）。 -->
                        <td v-if="showProvisional" class="px-3 py-2 text-center">
                            <label
                                v-if="row.companyId != null && !row.billingTarget"
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
                                    :class="[mainBtnClass(row), !hasQuoteAnswer(row) && 'pointer-events-none opacity-40']"
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
                                    <!-- 請求先行（billingTarget・見積依頼画面のみ）：チェック不要、押下で即座に見積送信。 -->
                                    <button
                                        v-if="row.billingTarget"
                                        type="button"
                                        class="mx-auto flex h-9 w-28 items-center justify-center whitespace-nowrap rounded-xl border border-[#c4a35b] bg-[#c4a35b] px-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#b3923f]"
                                        title="請求先へ見積を送信する"
                                        @click="emit('billing-send', row)"
                                    >
                                        見積送信
                                    </button>
                                    <!-- 見積依頼：選択チップ（枠付き）。中身はネイティブ checkbox のままで多重選択を維持。 -->
                                    <label
                                        v-else-if="isCheckbox"
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
                                    <!-- 部長承認：選択トグル（ヘッダー確定で一括）。取消申請/承認：押下で理由モーダル→1件実行。 -->
                                    <button
                                        v-else
                                        type="button"
                                        :class="mainBtnClass(row)"
                                        @click="isPerRowAction ? emit('row-action', row) : emit('row-toggle', row)"
                                    >
                                        <CheckCircle2 v-if="!isPerRowAction && isActive(row)" class="absolute left-2.5 size-4" />
                                        {{ isPerRowAction ? config.idleLabel : (isActive(row) ? config.activeLabel : config.idleLabel) }}
                                    </button>
                                </template>
                                <span v-else :class="mutedTextClass">—</span>
                            </template>
                        </td>
                        <!-- 見積依頼送信回数（右端・見積依頼画面のみ）。0=未依頼は淡色で表示。 -->
                        <td v-if="showSendCount" class="px-3 py-2 text-center tabular-nums">
                            <span v-if="row.companyId != null" :class="row.sendCount === 0 ? mutedTextClass : 'font-semibold'">
                                {{ row.sendCount }}回
                            </span>
                            <span v-else :class="mutedTextClass">—</span>
                        </td>
                        <!-- 否認（右端・部長承認画面のみ）。押下で理由入力モーダル→業者選定へ差し戻し。 -->
                        <td v-if="showReject" class="px-3 py-2 text-center">
                            <button
                                v-if="row.companyId != null"
                                type="button"
                                class="relative mx-auto flex h-9 w-24 items-center justify-center gap-1 whitespace-nowrap rounded-xl border border-red-400/60 bg-red-50 px-2 text-sm font-semibold text-red-600 shadow-sm transition hover:border-red-500 hover:bg-red-100"
                                title="否認して業者選定へ差し戻す"
                                @click="emit('reject', row)"
                            >
                                <Ban class="size-4" />否認
                            </button>
                            <span v-else :class="mutedTextClass">—</span>
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

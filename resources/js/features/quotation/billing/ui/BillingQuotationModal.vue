<script setup lang="ts">
// 【請求】見積作成モーダル（もらい運用フロー ③・⭐️新規画面）。
//
// 列構成は felix_total の業者マイページ「見積」タブ（resources/views/pages/estimate/_estimate_table.blade.php）
// を踏襲する：拠点 / 部門 / 依頼内容 / 数量 / 単位 / 税区分 / 税種類 / 単価 / 金額。
// 保存先は t_billing_quotations（ヘッダー）と t_billing_quotation_details（明細）。
//
// 表示ルール:
// - 既存データがある場合、**is_changed = true の明細だけ**を表示する
//   （felix_total の fix_flg と同じ。空の予備行は is_changed = false で保存されるため）。
// - 未入力の行は保存時に is_changed = false で送り、一覧には出さない。
//
// 金額について:
// - 確定値はサーバ側（BCMath）で計算する。ここの合計は**入力確認用の目安**（frontend.md §4.9）。
// - 目安の計算も誤差を出さないよう整数演算だけで行う（× 0.1 のような小数倍を避ける）。
import { computed, reactive, ref, watch } from 'vue';
import { X, Plus, Trash2 } from 'lucide-vue-next';
import type { BillingMasters, BillingQuotationDetail, BillingRow, BillingTaxType } from '../model/billing';

const props = defineProps<{
    open: boolean;
    row: BillingRow | null;
    buildingName: string;
    masters: BillingMasters;
    processing?: boolean;
    /** 閲覧専用（見積承認などから内容確認だけで開く）。入力とボタンを無効化する。 */
    readonly?: boolean;
}>();
const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'submit', payload: BillingQuotationInput): void;
}>();

/** 明細1行の入力値（画面用。数量・金額は入力途中を許すため number | null）。 */
type DetailInput = {
    id: number | null;
    isMemo: boolean;
    branchCode: number | null;
    departmentId: number | null;
    name: string;
    quantity: number | null;
    unitId: number | null;
    unitPrice: number | null;
    taxType: BillingTaxType;
    isTaxInclusive: boolean;
    price: number | null;
};

/** サーバへ送る形（t_billing_quotations ＋ t_billing_quotation_details）。 */
export type BillingQuotationInput = {
    partnerId: number;
    quotationDate: string;
    /** 消費税調整（端数調整・手入力調整の差分）。 */
    taxAdjust: number;
    withholdingIncomeTax: number | null;
    comment: string;
    /** 既にアップロード済みのファイル URL（差し替えない場合はそのまま送る）。 */
    fileUrl: string;
    /** 端末から選択した見積書ファイル。未選択は null（サーバ側で file_url を更新しない）。 */
    file: File | null;
    /** 明細。空行は isChanged=false で送り、サーバ側は一覧に出さない。 */
    details: (DetailInput & { taxRate: string; isChanged: boolean })[];
};

/** 消費税率（felix_total の tax_list に対応）。課税=10% / 非課税=0%。 */
const TAX_RATE: Record<BillingTaxType, string> = { TAXABLE: '0.10', NON_TAXABLE: '0.00' };
/** 課税区分の選択肢（felix_total: 1.10=課税 / 1.00=非課税）。 */
const TAX_TYPES: { value: BillingTaxType; label: string }[] = [
    { value: 'TAXABLE', label: '課税' },
    { value: 'NON_TAXABLE', label: '非課税' },
];

const emptyDetail = (): DetailInput => ({
    id: null,
    isMemo: false,
    branchCode: null,
    departmentId: null,
    name: '',
    quantity: null,
    unitId: null,
    unitPrice: null,
    taxType: 'TAXABLE',
    isTaxInclusive: false,
    price: null,
});

/** 文字列で受け取った金額を入力用の数値に戻す（空は null）。 */
const toNum = (v: string | null | undefined): number | null =>
    v === null || v === undefined || v === '' ? null : Number(v);

const form = reactive({
    quotationDate: '',
    withholdingIncomeTax: null as number | null,
    comment: '',
    fileUrl: '',
    file: null as File | null,
    details: [emptyDetail()] as DetailInput[],
    /** 「課税」欄の手入力値。null なら自動計算値をそのまま使う。 */
    taxOverride: null as number | null,
});

// 開くたびに初期化する。既存見積があれば is_changed の行だけを読み込む（＝felix_total の fix_flg と同じ）。
watch(
    () => props.open,
    (open) => {
        if (!open) {
            return;
        }
        const quotation = props.row?.quotation ?? null;
        form.quotationDate = quotation?.quotationDate ?? '';
        form.withholdingIncomeTax = toNum(quotation?.withholdingIncomeTax);
        form.comment = quotation?.comment ?? '';
        // 見積書ファイルは画面から選ばせないが、既存の file_url は保存時にそのまま返して消さない。
        form.fileUrl = quotation?.fileUrl ?? '';
        form.file = null;
        form.taxOverride = null;

        const rows = (quotation?.details ?? [])
            .filter((d: BillingQuotationDetail) => d.isChanged)
            .map((d: BillingQuotationDetail): DetailInput => ({
                id: d.id,
                isMemo: d.isMemo,
                branchCode: d.branchCode,
                departmentId: d.departmentId,
                name: d.name,
                quantity: d.quantity,
                unitId: d.unitId,
                unitPrice: toNum(d.unitPrice),
                taxType: d.taxType,
                isTaxInclusive: d.isTaxInclusive,
                price: toNum(d.price),
            }));
        form.details = rows.length > 0 ? rows : [emptyDetail()];
    },
);

const addDetail = (): void => {
    form.details = [...form.details, emptyDetail()];
};
const removeDetail = (index: number): void => {
    form.details = form.details.length <= 1 ? [emptyDetail()] : form.details.filter((_, i) => i !== index);
};

/** 数量・単価の変更で金額を再計算する（どちらも整数のため誤差は出ない）。 */
const recalcPrice = (detail: DetailInput): void => {
    if (detail.isMemo || detail.quantity === null || detail.unitPrice === null) {
        return;
    }
    detail.price = detail.quantity * detail.unitPrice;
};

/** 行が「使用中」か（＝ is_changed。メモ行は内容があれば使用中）。 */
const isUsed = (d: DetailInput): boolean =>
    d.isMemo ? d.name.trim() !== '' : d.name.trim() !== '' || d.price !== null;

/** 税抜金額（税込入力の行は税抜へ戻す）。整数演算のみで丸める。 */
const excludingTax = (d: DetailInput): number => {
    const price = d.price ?? 0;
    if (!d.isTaxInclusive) {
        return price;
    }
    // 税込 → 税抜。× (1/1.1) を避け、整数の 100/110 で計算する。
    return Math.round((price * 100) / 110);
};

const usedDetails = computed(() => form.details.filter((d) => isUsed(d) && !d.isMemo));

/** 税別合計（目安）。 */
const subtotal = computed(() => usedDetails.value.reduce((sum, d) => sum + excludingTax(d), 0));
/** 課税対象の税抜合計。 */
const taxableSubtotal = computed(() =>
    usedDetails.value.filter((d) => d.taxType === 'TAXABLE').reduce((sum, d) => sum + excludingTax(d), 0),
);
/** 自動計算の消費税（合計に対して 10%）。× 0.1 を避け 10/100 で計算する。 */
const taxAuto = computed(() => Math.round((taxableSubtotal.value * 10) / 100));
/** 行ごとに丸めた税込合計（端数確認用。felix_total の genka_total_taxin_check と同じ）。 */
const taxinByRow = computed(() =>
    usedDetails.value.reduce((sum, d) => {
        const price = d.price ?? 0;
        if (d.isTaxInclusive) {
            return sum + price;
        }
        return sum + (d.taxType === 'TAXABLE' ? Math.round((price * 110) / 100) : price);
    }, 0),
);
/** 画面に出す消費税額（手入力があればそちら）。 */
const taxAmount = computed(() => form.taxOverride ?? taxinByRow.value - subtotal.value);
/** 消費税調整＝表示中の消費税額 − 自動計算値（felix_total の tax_adjust と同じ考え方）。 */
const taxAdjust = computed(() => taxAmount.value - taxAuto.value);
/** 税込金額。 */
const totalIncludingTax = computed(() => subtotal.value + taxAmount.value);
/** 差引支払額（税込 − 源泉所得税）。 */
const netAmount = computed(() => totalIncludingTax.value - (form.withholdingIncomeTax ?? 0));

const canSubmit = computed(() => form.quotationDate !== '' && usedDetails.value.length > 0 && !props.processing);

const submit = (): void => {
    if (!canSubmit.value || props.row === null) {
        return;
    }
    emit('submit', {
        partnerId: props.row.partnerId,
        quotationDate: form.quotationDate,
        taxAdjust: taxAdjust.value,
        withholdingIncomeTax: form.withholdingIncomeTax,
        comment: form.comment,
        fileUrl: form.fileUrl,
        file: form.file,
        // 空行も送り、サーバ側で is_changed=false として保存する（felix_total の fix_flg と同じ）。
        // メモ行は依頼内容以外を NULL にして送る（画面で入力していた値は保持しないため）。
        details: form.details.map((d) => ({
            ...(d.isMemo ? { ...emptyDetail(), id: d.id, isMemo: true, name: d.name } : d),
            taxRate: TAX_RATE[d.taxType],
            isChanged: isUsed(d),
        })),
    });
};

const inputClass =
    'h-9 w-full rounded-lg border border-slate-300 bg-white px-2 text-sm text-slate-800 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 disabled:bg-slate-100 disabled:text-slate-400';
const numClass = `${inputClass} text-right tabular-nums`;
const yen = (n: number): string => `¥${n.toLocaleString('ja-JP')}`;
</script>

<template>
    <div v-if="open && row" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="emit('close')">
        <div class="flex max-h-[92vh] w-full max-w-[1200px] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
            <!-- ヘッダー：見積管理の他モーダルと同じ紺帯＋金のキーライン -->
            <div class="flex items-center gap-3 border-l-4 border-l-[#c4a35b] bg-primary px-4 py-3 text-primary-foreground">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold">
                        {{ readonly ? '【請求】見積内容' : `【請求】見積${row.quotation ? '修正' : '作成'}` }}
                    </p>
                    <p class="truncate text-xs opacity-80">{{ buildingName }}／{{ row.itemName }}／{{ row.vendorName }}</p>
                </div>
                <button type="button" class="rounded-lg p-1 hover:bg-white/10" @click="emit('close')">
                    <X class="size-5" />
                </button>
            </div>

            <!-- 閲覧専用のときは fieldset ごと無効化して、中の入力・追加/削除ボタンをまとめて止める。 -->
            <fieldset :disabled="readonly" class="flex-1 space-y-4 overflow-y-auto p-4">
                <!-- ヘッダー項目（t_billing_quotations） -->
                <!-- 見積書ファイルの選択は出さない（作成・修正・閲覧いずれも非表示）。
                     既にアップロード済みの file_url は保存時にそのまま送り、消さない。 -->
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <label class="text-sm">
                        <span class="mb-1 block text-xs text-slate-500">日付 <span class="text-red-600">*</span></span>
                        <input v-model="form.quotationDate" type="date" :class="inputClass" />
                    </label>
                </div>

                <!-- 明細（t_billing_quotation_details）。列は felix_total の見積タブと同じ並び。 -->
                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full min-w-[1040px] text-sm">
                        <thead class="bg-zinc-100 text-zinc-700">
                            <tr class="text-xs font-bold uppercase tracking-wider">
                                <th class="px-2 py-2 text-center" style="width: 62px">メモ行</th>
                                <th class="px-2 py-2 text-center" style="width: 110px">拠点</th>
                                <th class="px-2 py-2 text-center" style="width: 150px">部門</th>
                                <th class="px-2 py-2 text-left">依頼内容</th>
                                <th class="px-2 py-2 text-center" style="width: 90px">数量</th>
                                <th class="px-2 py-2 text-center" style="width: 100px">単位</th>
                                <th class="px-2 py-2 text-center" style="width: 100px">税区分</th>
                                <th class="px-2 py-2 text-center" style="width: 90px">税種類</th>
                                <th class="px-2 py-2 text-center" style="width: 120px">単価</th>
                                <th class="px-2 py-2 text-center" style="width: 130px">金額</th>
                                <th class="px-2 py-2" style="width: 44px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(detail, index) in form.details" :key="index" class="border-t border-slate-100">
                                <td class="px-2 py-1.5 text-center">
                                    <input v-model="detail.isMemo" type="checkbox" class="size-4 cursor-pointer accent-[#c4a35b]" title="メモ行（依頼内容だけを全列にわたって表示する行）" />
                                </td>
                                <!--
                                    メモ行：依頼内容以外の列は出さず、依頼内容を全列にまたいで入力できるようにする
                                    （felix_total の見積タブと同じ colspan 表示）。保存時は依頼内容以外を NULL にする。
                                -->
                                <td v-if="detail.isMemo" class="bg-slate-50/60 px-2 py-1.5" colspan="9">
                                    <input v-model="detail.name" type="text" :class="inputClass" placeholder="メモ（見積書に文章として表示する行）" />
                                </td>
                                <template v-else>
                                    <td class="px-2 py-1.5">
                                        <select v-model="detail.branchCode" :class="inputClass">
                                            <option :value="null">--</option>
                                            <option v-for="b in masters.branches" :key="b.code" :value="b.code">{{ b.name }}</option>
                                        </select>
                                    </td>
                                    <td class="px-2 py-1.5">
                                        <select v-model="detail.departmentId" :class="inputClass">
                                            <option :value="null">--</option>
                                            <option v-for="d in masters.departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                                        </select>
                                    </td>
                                    <td class="px-2 py-1.5">
                                        <input v-model="detail.name" type="text" :class="inputClass" />
                                    </td>
                                    <td class="px-2 py-1.5">
                                        <input v-model.number="detail.quantity" type="number" :class="numClass" @change="recalcPrice(detail)" />
                                    </td>
                                    <td class="px-2 py-1.5">
                                        <select v-model="detail.unitId" :class="inputClass">
                                            <option :value="null">--</option>
                                            <option v-for="u in masters.units" :key="u.id" :value="u.id">{{ u.name }}</option>
                                        </select>
                                    </td>
                                    <td class="px-2 py-1.5">
                                        <select v-model="detail.taxType" :class="inputClass">
                                            <option v-for="t in TAX_TYPES" :key="t.value" :value="t.value">{{ t.label }}</option>
                                        </select>
                                    </td>
                                    <td class="px-2 py-1.5">
                                        <select v-model="detail.isTaxInclusive" :class="inputClass">
                                            <option :value="false">税別</option>
                                            <option :value="true">税込</option>
                                        </select>
                                    </td>
                                    <td class="px-2 py-1.5">
                                        <input v-model.number="detail.unitPrice" type="number" :class="numClass" @change="recalcPrice(detail)" />
                                    </td>
                                    <td class="px-2 py-1.5">
                                        <input v-model.number="detail.price" type="number" :class="numClass" />
                                    </td>
                                </template>
                                <td class="px-2 py-1.5 text-center">
                                    <button type="button" class="rounded-lg p-1 text-slate-500 transition hover:bg-red-50 hover:text-red-600" title="この行を削除" @click="removeDetail(index)">
                                        <Trash2 class="size-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <!-- 合計行（felix_total の見積タブと同じ並び：税別合計 / 課税 / 税込 / 源泉所得税） -->
                        <tfoot class="border-t-2 border-slate-300 bg-slate-50 text-sm">
                            <tr>
                                <td class="px-2 py-2" colspan="2">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 whitespace-nowrap rounded-full border border-teal-600/50 px-3 py-1 text-sm font-medium text-teal-700 transition-colors hover:border-teal-600 hover:bg-teal-600 hover:text-white"
                                        @click="addDetail"
                                    >
                                        <Plus class="size-3.5" />追加
                                    </button>
                                </td>
                                <td class="px-2 py-2 text-right font-bold" colspan="7">税別合計</td>
                                <td class="px-2 py-2 text-right font-bold tabular-nums" colspan="2">{{ yen(subtotal) }}</td>
                            </tr>
                            <tr>
                                <td class="px-2 py-2 text-right font-bold" colspan="9">課税（10%）</td>
                                <td class="px-2 py-1.5" colspan="2">
                                    <input
                                        :value="taxAmount"
                                        type="number"
                                        :class="numClass"
                                        title="端数調整が必要なときだけ手で直す（差分が消費税調整として保存される）"
                                        @input="form.taxOverride = ($event.target as HTMLInputElement).value === '' ? null : Number(($event.target as HTMLInputElement).value)"
                                    />
                                </td>
                            </tr>
                            <tr>
                                <td class="px-2 py-2 text-right font-bold" colspan="9">税込金額</td>
                                <td class="px-2 py-2 text-right font-bold tabular-nums" colspan="2">{{ yen(totalIncludingTax) }}</td>
                            </tr>
                            <tr>
                                <td class="px-2 py-2 text-right font-bold" colspan="9">源泉所得及び復興特別所得税</td>
                                <td class="px-2 py-1.5" colspan="2">
                                    <input v-model.number="form.withholdingIncomeTax" type="number" :class="numClass" />
                                </td>
                            </tr>
                            <tr class="border-t border-slate-200">
                                <td class="px-2 py-2 text-right font-bold" colspan="9">差引支払額</td>
                                <td class="px-2 py-2 text-right text-base font-bold tabular-nums" colspan="2">{{ yen(netAmount) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <label class="block text-sm">
                    <span class="mb-1 block text-xs text-slate-500">コメント</span>
                    <textarea v-model="form.comment" rows="3" class="w-full rounded-lg border border-slate-300 bg-white p-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"></textarea>
                </label>
            </fieldset>

            <div class="flex items-center justify-between gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3">
                <p class="text-xs text-slate-500">
                    {{ readonly ? '内容の確認のみ。ここでは編集できません。' : '入力のある行だけが保存されます（空行は一覧に出ません）。' }}
                </p>
                <div class="flex gap-2">
                    <button type="button" class="h-9 rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-100" @click="emit('close')">
                        {{ readonly ? '閉じる' : 'キャンセル' }}
                    </button>
                    <button
                        v-if="!readonly"
                        type="button"
                        class="h-9 rounded-xl px-4 text-sm font-semibold transition"
                        :class="canSubmit
                            ? 'border border-[#c4a35b] bg-[#c4a35b] text-white hover:bg-[#b3923f]'
                            : 'cursor-not-allowed border border-[#c4a35b]/40 bg-[#c4a35b]/10 text-[#8a6a25]/60'"
                        :disabled="!canSubmit"
                        :title="canSubmit ? '' : '日付と明細（1行以上）を入力してください'"
                        @click="submit"
                    >
                        {{ processing ? '保存中…' : '保存' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

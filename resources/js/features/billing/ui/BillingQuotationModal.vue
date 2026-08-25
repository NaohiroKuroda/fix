<script setup lang="ts">
// ③ もらいの見積作成モーダル（⭐️新規画面）。
// 【請求】見積作成の一覧行から開き、見積額を入力して保存する。
// 入力項目は t_billing_quotations / t_billing_quotation_ditails に対応する
// （docs/detailed-design/quotations/06_請求_見積作成_詳細設計.md §6.2）。
//
// 金額計算はサーバ側（BCMath）で確定する。ここで出す合計は **表示上の目安**であり、
// 確定値は保存後のサーバ応答で上書きされる（docs/architecture/frontend.md §4.9）。
import { computed, reactive, watch } from 'vue';
import { X, Plus, Trash2 } from 'lucide-vue-next';
import type { BillingRow } from '../model/billing';

const props = defineProps<{
    open: boolean;
    row: BillingRow | null;
    buildingName: string;
    processing?: boolean;
}>();
const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'submit', payload: BillingQuotationInput): void;
}>();

/**
 * 明細1行の入力値。数量・単価は整数のため、目安計算も整数演算で完結する。
 * Inertia の POST ボディへそのまま載せられるよう、interface ではなく type で定義する
 * （type は暗黙のインデックスシグネチャを持ち、FormDataConvertible の Record へ代入できる）。
 */
type DetailInput = {
    isMemo: boolean;
    name: string;
    quantity: number | null;
    unitName: string;
    unitPrice: number | null;
    taxType: 'TAXABLE' | 'NON_TAXABLE';
    isTaxInclusive: boolean;
};

/** モーダルの入力値（サーバへ送る形）。 */
export type BillingQuotationInput = {
    partnerId: number;
    quotationDate: string;
    taxAdjust: number | null;
    withholdingIncomeTax: number | null;
    comment: string;
    fileUrl: string;
    details: DetailInput[];
};

const emptyDetail = (): DetailInput => ({
    isMemo: false,
    name: '',
    quantity: null,
    unitName: '',
    unitPrice: null,
    taxType: 'TAXABLE',
    isTaxInclusive: false,
});

const form = reactive({
    quotationDate: '',
    taxAdjust: null as number | null,
    withholdingIncomeTax: null as number | null,
    comment: '',
    fileUrl: '',
    details: [emptyDetail()] as DetailInput[],
});

// 開くたびに初期化する（前回の入力を持ち越さない）。
watch(
    () => props.open,
    (open) => {
        if (!open) {
            return;
        }
        form.quotationDate = '';
        form.taxAdjust = null;
        form.withholdingIncomeTax = null;
        form.comment = '';
        form.fileUrl = '';
        form.details = [emptyDetail()];
    },
);

const addDetail = (): void => {
    form.details = [...form.details, emptyDetail()];
};
const removeDetail = (index: number): void => {
    form.details = form.details.length <= 1 ? [emptyDetail()] : form.details.filter((_, i) => i !== index);
};

/** 明細1行の金額（数量 × 単価）。どちらも整数のため誤差は出ない。メモ行は 0。 */
const lineAmount = (detail: DetailInput): number =>
    detail.isMemo || detail.quantity === null || detail.unitPrice === null ? 0 : detail.quantity * detail.unitPrice;

/** 税抜合計（**目安**）。確定値はサーバが BCMath で計算する。 */
const subtotalPreview = computed(() => form.details.reduce((sum, d) => sum + lineAmount(d), 0));

const canSubmit = computed(() => form.quotationDate !== '' && !props.processing);

const submit = (): void => {
    if (!canSubmit.value || props.row === null) {
        return;
    }
    emit('submit', {
        partnerId: props.row.partnerId,
        quotationDate: form.quotationDate,
        taxAdjust: form.taxAdjust,
        withholdingIncomeTax: form.withholdingIncomeTax,
        comment: form.comment,
        fileUrl: form.fileUrl,
        details: form.details,
    });
};

const inputClass =
    'h-9 w-full rounded-lg border border-slate-300 bg-white px-2 text-sm text-slate-800 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20';
const yen = (n: number): string => `¥${n.toLocaleString('ja-JP')}`;
</script>

<template>
    <div v-if="open && row" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="emit('close')">
        <div class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center gap-3 border-b border-l-4 border-l-[#c4a35b] bg-primary px-4 py-3 text-primary-foreground">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold">【請求】見積作成</p>
                    <p class="truncate text-xs opacity-80">{{ buildingName }}／{{ row.itemName }}／{{ row.vendorName }}</p>
                </div>
                <button type="button" class="rounded-lg p-1 hover:bg-white/10" @click="emit('close')">
                    <X class="size-5" />
                </button>
            </div>

            <div class="flex-1 space-y-4 overflow-y-auto p-4">
                <!-- ヘッダー（t_billing_quotations） -->
                <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                    <label class="text-sm">
                        <span class="mb-1 block text-xs text-slate-500">見積日 <span class="text-red-600">*</span></span>
                        <input v-model="form.quotationDate" type="date" :class="inputClass" />
                    </label>
                    <label class="text-sm">
                        <span class="mb-1 block text-xs text-slate-500">消費税調整</span>
                        <input v-model.number="form.taxAdjust" type="number" :class="inputClass" />
                    </label>
                    <label class="text-sm">
                        <span class="mb-1 block text-xs text-slate-500">源泉所得税</span>
                        <input v-model.number="form.withholdingIncomeTax" type="number" :class="inputClass" />
                    </label>
                    <label class="text-sm">
                        <span class="mb-1 block text-xs text-slate-500">ファイルURL</span>
                        <input v-model="form.fileUrl" type="text" :class="inputClass" />
                    </label>
                </div>

                <!-- 明細（t_billing_quotation_ditails） -->
                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full min-w-[760px] text-sm">
                        <thead class="bg-zinc-100 text-zinc-700">
                            <tr class="text-left text-xs font-bold uppercase tracking-wider">
                                <th class="px-2 py-2">メモ行</th>
                                <th class="px-2 py-2">項目名</th>
                                <th class="px-2 py-2 text-right">数量</th>
                                <th class="px-2 py-2">単位</th>
                                <th class="px-2 py-2 text-right">単価</th>
                                <th class="px-2 py-2">課税区分</th>
                                <th class="px-2 py-2">税込</th>
                                <th class="px-2 py-2 text-right">金額</th>
                                <th class="px-2 py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(detail, index) in form.details" :key="index" class="border-t border-slate-100">
                                <td class="px-2 py-1.5 text-center">
                                    <input v-model="detail.isMemo" type="checkbox" class="size-4 accent-[#c4a35b]" />
                                </td>
                                <td class="px-2 py-1.5"><input v-model="detail.name" type="text" :class="inputClass" /></td>
                                <td class="px-2 py-1.5">
                                    <input v-model.number="detail.quantity" :disabled="detail.isMemo" type="number" :class="[inputClass, 'text-right']" />
                                </td>
                                <td class="px-2 py-1.5"><input v-model="detail.unitName" :disabled="detail.isMemo" type="text" :class="inputClass" /></td>
                                <td class="px-2 py-1.5">
                                    <input v-model.number="detail.unitPrice" :disabled="detail.isMemo" type="number" :class="[inputClass, 'text-right']" />
                                </td>
                                <td class="px-2 py-1.5">
                                    <select v-model="detail.taxType" :disabled="detail.isMemo" :class="inputClass">
                                        <option value="TAXABLE">課税</option>
                                        <option value="NON_TAXABLE">非課税</option>
                                    </select>
                                </td>
                                <td class="px-2 py-1.5 text-center">
                                    <input v-model="detail.isTaxInclusive" :disabled="detail.isMemo" type="checkbox" class="size-4 accent-[#c4a35b]" />
                                </td>
                                <td class="px-2 py-1.5 text-right tabular-nums">{{ yen(lineAmount(detail)) }}</td>
                                <td class="px-2 py-1.5 text-center">
                                    <button type="button" class="rounded-lg p-1 text-slate-500 hover:bg-red-50 hover:text-red-600" @click="removeDetail(index)">
                                        <Trash2 class="size-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 rounded-full border border-teal-600/50 px-3 py-1 text-sm font-medium text-teal-700 transition-colors hover:border-teal-600 hover:bg-teal-600 hover:text-white"
                        @click="addDetail"
                    >
                        <Plus class="size-3.5" />明細を追加
                    </button>
                    <p class="text-sm text-slate-600">
                        税抜合計（目安）: <span class="text-base font-bold tabular-nums text-slate-900">{{ yen(subtotalPreview) }}</span>
                    </p>
                </div>

                <label class="block text-sm">
                    <span class="mb-1 block text-xs text-slate-500">コメント</span>
                    <textarea v-model="form.comment" rows="3" class="w-full rounded-lg border border-slate-300 bg-white p-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"></textarea>
                </label>

                <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">
                    合計・消費税の確定値はサーバ側（BCMath）で計算します。ここに出している合計は入力確認用の目安です。
                </p>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3">
                <button type="button" class="h-9 rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-100" @click="emit('close')">
                    キャンセル
                </button>
                <button
                    type="button"
                    class="h-9 rounded-xl px-4 text-sm font-semibold transition"
                    :class="canSubmit ? 'border border-[#c4a35b] bg-[#c4a35b] text-white hover:bg-[#b3923f]' : 'cursor-not-allowed border border-[#c4a35b]/40 bg-[#c4a35b]/10 text-[#8a6a25]/60'"
                    :disabled="!canSubmit"
                    @click="submit"
                >
                    {{ processing ? '保存中…' : '保存' }}
                </button>
            </div>
        </div>
    </div>
</template>

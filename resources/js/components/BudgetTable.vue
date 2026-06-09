<script setup lang="ts">
// 実行予算一覧テーブル（旧 estimate_list を全列踏襲）。
// - estimate_aggregates 由来の全列を横スクロール表示
// - 物件名列は左固定。クリックで実行予算詳細(active=2 相当)を新規タブで開く
import { Badge } from '@/components/ui/badge';
import { ExternalLink } from 'lucide-vue-next';
import { yen } from '@/lib/format';
import type { AggCell, ExecutionBudget } from '@/types';

defineProps<{
    budgets: ExecutionBudget[];
}>();

/** 物件名クリックの遷移先（新規タブ）。旧 new-estimates-custom-edit?id=&active=2 に相当。 */
const detailUrl = (id: number): string => `/estimates/${id}`;

type ColType = 'text' | 'date' | 'money' | 'moneySingle' | 'percent' | 'empty'
    | 'contractNameDate' | 'dateStatus' | 'bankPair' | 'moneyPair' | 'moneyDealer' | 'yield';

interface Col {
    header: string;
    type: ColType;
    key?: string;
    keyLand?: string;
    keyBuild?: string;
    keyDealer?: string;
    align?: 'left' | 'right' | 'center';
    width: number;
}

interface Line {
    text: string;
    label?: string;
    red?: boolean;
}

// 旧 estimate_list の表示列を全て踏襲（空プレースホルダ列も含む）。
const columns: Col[] = [
    { header: '住所', type: 'text', key: 'address', align: 'left', width: 200 },
    { header: '仕入契約日', type: 'contractNameDate', align: 'left', width: 160 },
    { header: '販売資料格納日', type: 'empty', width: 110 },
    { header: '販売掲載日', type: 'date', key: 'sail_start_date', width: 110 },
    { header: '仕入司法書士', type: 'text', key: 'purchasing_judicial_scrivener', align: 'left', width: 150 },
    { header: '土地価格', type: 'moneySingle', key: 'land_purchase_price', width: 120 },
    { header: '土地諸経費', type: 'money', key: 'land_expenses_price', width: 130 },
    { header: '土地・諸経費', type: 'money', key: 'land_purchase_total_price', width: 130 },
    { header: '仕入金融機関名', type: 'bankPair', keyLand: 'pj_bank_land', keyBuild: 'pj_bank_build', align: 'left', width: 180 },
    { header: '融資枠', type: 'moneyPair', keyLand: 'land_loan_facility', keyBuild: 'build_loan_facility', width: 130 },
    { header: '金利', type: 'empty', width: 80 },
    { header: '仕入決済日', type: 'date', key: 'purchase_settlement_date', width: 110 },
    { header: '確認申請完了日', type: 'date', key: 'confirmation_application_completion_date', width: 120 },
    { header: '入居掲載日', type: 'empty', width: 110 },
    { header: '販売買付申込入金日', type: 'date', key: 'sales_contract_payment_date', width: 130 },
    { header: '販売契約日', type: 'dateStatus', key: 'sales_contract_date', width: 120 },
    { header: 'オーナー名', type: 'text', key: 'owner_name', align: 'left', width: 150 },
    { header: 'DL名', type: 'text', key: 'dealer_name', align: 'left', width: 130 },
    { header: '金融機関名', type: 'text', key: 'owner_bank_name', align: 'left', width: 150 },
    { header: '融資額', type: 'empty', width: 100 },
    { header: '金利', type: 'empty', width: 80 },
    { header: '期間', type: 'empty', width: 80 },
    { header: '販売司法書士', type: 'text', key: 'sales_judicial_scrivener', align: 'left', width: 150 },
    { header: '販売引渡日', type: 'date', key: 'delivery_date', width: 110 },
    { header: '仲介手数料', type: 'money', key: 'brokerage_fee_price', width: 130 },
    { header: 'コンサル手数料', type: 'money', key: 'consulting_fee_price', width: 130 },
    { header: '仲手＋コンサル', type: 'money', key: 'brokerage_consulting_fee_price', width: 130 },
    { header: '土地原価', type: 'moneyDealer', key: 'land_cost_total_price', keyDealer: 'dealer_land_cost_total_price', width: 140 },
    { header: '土価売価', type: 'moneyDealer', key: 'land_sell_total_price', keyDealer: 'dealer_land_sell_total_price', width: 140 },
    { header: '建物原価', type: 'moneyDealer', key: 'build_cost_total_price', keyDealer: 'dealer_build_cost_total_price', width: 140 },
    { header: '建物売価', type: 'moneyDealer', key: 'build_sell_total_price', keyDealer: 'dealer_build_sell_total_price', width: 140 },
    { header: '土地建原価', type: 'moneyDealer', key: 'cost_total_price', keyDealer: 'dealer_cost_total_price', width: 140 },
    { header: '土地建売価', type: 'moneyDealer', key: 'sell_total_price', keyDealer: 'dealer_sell_total_price', width: 140 },
    { header: '土地建粗利額', type: 'money', key: 'gross_profit_price', width: 140 },
    { header: '粗利率', type: 'percent', key: 'gross_profit_margin', width: 90 },
    { header: '販売利回り', type: 'yield', key: 'product_yield', keyDealer: 'dealer_product_yield', width: 110 },
];

const cellOf = (b: ExecutionBudget, key?: string): AggCell | undefined => (key ? b.agg[key] : undefined);

function buildLines(b: ExecutionBudget, col: Col): Line[] {
    const c = cellOf(b, col.key);
    switch (col.type) {
        case 'empty':
            return [{ text: '—' }];
        case 'text':
            return [{ text: c?.s ?? '—' }];
        case 'date':
            return [{ text: c?.d ?? '—' }];
        case 'dateStatus': {
            const status = cellOf(b, 'contract_status')?.s;
            return [{ text: c?.d ?? '—' }, ...(status ? [{ text: status }] : [])];
        }
        case 'contractNameDate': {
            const name = cellOf(b, 'purchase_contract_name')?.s;
            const date = cellOf(b, 'purchase_contract_date')?.d;
            return [{ text: name ?? '売主名無し', red: !name }, { text: date ?? '—' }];
        }
        case 'moneySingle':
            return [{ text: yen(c?.v), label: '税別' }];
        case 'money': {
            const taxIn = cellOf(b, (col.key ?? '') + '_tax_in');
            return [{ text: yen(c?.v), label: '税別' }, { text: yen(taxIn?.v), label: '税込' }];
        }
        case 'moneyDealer': {
            const taxIn = cellOf(b, (col.key ?? '') + '_tax_in');
            const lines: Line[] = [{ text: yen(c?.v), label: '税別' }, { text: yen(taxIn?.v), label: '税込' }];
            const dealer = cellOf(b, col.keyDealer);
            const dealerTaxIn = cellOf(b, (col.keyDealer ?? '') + '_tax_in');
            if (dealer?.v != null || dealerTaxIn?.v != null) {
                lines.push({ text: yen(dealer?.v), label: 'DL税別' }, { text: yen(dealerTaxIn?.v), label: 'DL税込' });
            }
            return lines;
        }
        case 'percent':
            return [{ text: c?.v != null ? c.v + '%' : '—', label: '税別' }];
        case 'yield': {
            const dealer = cellOf(b, col.keyDealer);
            const lines: Line[] = [{ text: c?.v != null ? c.v + '%' : '—', label: 'F' }];
            if (dealer?.v != null) lines.push({ text: dealer.v + '%', label: 'D' });
            return lines;
        }
        case 'bankPair': {
            const both = cellOf(b, 'pj_bank_land_build')?.s;
            const land = both ?? cellOf(b, col.keyLand)?.s;
            const build = both ?? cellOf(b, col.keyBuild)?.s;
            return [{ text: land ?? '未設定', label: '土地', red: !land }, { text: build ?? '未設定', label: '建物', red: !build }];
        }
        case 'moneyPair':
            return [{ text: yen(cellOf(b, col.keyLand)?.v), label: '土地' }, { text: yen(cellOf(b, col.keyBuild)?.v), label: '建物' }];
        default:
            return [{ text: '—' }];
    }
}

const alignClass = (a?: string): string => (a === 'left' ? 'text-left' : a === 'center' ? 'text-center' : 'text-right');
const justifyClass = (a?: string): string => (a === 'left' ? 'justify-start' : a === 'center' ? 'justify-center' : 'justify-end');
const isNumeric = (t: ColType): boolean => t.startsWith('money') || t === 'percent' || t === 'yield';

/** ラベルの色（旧 estimate_list の label-success=緑 を踏襲。ディーラー値は区別）。 */
const labelVariant = (label?: string): string => {
    if (!label) return 'muted';
    if (label.startsWith('DL') || label === 'D') return 'secondary';
    return 'success'; // 税別 / 税込 / 土地 / 建物 / F
};
</script>

<template>
    <div class="overflow-x-auto rounded-xl border bg-card">
        <table class="border-separate border-spacing-0 text-sm">
            <thead>
                <tr class="[&>th]:bg-primary [&>th]:text-primary-foreground [&>th]:px-3 [&>th]:py-3 [&>th]:text-xs [&>th]:font-semibold [&>th]:tracking-wide [&>th]:whitespace-nowrap [&>th]:border-b-2 [&>th]:border-r [&>th]:border-white/15 [&>th]:align-middle [&>th]:text-center">
                    <th class="sticky left-0 top-0 z-30 w-[300px] min-w-[300px] shadow-[1px_0_0_0_rgba(255,255,255,0.25)]">物件名</th>
                    <th
                        v-for="col in columns"
                        :key="col.header"
                        :style="{ width: col.width + 'px', minWidth: col.width + 'px' }"
                    >
                        {{ col.header }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="b in budgets" :key="b.id" class="group border-b transition-colors hover:bg-accent">
                    <!-- 物件名（固定・新規タブリンク） -->
                    <td class="sticky left-0 z-10 bg-card group-hover:bg-accent border-b border-r px-3 py-2.5 align-top w-[300px] min-w-[300px] shadow-[1px_0_0_0_var(--border)]">
                        <a
                            :href="detailUrl(b.id)"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="group/link inline-flex items-start gap-1.5"
                        >
                            <span class="min-w-0">
                                <span class="font-mono text-xs text-muted-foreground">No.{{ b.id }}</span>
                                <span class="block font-medium leading-snug text-primary group-hover/link:underline">
                                    {{ b.name }}
                                    <ExternalLink class="inline size-3.5 -mt-0.5 opacity-60" />
                                </span>
                                <Badge v-if="b.hasTmpBudget" variant="secondary" class="mt-1 font-normal">仮実行予算</Badge>
                            </span>
                        </a>
                    </td>
                    <!-- 全財務列 -->
                    <td
                        v-for="col in columns"
                        :key="col.header"
                        class="border-b px-3 py-2.5 align-top"
                        :class="alignClass(col.align)"
                    >
                        <div
                            v-for="(line, li) in buildLines(b, col)"
                            :key="li"
                            class="flex items-center gap-1.5 leading-6"
                            :class="justifyClass(col.align)"
                        >
                            <span :class="[line.red ? 'text-destructive' : '', isNumeric(col.type) ? 'font-mono tabular-nums' : '']">{{ line.text }}</span>
                            <Badge v-if="line.label" :variant="labelVariant(line.label)" class="font-normal text-[10px] px-1 py-0">{{ line.label }}</Badge>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

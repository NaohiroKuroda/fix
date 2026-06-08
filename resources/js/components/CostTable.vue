<script setup lang="ts">
// 実行予算詳細の原価明細テーブル（旧 c_estimate_cost_list-ajax の全列を踏襲）。
// AJAX 描画では step を渡さないため no-step のフル列が出る:
// 項目 / 請求先(発注先) / 単価表 / 概算 / 相見積 / 確定見積 / 工期設定 / 選定 / 依頼 / 見積UP /
// 常務承認 / 承認 / 見積書発行日 / 承認納期日 / 発注書送付日 / 必須ファイル / 請求日 / 入出金日
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { ExternalLink, CircleCheck } from 'lucide-vue-next';
import type { EstimateUnit, UnitCompany } from '@/types';

defineProps<{
    units: EstimateUnit[];
}>();

const yen = (n: number | null): string => (n === null ? '—' : '¥' + n.toLocaleString('ja-JP'));

/** 採用業者の確定見積（last_price）。 */
const adoptedPrice = (u: EstimateUnit): number | null => {
    const a = u.companies.find((c: UnitCompany) => c.adopted) ?? u.companies[0];
    return a ? a.lastPrice : null;
};

/** 状態 → バッジ variant（旧 label-* の配色に合わせる）。 */
const variantOf = (state: string): string => {
    const map: Record<string, string> = {
        approved: 'info', selected: 'info', sent: 'info',
        has: 'success',
        denied: 'warning', replied: 'warning',
        pending: 'danger', canceled: 'danger',
        notified: 'secondary',
        none: 'muted',
    };
    return map[state] ?? 'muted';
};
</script>

<template>
    <div class="overflow-x-auto">
        <table class="border-separate border-spacing-0 text-sm">
            <thead>
                <tr class="[&>th]:bg-primary [&>th]:text-primary-foreground [&>th]:px-3 [&>th]:py-2.5 [&>th]:text-xs [&>th]:font-medium [&>th]:whitespace-nowrap">
                    <th class="sticky left-0 top-0 z-20 text-left! w-[230px] min-w-[230px]">項目</th>
                    <th class="sticky left-[230px] top-0 z-20 text-left! w-[220px] min-w-[220px] shadow-[1px_0_0_0_rgba(255,255,255,0.25)]">請求先 / 発注先</th>
                    <th class="text-right w-[100px] min-w-[100px]">単価表(税抜)</th>
                    <th class="text-right w-[100px] min-w-[100px]">概算(税抜)</th>
                    <th class="text-right w-[110px] min-w-[110px]">相見積(税抜)</th>
                    <th class="text-right w-[120px] min-w-[120px]">確定見積(税抜)</th>
                    <th class="w-[150px] min-w-[150px]">工期設定</th>
                    <th class="w-[60px] min-w-[60px]">選定</th>
                    <th class="w-[120px] min-w-[120px]">依頼</th>
                    <th class="w-[120px] min-w-[120px]">見積UP</th>
                    <th class="w-[130px] min-w-[130px]">常務承認</th>
                    <th class="w-[70px] min-w-[70px]">承認</th>
                    <th class="w-[110px] min-w-[110px]">見積書発行日</th>
                    <th class="w-[120px] min-w-[120px]">承認納期日</th>
                    <th class="w-[120px] min-w-[120px]">発注書送付日</th>
                    <th class="w-[100px] min-w-[100px]">必須ファイル</th>
                    <th class="w-[110px] min-w-[110px]">請求日</th>
                    <th class="w-[110px] min-w-[110px]">入出金日</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="unit in units"
                    :key="unit.id"
                    class="border-b transition-colors"
                    :class="unit.useFlg === 1 ? 'bg-card hover:bg-accent/40' : 'bg-muted/60 text-muted-foreground hover:bg-muted'"
                >
                    <!-- 項目 -->
                    <td class="sticky left-0 z-10 bg-inherit border-b border-r px-3 py-2.5 align-top w-[230px] min-w-[230px]">
                        <div class="font-medium leading-snug" :class="unit.useFlg !== 1 ? 'line-through' : ''">{{ unit.label || '（名称未設定）' }}</div>
                        <Badge v-if="unit.subCateLabel" variant="secondary" class="mt-1 font-normal">{{ unit.subCateLabel }}</Badge>
                    </td>

                    <!-- 請求先 / 発注先 -->
                    <td class="sticky left-[230px] z-10 bg-inherit border-b border-r px-3 py-2.5 align-top w-[220px] min-w-[220px] shadow-[1px_0_0_0_var(--border)]">
                        <div v-if="unit.companies.length" class="flex flex-col gap-1.5">
                            <div v-for="(c, ci) in unit.companies" :key="ci" class="flex items-center justify-between gap-1.5 group/co">
                                <span class="truncate text-sm" :class="c.emphasized || c.adopted ? 'font-semibold' : ''">{{ c.name }}</span>
                                <div class="flex items-center gap-1 shrink-0">
                                    <Badge v-if="c.payName" variant="success" class="font-normal">{{ c.payName }}</Badge>
                                    <ExternalLink class="size-3.5 text-muted-foreground opacity-0 group-hover/co:opacity-100 transition" />
                                </div>
                            </div>
                        </div>
                        <span v-else class="text-muted-foreground">—</span>
                    </td>

                    <!-- 単価表 / 概算 -->
                    <td class="border-b px-3 py-2.5 text-right align-top font-mono tabular-nums text-muted-foreground">{{ yen(unit.masterPrice) }}</td>
                    <td class="border-b px-3 py-2.5 text-right align-top font-mono tabular-nums">{{ yen(unit.estimatePrice) }}</td>

                    <!-- 相見積（業者ごと・小） -->
                    <td class="border-b px-3 py-2.5 text-right align-top font-mono tabular-nums text-xs">
                        <template v-if="unit.companies.length">
                            <div v-for="(c, ci) in unit.companies" :key="ci" :class="c.changed ? 'font-semibold text-destructive' : ''">{{ yen(c.lastPrice) }}</div>
                        </template>
                        <span v-else class="text-muted-foreground">—</span>
                    </td>

                    <!-- 確定見積（採用・太字） -->
                    <td class="border-b px-3 py-2.5 text-right align-top font-mono tabular-nums text-base font-bold">{{ yen(adoptedPrice(unit)) }}</td>

                    <!-- 工期設定 -->
                    <td class="border-b px-3 py-2.5 text-center align-top text-xs">
                        <div v-if="unit.construct.date || unit.completion.date" class="flex flex-col items-center gap-0.5">
                            <div class="flex items-center gap-1">
                                <span class="font-mono tabular-nums">{{ unit.construct.date || '0000/00/00' }}</span>
                                <Badge :variant="unit.construct.set ? 'info' : 'danger'" class="font-normal">{{ unit.construct.label }}</Badge>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="font-mono tabular-nums">{{ unit.completion.date || '0000/00/00' }}</span>
                                <Badge :variant="unit.completion.set ? 'info' : 'danger'" class="font-normal">{{ unit.completion.label }}</Badge>
                            </div>
                        </div>
                        <span v-else class="text-muted-foreground">—</span>
                    </td>

                    <!-- 選定 -->
                    <td class="border-b px-3 py-2.5 text-center align-top">
                        <template v-if="unit.companies.length">
                            <div v-for="(c, ci) in unit.companies" :key="ci" class="leading-6">
                                <CircleCheck v-if="c.selected" class="inline size-4 text-blue-600" />
                                <span v-else class="text-muted-foreground">—</span>
                            </div>
                        </template>
                        <span v-else class="text-muted-foreground">—</span>
                    </td>

                    <!-- 依頼 -->
                    <td class="border-b px-3 py-2.5 text-center align-top text-xs">
                        <template v-if="unit.companies.length">
                            <div v-for="(c, ci) in unit.companies" :key="ci" class="flex flex-col items-center gap-0.5 py-0.5">
                                <span class="font-mono tabular-nums">{{ c.request.date || '0000/00/00' }}</span>
                                <Badge :variant="variantOf(c.request.state)" class="font-normal">{{ c.request.label }}<template v-if="c.request.count"> ({{ c.request.count }})</template></Badge>
                            </div>
                        </template>
                        <span v-else class="text-muted-foreground">—</span>
                    </td>

                    <!-- 見積UP -->
                    <td class="border-b px-3 py-2.5 text-center align-top text-xs">
                        <template v-if="unit.companies.length">
                            <div v-for="(c, ci) in unit.companies" :key="ci" class="flex flex-col items-center gap-0.5 py-0.5">
                                <span class="font-mono tabular-nums">{{ c.estimateUp.date || '0000/00/00' }}</span>
                                <Badge :variant="variantOf(c.estimateUp.state)" class="font-normal">{{ c.estimateUp.label }}<template v-if="c.estimateUp.count"> ({{ c.estimateUp.count }})</template></Badge>
                            </div>
                        </template>
                        <span v-else class="text-muted-foreground">—</span>
                    </td>

                    <!-- 常務承認 -->
                    <td class="border-b px-3 py-2.5 text-center align-top text-xs">
                        <template v-if="unit.companies.length">
                            <div v-for="(c, ci) in unit.companies" :key="ci" class="flex flex-col items-center gap-0.5 py-0.5">
                                <span class="font-mono tabular-nums">{{ c.execApproval.date || '0000/00/00' }}</span>
                                <Badge :variant="variantOf(c.execApproval.state)" class="font-normal">{{ c.execApproval.label || '—' }}</Badge>
                            </div>
                        </template>
                        <span v-else class="text-muted-foreground">—</span>
                    </td>

                    <!-- 承認（採用） -->
                    <td class="border-b px-3 py-2.5 text-center align-top">
                        <template v-if="unit.companies.length">
                            <div v-for="(c, ci) in unit.companies" :key="ci" class="leading-6">
                                <Checkbox :model-value="c.adopted" />
                            </div>
                        </template>
                        <span v-else class="text-muted-foreground">—</span>
                    </td>

                    <!-- 見積書発行日（レガシ同様 空） -->
                    <td class="border-b px-3 py-2.5 text-center align-top text-muted-foreground">—</td>

                    <!-- 承認納期日 -->
                    <td class="border-b px-3 py-2.5 text-center align-top text-xs">
                        <div class="flex flex-col items-center gap-0.5">
                            <span class="font-mono tabular-nums">{{ unit.replyDate.date || '—' }}</span>
                            <Badge v-if="unit.replyDate.label" :variant="variantOf(unit.replyDate.state)" class="font-normal">{{ unit.replyDate.label }}</Badge>
                        </div>
                    </td>

                    <!-- 発注書送付日 -->
                    <td class="border-b px-3 py-2.5 text-center align-top text-xs">
                        <div class="flex flex-col items-center gap-0.5">
                            <span class="font-mono tabular-nums">{{ unit.orderDate.date || '—' }}</span>
                            <Badge v-if="unit.orderDate.label" :variant="variantOf(unit.orderDate.state)" class="font-normal">{{ unit.orderDate.label }}</Badge>
                        </div>
                    </td>

                    <!-- 必須ファイル -->
                    <td class="border-b px-3 py-2.5 text-center align-top text-xs">
                        <Badge :variant="variantOf(unit.requiredFile.state)" class="font-normal">{{ unit.requiredFile.label }}</Badge>
                    </td>

                    <!-- 請求日 / 入出金日（レガシ同様 空） -->
                    <td class="border-b px-3 py-2.5 text-center align-top text-muted-foreground">—</td>
                    <td class="border-b px-3 py-2.5 text-center align-top text-muted-foreground">—</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

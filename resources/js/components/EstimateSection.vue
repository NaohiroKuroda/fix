<script setup lang="ts">
// 1案件（実行予算）分のステータス管理テーブル。
// - ヘッダークリックでアコーディオン開閉
// - 明細は横スクロール、項目/発注先列は左固定
// - 既存ステータス画面の全列を実データで表示
import { ref, computed } from 'vue';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Building2, ExternalLink, ChevronDown } from 'lucide-vue-next';
import type { Estimate } from '@/types';

const props = defineProps<{
    estimate: Estimate;
    defaultOpen?: boolean;
}>();

const open = ref(props.defaultOpen ?? true);

const yen = (n: number | null): string => (n === null ? '—' : '¥' + n.toLocaleString('ja-JP'));

const activeUnits = computed(() => props.estimate.units.filter((u) => u.useFlg === 1).length);
const totalAmount = computed(() =>
    props.estimate.units.filter((u) => u.useFlg === 1).reduce((s, u) => s + (u.estimatePrice ?? 0), 0),
);

/** 状態 → バッジ variant */
const variantOf = (state: string): string => {
    const map: Record<string, string> = {
        approved: 'success', sent: 'success', has: 'success',
        replied: 'warning', denied: 'warning', notified: 'secondary',
        pending: 'muted', none: 'muted', canceled: 'destructive',
    };
    return map[state] ?? 'muted';
};
</script>

<template>
    <Card class="overflow-hidden gap-0 py-0">
        <!-- アコーディオンヘッダー -->
        <CardHeader
            class="flex-row items-center justify-between gap-3 border-b bg-card px-5 py-3.5 cursor-pointer select-none hover:bg-accent/40 transition-colors"
            @click="open = !open"
        >
            <div class="flex items-center gap-3 min-w-0">
                <ChevronDown class="size-5 text-muted-foreground transition-transform" :class="open ? '' : '-rotate-90'" />
                <span class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <Building2 class="size-5" />
                </span>
                <div class="min-w-0">
                    <span class="font-mono text-xs text-muted-foreground">No.{{ estimate.id }}</span>
                    <h2 class="truncate text-base font-semibold tracking-tight">{{ estimate.name }}</h2>
                </div>
            </div>
            <div class="flex items-center gap-4 shrink-0">
                <div class="hidden sm:flex flex-col items-end">
                    <span class="text-xs text-muted-foreground">採用 {{ activeUnits }} 件 / 概算合計</span>
                    <span class="font-mono text-sm font-semibold tabular-nums">{{ yen(totalAmount) }}</span>
                </div>
                <Badge variant="secondary" class="font-normal">{{ estimate.units.length }} 明細</Badge>
            </div>
        </CardHeader>

        <CardContent v-show="open" class="p-0">
            <div class="overflow-x-auto">
                <table class="w-full border-separate border-spacing-0 text-sm">
                    <thead>
                        <tr class="[&>th]:bg-primary [&>th]:text-primary-foreground [&>th]:px-3 [&>th]:py-2.5 [&>th]:text-xs [&>th]:font-medium [&>th]:whitespace-nowrap [&>th]:align-middle">
                            <th class="sticky left-0 top-0 z-30 text-left! w-[230px] min-w-[230px]">項目</th>
                            <th class="sticky left-[230px] top-0 z-30 text-left! w-[210px] min-w-[210px] shadow-[1px_0_0_0_rgba(255,255,255,0.25)]">発注先</th>
                            <th class="text-right w-[110px] min-w-[110px]">単価表(税抜)</th>
                            <th class="text-right w-[110px] min-w-[110px]">概算(税抜)</th>
                            <th class="text-right w-[110px] min-w-[110px]">相見積(税抜)</th>
                            <th class="w-[60px] min-w-[60px]">選定</th>
                            <th class="w-[130px] min-w-[130px]">工期設定</th>
                            <th class="w-[130px] min-w-[130px]">依頼</th>
                            <th class="w-[130px] min-w-[130px]">見積UP</th>
                            <th class="w-[130px] min-w-[130px]">建設部選定</th>
                            <th class="w-[130px] min-w-[130px]">設計部選定</th>
                            <th class="w-[130px] min-w-[130px]">常務承認</th>
                            <th class="w-[120px] min-w-[120px]">承認納期日</th>
                            <th class="w-[120px] min-w-[120px]">発注書送付日</th>
                            <th class="w-[100px] min-w-[100px]">必須ファイル</th>
                            <th class="w-[120px] min-w-[120px]">第一承認</th>
                            <th class="w-[120px] min-w-[120px]">第二承認</th>
                            <th class="w-[120px] min-w-[120px]">第三承認</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="unit in estimate.units"
                            :key="unit.id"
                            class="border-b transition-colors"
                            :class="unit.useFlg === 1 ? 'bg-card hover:bg-accent/40' : 'bg-muted/60 text-muted-foreground hover:bg-muted'"
                        >
                            <!-- 項目 -->
                            <td class="sticky left-0 z-10 bg-inherit border-b border-r px-3 py-3 align-top w-[230px] min-w-[230px]">
                                <div class="flex items-start gap-2">
                                    <Checkbox :model-value="unit.useFlg === 1" class="mt-0.5" />
                                    <div class="min-w-0">
                                        <div class="font-medium leading-snug" :class="unit.useFlg !== 1 ? 'line-through' : ''">{{ unit.label || '（名称未設定）' }}</div>
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            <Badge v-if="unit.subCateLabel" variant="secondary" class="font-normal">{{ unit.subCateLabel }}</Badge>
                                            <Badge v-if="unit.useFlg !== 1" variant="muted">停止中</Badge>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- 発注先 -->
                            <td class="sticky left-[230px] z-10 bg-inherit border-b border-r px-3 py-3 align-top w-[210px] min-w-[210px] shadow-[1px_0_0_0_var(--border)]">
                                <div v-if="unit.companies.length" class="flex flex-col gap-1.5">
                                    <div v-for="(c, ci) in unit.companies" :key="ci" class="flex items-center justify-between gap-1.5 group/co">
                                        <span class="truncate text-sm" :class="c.emphasized || c.adopted ? 'font-semibold' : ''">{{ c.name }}</span>
                                        <div class="flex items-center gap-1 shrink-0">
                                            <Badge v-if="c.payName" variant="success" class="font-normal">{{ c.payName }}</Badge>
                                            <Badge v-else-if="c.adopted" variant="success" class="font-normal">採用</Badge>
                                            <ExternalLink class="size-3.5 text-muted-foreground opacity-0 group-hover/co:opacity-100 transition" />
                                        </div>
                                    </div>
                                </div>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>

                            <!-- 単価表 / 概算 -->
                            <td class="border-b px-3 py-3 text-right align-top font-mono tabular-nums">{{ yen(unit.masterPrice) }}</td>
                            <td class="border-b px-3 py-3 text-right align-top font-mono tabular-nums">{{ yen(unit.estimatePrice) }}</td>

                            <!-- 相見積（業者ごと） -->
                            <td class="border-b px-3 py-3 text-right align-top font-mono tabular-nums">
                                <template v-if="unit.companies.length">
                                    <div v-for="(c, ci) in unit.companies" :key="ci" :class="c.changed ? 'font-semibold text-destructive' : ''">{{ yen(c.lastPrice) }}</div>
                                </template>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>

                            <!-- 選定（業者ごと） -->
                            <td class="border-b px-3 py-3 text-center align-top">
                                <template v-if="unit.companies.length">
                                    <div v-for="(c, ci) in unit.companies" :key="ci" class="leading-6">
                                        <span v-if="c.selected" class="text-success font-bold">●</span>
                                        <span v-else class="text-muted-foreground">—</span>
                                    </div>
                                </template>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>

                            <!-- 工期設定 -->
                            <td class="border-b px-3 py-3 text-center align-top text-xs">
                                <div class="flex flex-col items-center gap-0.5">
                                    <span class="font-mono tabular-nums">{{ unit.construct.date || '—' }}</span>
                                    <Badge :variant="unit.construct.set ? 'secondary' : 'muted'" class="font-normal">{{ unit.construct.label }}</Badge>
                                    <span class="font-mono tabular-nums mt-0.5">{{ unit.completion.date || '—' }}</span>
                                    <Badge :variant="unit.completion.set ? 'secondary' : 'muted'" class="font-normal">{{ unit.completion.label }}</Badge>
                                </div>
                            </td>

                            <!-- 依頼（業者ごと） -->
                            <td class="border-b px-3 py-3 text-center align-top text-xs">
                                <template v-if="unit.companies.length">
                                    <div v-for="(c, ci) in unit.companies" :key="ci" class="flex flex-col items-center gap-0.5 py-0.5">
                                        <span class="font-mono tabular-nums">{{ c.request.date || '—' }}</span>
                                        <Badge :variant="variantOf(c.request.state)" class="font-normal">{{ c.request.label }}<template v-if="c.request.count"> ({{ c.request.count }})</template></Badge>
                                    </div>
                                </template>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>

                            <!-- 見積UP（業者ごと） -->
                            <td class="border-b px-3 py-3 text-center align-top text-xs">
                                <template v-if="unit.companies.length">
                                    <div v-for="(c, ci) in unit.companies" :key="ci" class="flex flex-col items-center gap-0.5 py-0.5">
                                        <span class="font-mono tabular-nums">{{ c.estimateUp.date || '—' }}</span>
                                        <Badge :variant="variantOf(c.estimateUp.state)" class="font-normal">{{ c.estimateUp.label }}<template v-if="c.estimateUp.count"> ({{ c.estimateUp.count }})</template></Badge>
                                    </div>
                                </template>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>

                            <!-- 建設部選定 / 設計部選定 / 常務承認（業者ごと） -->
                            <td v-for="col in (['buildSelect','designSelect','execApproval'] as const)" :key="col" class="border-b px-3 py-3 text-center align-top text-xs">
                                <template v-if="unit.companies.length">
                                    <div v-for="(c, ci) in unit.companies" :key="ci" class="flex flex-col items-center gap-0.5 py-0.5">
                                        <span class="font-mono tabular-nums">{{ c[col].date || '—' }}</span>
                                        <Badge :variant="variantOf(c[col].state)" class="font-normal">{{ c[col].label || '—' }}</Badge>
                                        <span v-if="c[col].denial" class="text-destructive text-[10px] leading-tight">{{ c[col].denial }}</span>
                                    </div>
                                </template>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>

                            <!-- 承認納期日 -->
                            <td class="border-b px-3 py-3 text-center align-top text-xs">
                                <div class="flex flex-col items-center gap-0.5">
                                    <span class="font-mono tabular-nums">{{ unit.replyDate.date || '—' }}</span>
                                    <Badge v-if="unit.replyDate.label" :variant="variantOf(unit.replyDate.state)" class="font-normal">{{ unit.replyDate.label }}</Badge>
                                </div>
                            </td>

                            <!-- 発注書送付日 -->
                            <td class="border-b px-3 py-3 text-center align-top text-xs">
                                <div class="flex flex-col items-center gap-0.5">
                                    <span class="font-mono tabular-nums">{{ unit.orderDate.date || '—' }}</span>
                                    <Badge v-if="unit.orderDate.label" :variant="variantOf(unit.orderDate.state)" class="font-normal">{{ unit.orderDate.label }}</Badge>
                                </div>
                            </td>

                            <!-- 必須ファイル -->
                            <td class="border-b px-3 py-3 text-center align-top text-xs">
                                <Badge :variant="variantOf(unit.requiredFile.state)" class="font-normal">{{ unit.requiredFile.label }}</Badge>
                            </td>

                            <!-- 第一/第二/第三承認（業者ごと） -->
                            <td v-for="col in (['approval1','approval2','approval3'] as const)" :key="col" class="border-b px-3 py-3 text-center align-top text-xs">
                                <template v-if="unit.companies.length">
                                    <div v-for="(c, ci) in unit.companies" :key="ci" class="flex flex-col items-center gap-0.5 py-0.5">
                                        <template v-if="c[col].label">
                                            <span class="font-mono tabular-nums">{{ c[col].date || '—' }}</span>
                                            <Badge :variant="variantOf(c[col].state)" class="font-normal">{{ c[col].label }}</Badge>
                                        </template>
                                        <span v-else class="text-muted-foreground">—</span>
                                    </div>
                                </template>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </CardContent>
    </Card>
</template>

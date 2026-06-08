<script setup lang="ts">
// 実行予算 詳細（旧 new-estimates-custom-edit?active=2 踏襲）。
// 原価明細をセクション別（土地仕入/建設/金融/販売 等）に表示。
import { reactive, ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import CostTable from '@/components/CostTable.vue';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Wallet, MapPin, CalendarDays, ChevronDown, Search } from 'lucide-vue-next';
import type { BudgetSection, EstimateDetail } from '@/types';

const props = defineProps<{
    budget: EstimateDetail;
}>();

const yen = (n: number | null): string => (n === null ? '—' : '¥' + n.toLocaleString('ja-JP'));

const s = props.budget.summary;

// 明細キーワード検索（項目名で絞り込み・クライアント側）
const keyword = ref('');
const visibleSections = computed<BudgetSection[]>(() => {
    const kw = keyword.value.trim();
    if (!kw) return props.budget.sections;
    return props.budget.sections
        .map((sec) => ({ ...sec, units: sec.units.filter((u) => u.label.includes(kw)) }))
        .filter((sec) => sec.units.length > 0);
});

// セクションごとの開閉状態（既定: 開）
const closed = reactive<Record<string, boolean>>({});
const isOpen = (key: string): boolean => !closed[key];
const toggle = (key: string): void => {
    closed[key] = !closed[key];
};
</script>

<template>
    <Head :title="`実行予算 No.${budget.id}`" />
    <AppLayout>
        <!-- ヘッダー -->
        <header class="border-b bg-card">
            <div class="mx-auto max-w-[1680px] px-4 py-4 md:px-6">
                <div class="flex flex-wrap items-start gap-3">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm">
                        <Wallet class="size-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs text-muted-foreground">No.{{ budget.id }}</span>
                        </div>
                        <h1 class="text-lg font-bold tracking-tight">{{ budget.name }}</h1>
                        <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                            <span v-if="s.address" class="inline-flex items-center gap-1"><MapPin class="size-3.5" />{{ s.address }}</span>
                            <span v-if="s.deliveryDate" class="inline-flex items-center gap-1"><CalendarDays class="size-3.5" />引渡 {{ s.deliveryDate }}</span>
                        </div>
                    </div>
                </div>

                <!-- 財務サマリ -->
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-lg border bg-muted/30 px-4 py-2.5">
                        <div class="text-[10px] text-muted-foreground">土地建売価(税抜)</div>
                        <div class="font-mono text-sm font-semibold tabular-nums">{{ yen(s.sellTotal) }}</div>
                    </div>
                    <div class="rounded-lg border bg-muted/30 px-4 py-2.5">
                        <div class="text-[10px] text-muted-foreground">土地建原価(税抜)</div>
                        <div class="font-mono text-sm tabular-nums">{{ yen(s.costTotal) }}</div>
                    </div>
                    <div class="rounded-lg border bg-muted/30 px-4 py-2.5">
                        <div class="text-[10px] text-muted-foreground">粗利額</div>
                        <div class="font-mono text-sm font-semibold tabular-nums text-primary">{{ yen(s.profitTotal) }}</div>
                    </div>
                    <div class="rounded-lg border bg-muted/30 px-4 py-2.5">
                        <div class="text-[10px] text-muted-foreground">粗利率</div>
                        <div class="font-mono text-sm font-semibold tabular-nums">{{ s.profitRate !== null ? s.profitRate + '%' : '—' }}</div>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-[1680px] space-y-5 p-4 md:p-6">
            <!-- 明細キーワード検索 -->
            <div class="flex items-center gap-2">
                <div class="relative w-full max-w-sm">
                    <Search class="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="keyword" placeholder="明細を項目名で絞り込み" class="pl-8" />
                </div>
                <Badge v-if="keyword.trim()" variant="secondary" class="font-normal">{{ visibleSections.reduce((n, s2) => n + s2.units.length, 0) }} 件ヒット</Badge>
            </div>

            <!-- 原価セクション -->
            <Card v-for="section in visibleSections" :key="section.key" class="overflow-hidden gap-0 py-0">
                <CardHeader
                    class="flex-row items-center gap-2 border-b bg-card px-5 py-3 cursor-pointer select-none hover:bg-accent/40 transition-colors"
                    @click="toggle(section.key)"
                >
                    <ChevronDown class="size-4 text-muted-foreground transition-transform" :class="isOpen(section.key) ? '' : '-rotate-90'" />
                    <h2 class="text-sm font-semibold">{{ section.title }}</h2>
                    <Badge variant="secondary" class="font-normal">{{ section.units.length }} 件</Badge>
                </CardHeader>
                <CardContent v-show="isOpen(section.key)" class="p-0">
                    <CostTable :units="section.units" />
                </CardContent>
            </Card>

            <Card v-if="visibleSections.length === 0">
                <CardContent class="py-12 text-center text-muted-foreground">
                    {{ keyword.trim() ? '該当する明細がありません。' : '表示できる原価明細がありません。' }}
                </CardContent>
            </Card>
        </main>
    </AppLayout>
</template>

<script setup lang="ts">
// 実行予算一覧。案件ごとにアコーディオン + 見積明細の横スクロール、検索 + 10件ページネーション。
import { reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { index as budgetRoute } from '@/routes/execution-budgets';
import AppLayout from '@/layouts/AppLayout.vue';
import BudgetTable from '@/components/BudgetTable.vue';
import type { ExecutionBudget, BudgetFilters, LabelMap, Pagination } from '@/types';

import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
    Select, SelectTrigger, SelectContent, SelectItem, SelectValue,
} from '@/components/ui/select';
import {
    Wallet, Search, RotateCcw, SlidersHorizontal, Hash,
    PackageSearch, ChevronLeft, ChevronRight, ChevronDown, ArrowDownUp,
} from 'lucide-vue-next';

const props = defineProps<{
    budgets: ExecutionBudget[];
    pagination: Pagination;
    filters: BudgetFilters;
    sortOptions: LabelMap;
}>();

const form = reactive<BudgetFilters>({ ...props.filters });
const filtersOpen = ref(true);

function buildQuery(extra: Record<string, string | number> = {}): Record<string, string | number> {
    const q: Record<string, string | number> = {};
    if (form.id) q.id = form.id;
    if (form.keyword) q.keyword = form.keyword;
    if (form.monthFrom) q.monthFrom = form.monthFrom;
    if (form.monthTo) q.monthTo = form.monthTo;
    if (form.sort && form.sort !== 'id_desc') q.sort = form.sort;
    return { ...q, ...extra };
}

function submit(): void {
    router.get(budgetRoute.url(), buildQuery(), { preserveState: true, preserveScroll: true, replace: true });
}

function goToPage(page: number): void {
    if (page < 1 || page > props.pagination.lastPage || page === props.pagination.currentPage) return;
    router.get(budgetRoute.url(), buildQuery({ page }), { preserveState: true, preserveScroll: false });
}

function reset(): void {
    form.id = '';
    form.keyword = '';
    form.monthFrom = '';
    form.monthTo = '';
    form.sort = 'id_desc';
    submit();
}
</script>

<template>
    <Head title="実行予算一覧" />
    <AppLayout>
        <!-- ヘッダー -->
        <header class="sticky top-0 z-40 border-b bg-card/80 backdrop-blur supports-[backdrop-filter]:bg-card/60">
            <div class="mx-auto flex max-w-[1680px] items-center gap-3 px-4 py-3 md:px-6">
                <span class="flex size-10 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm">
                    <Wallet class="size-5" />
                </span>
                <div class="min-w-0">
                    <h1 class="truncate text-lg font-bold tracking-tight">実行予算一覧</h1>
                    <p class="truncate text-xs text-muted-foreground">案件ごとの売価・原価・粗利と見積明細</p>
                </div>
                <div class="ml-auto">
                    <Badge variant="secondary" class="gap-1.5 px-2.5 py-1">
                        <PackageSearch class="size-3.5" /> 該当 {{ pagination.total }} 件
                    </Badge>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-[1680px] space-y-5 p-4 md:p-6">
            <!-- 検索（アコーディオン） -->
            <Card>
                <CardContent class="p-4 md:p-5">
                    <button type="button" class="flex w-full items-center gap-2 text-sm font-medium select-none" @click="filtersOpen = !filtersOpen">
                        <SlidersHorizontal class="size-4 text-muted-foreground" /> 絞り込み
                        <ChevronDown class="ml-auto size-4 text-muted-foreground transition-transform" :class="filtersOpen ? '' : '-rotate-90'" />
                    </button>
                    <div v-show="filtersOpen" class="mt-3">
                    <form class="grid grid-cols-1 gap-x-4 gap-y-3 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="submit">
                        <div class="space-y-1.5">
                            <Label>ID（複数可）</Label>
                            <div class="relative">
                                <Hash class="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input v-model="form.id" placeholder="例: 469, 470" class="pl-8" />
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <Label>物件名</Label>
                            <div class="relative">
                                <Search class="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input v-model="form.keyword" placeholder="物件名で検索" class="pl-8" />
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <Label>引渡月</Label>
                            <div class="flex items-center gap-2">
                                <Input v-model="form.monthFrom" type="month" class="w-full" />
                                <span class="text-muted-foreground">〜</span>
                                <Input v-model="form.monthTo" type="month" class="w-full" />
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <Label>並べ替え</Label>
                            <Select v-model="form.sort" @update:model-value="submit">
                                <SelectTrigger>
                                    <ArrowDownUp class="size-4 text-muted-foreground" />
                                    <SelectValue placeholder="新着順" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="(label, k) in sortOptions" :key="k" :value="String(k)">{{ label }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <button type="submit" class="hidden" aria-hidden="true"></button>
                    </form>

                    <Separator class="my-4" />
                    <div class="flex items-center justify-end gap-2">
                        <Button variant="ghost" size="sm" class="gap-1.5" @click="reset">
                            <RotateCcw class="size-4" /> リセット
                        </Button>
                        <Button size="sm" class="gap-1.5" @click="submit">
                            <Search class="size-4" /> 絞り込み表示
                        </Button>
                    </div>
                    </div>
                </CardContent>
            </Card>

            <!-- 実行予算一覧（旧 estimate_list 踏襲・横スクロール + 行アコーディオン） -->
            <BudgetTable v-if="budgets.length" :budgets="budgets" />

            <!-- 空状態 -->
            <Card v-if="budgets.length === 0">
                <CardContent class="flex flex-col items-center justify-center gap-2 py-16 text-center">
                    <PackageSearch class="size-10 text-muted-foreground/50" />
                    <p class="font-medium">該当する実行予算がありません</p>
                    <Button variant="outline" size="sm" class="mt-2 gap-1.5" @click="reset">
                        <RotateCcw class="size-4" /> 条件をリセット
                    </Button>
                </CardContent>
            </Card>

            <!-- ページネーション -->
            <div v-if="pagination.total > 0" class="flex flex-col items-center justify-between gap-3 sm:flex-row">
                <p class="text-sm text-muted-foreground">
                    {{ pagination.total }} 件中 {{ pagination.from ?? 0 }}–{{ pagination.to ?? 0 }} 件を表示
                    （{{ pagination.currentPage }} / {{ pagination.lastPage }} ページ）
                </p>
                <div class="flex items-center gap-1.5">
                    <Button variant="outline" size="sm" class="gap-1" :disabled="pagination.currentPage <= 1" @click="goToPage(pagination.currentPage - 1)">
                        <ChevronLeft class="size-4" /> 前へ
                    </Button>
                    <Button variant="outline" size="sm" class="gap-1" :disabled="pagination.currentPage >= pagination.lastPage" @click="goToPage(pagination.currentPage + 1)">
                        次へ <ChevronRight class="size-4" />
                    </Button>
                </div>
            </div>
        </main>
    </AppLayout>
</template>

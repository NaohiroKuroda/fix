<script setup lang="ts">
// ステータス管理（業者選定承認・材料納品日・完了報告書）
// 既存 felix_total の DB から実データを取得して表示する。検索 + ページネーション対応。
import { reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { index as statusManagementRoute } from '@/routes/status-management';
import AppLayout from '@/layouts/AppLayout.vue';
import EstimateSection from '@/components/EstimateSection.vue';
import type { Estimate, Filters, FilterOptions, Pagination } from '@/types';

import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Separator } from '@/components/ui/separator';
import {
    Select,
    SelectTrigger,
    SelectContent,
    SelectItem,
    SelectValue,
} from '@/components/ui/select';
import {
    ClipboardList, Search, RotateCcw, SlidersHorizontal, Building2,
    PackageSearch, ChevronLeft, ChevronRight, ChevronDown,
} from 'lucide-vue-next';

const props = defineProps<{
    estimates: Estimate[];
    pagination: Pagination;
    filters: Filters;
    options: FilterOptions;
}>();

const ALL = 'all';

// 検索フォーム（サーバから返ってきた filters で初期化）
const form = reactive<Filters>({ ...props.filters });
const filtersOpen = ref(true);

/** フォーム → クエリパラメータ（未指定値は送らない） */
function buildQuery(extra: Record<string, string | number> = {}): Record<string, string | number> {
    const q: Record<string, string | number> = {};
    if (form.keyword) q.keyword = form.keyword;
    if (form.subCate !== ALL) q.subCate = form.subCate;
    if (form.company) q.company = form.company;
    if (form.companySelectStatus !== ALL) q.companySelectStatus = form.companySelectStatus;
    if (form.completeStatus !== ALL) q.completeStatus = form.completeStatus;
    if (form.constructAt) q.constructAt = form.constructAt;
    if (form.completionAt) q.completionAt = form.completionAt;
    if (form.adoptionFlg) q.adoptionFlg = 1;
    if (form.changedFlg) q.changedFlg = 1;
    return { ...q, ...extra };
}

function submit(): void {
    router.get(statusManagementRoute.url(), buildQuery(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function goToPage(page: number): void {
    if (page < 1 || page > props.pagination.lastPage || page === props.pagination.currentPage) return;
    router.get(statusManagementRoute.url(), buildQuery({ page }), {
        preserveState: true,
        preserveScroll: false,
    });
}

function reset(): void {
    form.keyword = '';
    form.subCate = ALL;
    form.company = '';
    form.companySelectStatus = ALL;
    form.completeStatus = ALL;
    form.constructAt = '';
    form.completionAt = '';
    form.adoptionFlg = false;
    form.changedFlg = false;
    submit();
}
</script>

<template>
    <Head title="ステータス管理" />
    <AppLayout>
        <!-- ヘッダー -->
        <header class="sticky top-0 z-40 border-b bg-card/80 backdrop-blur supports-[backdrop-filter]:bg-card/60">
            <div class="mx-auto flex max-w-[1680px] items-center gap-3 px-4 py-3 md:px-6">
                <span class="flex size-10 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm">
                    <ClipboardList class="size-5" />
                </span>
                <div class="min-w-0">
                    <h1 class="truncate text-lg font-bold tracking-tight">ステータス管理</h1>
                    <p class="truncate text-xs text-muted-foreground">業者選定承認・材料納品日・完了報告書</p>
                </div>
                <div class="ml-auto flex items-center gap-2">
                    <Badge variant="secondary" class="gap-1.5 px-2.5 py-1">
                        <PackageSearch class="size-3.5" />
                        該当案件 {{ pagination.total }} 件
                    </Badge>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-[1680px] space-y-5 p-4 md:p-6">
            <!-- フィルタパネル -->
            <Card>
                <CardContent class="p-4 md:p-5">
                    <button type="button" class="flex w-full items-center gap-2 text-sm font-medium select-none" @click="filtersOpen = !filtersOpen">
                        <SlidersHorizontal class="size-4 text-muted-foreground" />
                        絞り込み
                        <ChevronDown class="ml-auto size-4 text-muted-foreground transition-transform" :class="filtersOpen ? '' : '-rotate-90'" />
                    </button>

                    <div v-show="filtersOpen" class="mt-3">
                    <form class="grid grid-cols-1 gap-x-4 gap-y-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4" @submit.prevent="submit">
                        <div class="space-y-1.5">
                            <Label>物件名</Label>
                            <div class="relative">
                                <Search class="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input v-model="form.keyword" placeholder="物件名・IDで検索" class="pl-8" />
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <Label>区分</Label>
                            <Select v-model="form.subCate" @update:model-value="submit">
                                <SelectTrigger><SelectValue placeholder="すべて" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="'all'">すべて</SelectItem>
                                    <SelectItem v-for="(label, k) in options.subCate" :key="k" :value="String(k)">{{ label }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1.5">
                            <Label>業者</Label>
                            <div class="relative">
                                <Building2 class="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input v-model="form.company" placeholder="業者名で検索" class="pl-8" />
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <Label>選定承認ステータス</Label>
                            <Select v-model="form.companySelectStatus" @update:model-value="submit">
                                <SelectTrigger><SelectValue placeholder="すべて" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="'all'">すべて</SelectItem>
                                    <SelectItem v-for="(label, k) in options.companySelectStatus" :key="k" :value="String(k)">{{ label }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1.5">
                            <Label>完了報告（承認待ち）</Label>
                            <Select v-model="form.completeStatus" @update:model-value="submit">
                                <SelectTrigger><SelectValue placeholder="すべて" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="'all'">すべて</SelectItem>
                                    <SelectItem v-for="(label, k) in options.completeStatus" :key="k" :value="String(k)">{{ label }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1.5 sm:col-span-2">
                            <Label>工期</Label>
                            <div class="flex items-center gap-2">
                                <Input v-model="form.constructAt" type="date" class="w-full" />
                                <span class="text-muted-foreground">〜</span>
                                <Input v-model="form.completionAt" type="date" class="w-full" />
                            </div>
                        </div>

                        <div class="flex items-end">
                            <div class="flex flex-wrap items-center gap-4 pb-1.5">
                                <label class="flex cursor-pointer items-center gap-2 text-sm">
                                    <Checkbox v-model="form.adoptionFlg" @update:model-value="submit" /> 採用ありのみ
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 text-sm">
                                    <Checkbox v-model="form.changedFlg" @update:model-value="submit" /> 金額変更あり
                                </label>
                            </div>
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

            <!-- 案件ごとのステータステーブル -->
            <EstimateSection v-for="e in estimates" :key="e.id" :estimate="e" />

            <!-- 空状態 -->
            <Card v-if="estimates.length === 0">
                <CardContent class="flex flex-col items-center justify-center gap-2 py-16 text-center">
                    <PackageSearch class="size-10 text-muted-foreground/50" />
                    <p class="font-medium">該当する案件がありません</p>
                    <p class="text-sm text-muted-foreground">検索条件を変更してお試しください。</p>
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

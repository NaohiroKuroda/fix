<script setup lang="ts">
// 見積管理の共通画面（見積り依頼 / 発注業者選定 …）。
// 5シートともレイアウトはほぼ同一なので、列の出し分けを mode で行い1コンポーネントに集約する。
// felix_total 実行予算の「見積部分」のみを切り出した、申請/承認専用の表示。
// ※ 申請/承認の書き込み（依頼送信・選定）は今後実装。現状はチェック/ボタンは表示のみ。
import { computed, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type {
    EstimateManagementFilters,
    EstimateManagementMode,
    EstimateManagementPagination,
    EstimateManagementProject,
} from '@/types/estimate-management';

const props = defineProps<{
    title: string;
    statusLabel: string;
    mode: EstimateManagementMode;
    actionLabel?: string | null;
    projects: EstimateManagementProject[];
    pagination: EstimateManagementPagination;
    filters: EstimateManagementFilters;
}>();

const form = reactive({ keyword: props.filters.keyword, itemLabel: props.filters.itemLabel });

const isVendorSelection = computed(() => props.mode === 'vendor-selection');

const search = () => {
    router.get(
        window.location.pathname,
        { keyword: form.keyword || undefined, itemLabel: form.itemLabel || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const yen = (value: number | null): string =>
    value === null || value === undefined ? '—' : `¥${value.toLocaleString()}`;
</script>

<template>
    <AppLayout>
        <Head :title="`見積管理 - ${title}`" />

        <div class="space-y-4 p-4 md:p-6">
            <!-- ヘッダー（業務 / 状態） -->
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                <h1 class="text-lg font-bold tracking-tight">見積管理</h1>
                <span class="rounded-md bg-primary/10 px-2 py-0.5 text-sm font-semibold text-primary">業務：{{ title }}</span>
                <span class="text-sm text-muted-foreground">状態：{{ statusLabel }}</span>
            </div>

            <!-- 絞り込み検索 -->
            <form class="flex flex-wrap items-end gap-3 rounded-lg border bg-card p-3" @submit.prevent="search">
                <label class="text-sm">
                    <span class="mb-1 block text-xs text-muted-foreground">物件名</span>
                    <input v-model="form.keyword" type="text" class="h-9 w-48 rounded-md border px-2 text-sm" />
                </label>
                <label class="text-sm">
                    <span class="mb-1 block text-xs text-muted-foreground">項目名</span>
                    <input v-model="form.itemLabel" type="text" class="h-9 w-48 rounded-md border px-2 text-sm" />
                </label>
                <button type="submit" class="h-9 rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground transition-opacity hover:opacity-90">
                    絞り込み検索
                </button>
            </form>

            <!-- アクション（見積依頼送信 等。申請処理は今後実装のため表示のみ） -->
            <div v-if="actionLabel" class="flex justify-end">
                <button
                    type="button"
                    class="h-9 cursor-not-allowed rounded-md border bg-card px-4 text-sm font-semibold text-muted-foreground"
                    disabled
                    title="申請処理は今後実装します"
                >
                    {{ actionLabel }}
                </button>
            </div>

            <!-- 案件カード -->
            <div v-for="project in projects" :key="project.id" class="overflow-hidden rounded-lg border bg-card">
                <div class="border-b bg-muted/40 px-3 py-2 text-sm font-semibold">No.{{ project.no }}　{{ project.name }}</div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/20 text-left text-xs text-muted-foreground">
                            <tr>
                                <th class="px-3 py-2 font-medium">項目</th>
                                <th class="px-3 py-2 font-medium">見積先</th>
                                <th class="px-3 py-2 text-right font-medium">標準単価<br />(税抜)</th>
                                <th class="px-3 py-2 text-right font-medium">予算単価<br />(税抜)</th>
                                <th v-if="isVendorSelection" class="px-3 py-2 text-right font-medium">相見積<br />(税抜)</th>
                                <th v-if="isVendorSelection" class="px-3 py-2 font-medium">発注業者選定</th>
                                <th v-else class="px-3 py-2 font-medium">見積依頼状態</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, i) in project.rows" :key="`${row.unitId}-${row.companyId}-${i}`" class="border-t">
                                <td class="px-3 py-2 font-medium">{{ row.itemLabel }}</td>
                                <td class="px-3 py-2">{{ row.vendorName }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ yen(row.masterPrice) }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ yen(row.budgetPrice) }}</td>
                                <template v-if="isVendorSelection">
                                    <td class="px-3 py-2 text-right tabular-nums">{{ yen(row.quotePrice) }}</td>
                                    <td class="px-3 py-2">
                                        <span :class="row.selected ? 'font-semibold text-primary' : 'text-muted-foreground'">
                                            {{ row.selected ? '｛選定済｝' : '｛未選定｝' }}
                                        </span>
                                    </td>
                                </template>
                                <template v-else>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex items-center gap-1.5" :class="row.requested ? 'font-semibold text-primary' : 'text-muted-foreground'">
                                            <input type="checkbox" :checked="row.requested" disabled class="size-3.5" />
                                            {{ row.requested ? '依頼済' : '未依頼' }}
                                        </span>
                                    </td>
                                </template>
                            </tr>
                            <tr v-if="!project.rows.length">
                                <td colspan="6" class="px-3 py-4 text-center text-muted-foreground">対象データがありません。</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="!projects.length" class="rounded-lg border bg-card p-8 text-center text-muted-foreground">
                対象の案件がありません。
            </div>

            <p class="text-xs text-muted-foreground">
                全 {{ pagination.total }} 件中 {{ pagination.from ?? 0 }}–{{ pagination.to ?? 0 }} 件を表示
            </p>
        </div>
    </AppLayout>
</template>

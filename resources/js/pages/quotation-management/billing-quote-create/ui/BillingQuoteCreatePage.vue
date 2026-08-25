<script setup lang="ts">
// 【請求】見積作成（もらい）。共通コンテナに mode='billing-quote-create' を渡す。**現時点はモック**。
import { AppLayout } from '@/shared/ui/layouts';
import { BillingScreen } from '@/features/billing';
import type { BillingFilters, BillingPagination, BillingProject } from '@/features/billing';

defineOptions({ layout: AppLayout });

defineProps<{
    projects: BillingProject[];
    pagination: BillingPagination;
    filters: BillingFilters;
}>();
</script>

<template>
    <BillingScreen
        mode="billing-quote-create"
        glass
        :projects="projects"
        :pagination="pagination"
        :filters="filters"
    />
</template>

<script setup lang="ts">
// 【請求】見積承認（もらい）。共通コンテナに mode='billing-quote-approval' を渡す。
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
        mode="billing-quote-approval"
        glass
        :projects="projects"
        :pagination="pagination"
        :filters="filters"
    />
</template>

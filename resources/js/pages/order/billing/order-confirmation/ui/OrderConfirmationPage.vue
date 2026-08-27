<script setup lang="ts">
// 【請求】発注書確認（もらい）。共通コンテナに mode='billing-order-confirmation' を渡す。
import { AppLayout } from '@/shared/ui/layouts';
import { BillingScreen } from '@/features/quotation/billing';
import type { BillingFilters, BillingPagination, BillingProject } from '@/features/quotation/billing';

defineOptions({ layout: AppLayout });

defineProps<{
    projects: BillingProject[];
    pagination: BillingPagination;
    filters: BillingFilters;
}>();
</script>

<template>
    <BillingScreen
        mode="billing-order-confirmation"
        glass
        :projects="projects"
        :pagination="pagination"
        :filters="filters"
    />
</template>

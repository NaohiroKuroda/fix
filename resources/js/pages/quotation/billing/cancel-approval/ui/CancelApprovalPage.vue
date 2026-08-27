<script setup lang="ts">
// 【請求】見積取消承認（もらい）。共通コンテナに mode='billing-cancel-approval' を渡す。
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
        mode="billing-cancel-approval"
        glass
        :projects="projects"
        :pagination="pagination"
        :filters="filters"
    />
</template>

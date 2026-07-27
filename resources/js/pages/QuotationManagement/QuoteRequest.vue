<script setup lang="ts">
// 見積依頼（FELIX→業者依頼前）。共通コンポーネントに mode='quote-request' を渡す。
import QuotationManagementScreen from '@/components/quotation-management/QuotationManagementScreen.vue';
import type {
    QuotationManagementFilters,
    QuotationManagementPagination,
    QuotationManagementProject,
} from '@/types/quotation-management';

defineProps<{
    projects: QuotationManagementProject[];
    pagination: QuotationManagementPagination;
    filters: QuotationManagementFilters;
}>();
</script>

<template>
    <QuotationManagementScreen
        title="見積依頼"
        status-label="【FELIX→業者依頼前】"
        mode="quote-request"
        action-label="見積依頼送信"
        glass
        :projects="projects"
        :pagination="pagination"
        :filters="filters"
    />
</template>

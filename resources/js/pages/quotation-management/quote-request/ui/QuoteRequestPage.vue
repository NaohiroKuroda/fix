<script setup lang="ts">
// 見積依頼（FELIX→業者依頼前）。共通コンポーネントに mode='quote-request' を渡す。
import { AppLayout } from '@/shared/ui/layouts';
import { QuotationManagementScreen } from '@/features/quotation-flow';
import type {
    QuotationManagementFilters,
    QuotationManagementPagination,
    QuotationManagementProject,
} from '@/features/quotation-flow';

defineOptions({ layout: AppLayout });

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

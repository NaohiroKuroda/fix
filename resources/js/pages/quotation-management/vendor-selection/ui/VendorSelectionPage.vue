<script setup lang="ts">
// 業者選定（業者→FELIX返答済）。共通コンポーネントに mode='vendor-selection' を渡す。
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
        title="業者選定"
        status-label="【業者→FELIX返答済】"
        mode="vendor-selection"
        action-label="発注業者を確定"
        glass
        :projects="projects"
        :pagination="pagination"
        :filters="filters"
    />
</template>

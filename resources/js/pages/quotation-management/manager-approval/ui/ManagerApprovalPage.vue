<script setup lang="ts">
// 部長承認（FELIX→業者選定済）。共通コンポーネントに mode='manager-approval' を渡す。
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
        title="部長承認"
        status-label="【業者選定済→FELIX(建設部)】"
        mode="manager-approval"
        action-label="部長承認"
        glass
        :projects="projects"
        :pagination="pagination"
        :filters="filters"
    />
</template>

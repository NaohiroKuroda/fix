<script setup lang="ts">
// 部長取消申請（FELIX→業者選定済）。共通コンポーネントに mode='cancel-request' を渡す。
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
        title="部長取消申請"
        status-label="【FELIX(担当者)→FELIX(建設部部長)】"
        mode="cancel-request"
        action-label="取消申請"
        glass
        :projects="projects"
        :pagination="pagination"
        :filters="filters"
    />
</template>

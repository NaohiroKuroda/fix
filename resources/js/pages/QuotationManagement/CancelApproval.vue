<script setup lang="ts">
// 部長取消承認（担当→部長取消申請）。共通コンポーネントに mode='cancel-approval' を渡す。
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
        title="部長取消承認"
        status-label="【FELIX(建設部部長)→FELIX(担当者)】"
        mode="cancel-approval"
        action-label="取消承認"
        glass
        :projects="projects"
        :pagination="pagination"
        :filters="filters"
    />
</template>

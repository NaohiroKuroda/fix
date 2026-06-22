<script setup lang="ts">
// 見積依頼（FELIX→業者依頼前）。共通コンポーネントに mode='quote-request' を渡す。
import EstimateManagementScreen from '@/components/estimate-management/EstimateManagementScreen.vue';
import type {
    EstimateManagementFilters,
    EstimateManagementPagination,
    EstimateManagementProject,
} from '@/types/estimate-management';

defineProps<{
    projects: EstimateManagementProject[];
    pagination: EstimateManagementPagination;
    filters: EstimateManagementFilters;
}>();
</script>

<template>
    <EstimateManagementScreen
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

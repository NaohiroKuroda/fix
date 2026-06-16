<script setup lang="ts">
// 見積り依頼（F→業者依頼前）。共通コンポーネントに mode='quote-request' を渡す。
import EstimateManagementScreen from '@/components/EstimateManagementScreen.vue';
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
        title="見積り依頼"
        status-label="F→業者依頼前"
        mode="quote-request"
        action-label="見積依頼送信"
        :projects="projects"
        :pagination="pagination"
        :filters="filters"
    />
</template>

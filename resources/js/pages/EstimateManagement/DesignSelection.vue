<script setup lang="ts">
// 設計部選定（業者→FELIX返答済）。共通コンポーネントに mode='design-selection' を渡す。
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
        title="設計部承認"
        status-label="【FELIX(建設部)→FELIX(設計部)】"
        mode="design-selection"
        action-label="設計部承認を確定"
        glass
        :projects="projects"
        :pagination="pagination"
        :filters="filters"
    />
</template>

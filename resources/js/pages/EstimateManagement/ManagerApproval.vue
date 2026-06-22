<script setup lang="ts">
// 部長承認（FELIX→業者選定済）。共通コンポーネントに mode='manager-approval' を渡す。
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

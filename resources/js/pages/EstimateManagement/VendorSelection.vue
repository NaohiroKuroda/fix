<script setup lang="ts">
// 発注業者選定（業者→F返答済）。共通コンポーネントに mode='vendor-selection' を渡す。
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
        title="発注業者選定"
        status-label="業者→F返答済"
        mode="vendor-selection"
        :projects="projects"
        :pagination="pagination"
        :filters="filters"
    />
</template>

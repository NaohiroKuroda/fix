<script setup lang="ts">
// 部長承認（FELIX→業者選定済）。共通コンポーネントに mode='manager-approval' を渡す。
import { AppLayout } from '@/shared/ui/layouts';
import { PayableScreen } from '@/features/payable';
import type {
    PayableFilters,
    PayablePagination,
    PayableProject,
} from '@/features/payable';

defineOptions({ layout: AppLayout });

defineProps<{
    projects: PayableProject[];
    pagination: PayablePagination;
    filters: PayableFilters;
}>();
</script>

<template>
    <PayableScreen
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

<script setup lang="ts">
// 業者選定（業者→FELIX返答済）。共通コンポーネントに mode='vendor-selection' を渡す。
import { AppLayout } from '@/shared/ui/layouts';
import { PayableScreen } from '@/features/quotation/payable';
import type {
    PayableFilters,
    PayablePagination,
    PayableProject,
} from '@/features/quotation/payable';

defineOptions({ layout: AppLayout });

defineProps<{
    projects: PayableProject[];
    pagination: PayablePagination;
    filters: PayableFilters;
}>();
</script>

<template>
    <PayableScreen
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

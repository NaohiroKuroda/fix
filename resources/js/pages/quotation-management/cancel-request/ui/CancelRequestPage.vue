<script setup lang="ts">
// 部長取消申請（FELIX→業者選定済）。共通コンポーネントに mode='cancel-request' を渡す。
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

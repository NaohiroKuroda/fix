<script setup lang="ts">
// 部長取消承認（担当→部長取消申請）。共通コンポーネントに mode='cancel-approval' を渡す。
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

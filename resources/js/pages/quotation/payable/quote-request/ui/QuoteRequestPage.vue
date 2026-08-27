<script setup lang="ts">
// 見積依頼（FELIX→業者依頼前）。共通コンポーネントに mode='quote-request' を渡す。
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

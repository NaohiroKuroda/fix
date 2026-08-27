<script setup lang="ts">
// 【請求】見積作成（もらい）。共通コンテナに mode='billing-quote-create' を渡す。
import { AppLayout } from '@/shared/ui/layouts';
import { BillingScreen } from '@/features/billing';
import type { BillingFilters, BillingMasters, BillingPagination, BillingProject } from '@/features/billing';

defineOptions({ layout: AppLayout });

defineProps<{
    projects: BillingProject[];
    pagination: BillingPagination;
    filters: BillingFilters;
    /** 見積作成モーダルの選択肢（拠点 / 部署 / 単位）。 */
    masters: BillingMasters;
}>();
</script>

<template>
    <BillingScreen
        mode="billing-quote-create"
        glass
        :projects="projects"
        :pagination="pagination"
        :filters="filters"
        :masters="masters"
    />
</template>

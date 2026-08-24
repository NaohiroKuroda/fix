<script setup lang="ts">
import { AppLayout } from '@/shared/ui/layouts';
import { OrderDeliveryScreen } from '@/features/order-delivery-flow';
import type { OrderDeliveryFilters, OrderDeliveryPagination, OrderDeliveryProject } from '@/features/order-delivery-flow';

defineOptions({ layout: AppLayout });

defineProps<{
    projects: OrderDeliveryProject[];
    pagination: OrderDeliveryPagination;
    filters: OrderDeliveryFilters;
}>();
</script>

<template>
    <OrderDeliveryScreen
        mode="order-acceptance"
        action-url="/order-delivery/order-acceptance"
        cancel-action-url="/order-delivery/order-acceptance/cancel-request"
        :projects="projects"
        :pagination="pagination"
        :filters="filters"
    />
</template>

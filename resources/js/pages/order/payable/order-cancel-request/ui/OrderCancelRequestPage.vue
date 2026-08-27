<script setup lang="ts">
import { AppLayout } from '@/shared/ui/layouts';
import { OrderDeliveryScreen } from '@/features/order/payable';
import type { OrderDeliveryFilters, OrderDeliveryPagination, OrderDeliveryProject } from '@/features/order/payable';

defineOptions({ layout: AppLayout });

defineProps<{
    projects: OrderDeliveryProject[];
    pagination: OrderDeliveryPagination;
    filters: OrderDeliveryFilters;
}>();
</script>

<template>
    <OrderDeliveryScreen
        mode="order-cancel-request"
        action-url="/order-delivery/order-cancel-request"
        :projects="projects"
        :pagination="pagination"
        :filters="filters"
    />
</template>

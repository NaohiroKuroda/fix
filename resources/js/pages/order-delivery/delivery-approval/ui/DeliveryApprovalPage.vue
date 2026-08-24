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
        mode="delivery-approval"
        action-url="/order-delivery/delivery-approval"
        reject-url="/order-delivery/delivery-approval/reject"
        :projects="projects"
        :pagination="pagination"
        :filters="filters"
    />
</template>

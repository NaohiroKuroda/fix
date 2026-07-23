<script setup lang="ts">
import OrderDeliveryScreen from '@/components/order-delivery/OrderDeliveryScreen.vue';
import type { OrderDeliveryFilters, OrderDeliveryPagination, OrderDeliveryProject } from '@/types/order-delivery';

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

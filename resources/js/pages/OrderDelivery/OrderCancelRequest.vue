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
        mode="order-cancel-request"
        action-url="/order-delivery/order-cancel-request"
        :projects="projects"
        :pagination="pagination"
        :filters="filters"
    />
</template>

<script setup lang="ts">
// 【支払】業者承諾確認。**表示のみ**の画面のため、送信先 URL は渡さない。
// 明細は 項目名 / パートナー名 / 発注金額 / 発注承諾日 / 発注書 の5列
// （→ docs/detailed-design/orders/01_支払_業者承諾確認_詳細設計.md §5）。
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
        mode="order-acceptance"
        :projects="projects"
        :pagination="pagination"
        :filters="filters"
    />
</template>

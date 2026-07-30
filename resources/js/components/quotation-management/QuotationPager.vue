<script setup lang="ts">
// 件数表示 + 前へ/次へ。ページ変更は親へ emit('change', page) で委譲。
import { useQuotationTheme } from '@/composables/useQuotationTheme';
import type { QuotationManagementPagination } from '@/types/quotation-management';

const props = defineProps<{ pagination: QuotationManagementPagination; glass?: boolean }>();
const emit = defineEmits<{ (e: 'change', page: number): void }>();

const { pagerBtnClass, onGlassTextClass } = useQuotationTheme(() => props.glass === true);
</script>

<template>
    <div class="flex items-center gap-3">
        <p class="text-xs" :class="onGlassTextClass">
            全 {{ pagination.total }} 件中 {{ pagination.from ?? 0 }}–{{ pagination.to ?? 0 }} 件を表示
        </p>
        <div v-if="pagination.lastPage > 1" class="flex items-center gap-2">
            <button
                type="button"
                :class="pagerBtnClass"
                :disabled="pagination.currentPage <= 1"
                @click="emit('change', pagination.currentPage - 1)"
            >
                前へ
            </button>
            <span class="text-sm font-bold text-slate-800">{{ pagination.currentPage }} / {{ pagination.lastPage }}</span>
            <button
                type="button"
                :class="pagerBtnClass"
                :disabled="pagination.currentPage >= pagination.lastPage"
                @click="emit('change', pagination.currentPage + 1)"
            >
                次へ
            </button>
        </div>
    </div>
</template>

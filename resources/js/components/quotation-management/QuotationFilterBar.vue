<script setup lang="ts">
// 見積管理の絞り込みフォーム（物件名 / 項目名 / 見積先）。送信は親へ emit('search') で委譲。
import { reactive } from 'vue';
import { useQuotationTheme } from '@/composables/useQuotationTheme';
import type { QuotationManagementFilters } from '@/types/quotation-management';

const props = defineProps<{ filters: QuotationManagementFilters; glass?: boolean }>();
const emit = defineEmits<{ (e: 'search', payload: QuotationManagementFilters): void }>();

const { glassPanelClass, inputClass, primaryBtnClass, onGlassTextClass } = useQuotationTheme(() => props.glass === true);

const form = reactive<QuotationManagementFilters>({
    keyword: props.filters.keyword,
    itemLabel: props.filters.itemLabel,
    vendor: props.filters.vendor,
});

const submit = (): void => emit('search', { keyword: form.keyword, itemLabel: form.itemLabel, vendor: form.vendor });
</script>

<template>
    <form class="flex flex-wrap items-end gap-3 p-3" :class="glassPanelClass" @submit.prevent="submit">
        <label class="text-sm">
            <span class="mb-1 block text-xs" :class="onGlassTextClass">物件名</span>
            <input v-model="form.keyword" type="text" :class="inputClass" />
        </label>
        <label class="text-sm">
            <span class="mb-1 block text-xs" :class="onGlassTextClass">項目名</span>
            <input v-model="form.itemLabel" type="text" :class="inputClass" />
        </label>
        <label class="text-sm">
            <span class="mb-1 block text-xs" :class="onGlassTextClass">見積先</span>
            <input v-model="form.vendor" type="text" :class="inputClass" />
        </label>
        <button type="submit" :class="primaryBtnClass">絞り込み検索</button>
    </form>
</template>

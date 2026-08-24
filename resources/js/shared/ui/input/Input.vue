<script setup>
import { useVModel } from '@vueuse/core';
import { cn } from '@/shared/lib/cn';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    class: { type: null, default: '' },
});
const emits = defineEmits(['update:modelValue']);

const modelValue = useVModel(props, 'modelValue', emits, { passive: true });
</script>

<template>
    <input
        v-model="modelValue"
        :class="
            cn(
                'flex h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground file:border-0 file:bg-transparent file:text-sm file:font-medium focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/40 disabled:cursor-not-allowed disabled:opacity-50',
                props.class
            )
        "
    />
</template>

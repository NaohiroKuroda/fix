<script setup>
import { SelectItem, SelectItemIndicator, SelectItemText } from 'reka-ui';
import { Check } from 'lucide-vue-next';
import { cn } from '@/shared/lib/cn';

const props = defineProps({
    value: { type: null, required: true },
    disabled: { type: Boolean, default: false },
    class: { type: null, default: '' },
});
</script>

<template>
    <SelectItem
        :value="value"
        :disabled="disabled"
        :class="
            cn(
                'relative flex w-full cursor-pointer items-center gap-2 rounded-sm py-1.5 pr-8 pl-2 text-sm outline-none select-none data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
                props.class
            )
        "
    >
        <span class="absolute right-2 flex size-3.5 items-center justify-center">
            <SelectItemIndicator>
                <Check class="size-4" />
            </SelectItemIndicator>
        </span>
        <SelectItemText>
            <slot />
        </SelectItemText>
    </SelectItem>
</template>

<script setup>
import { SelectTrigger, SelectIcon } from 'reka-ui';
import { ChevronDown } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

const props = defineProps({
    class: { type: null, default: '' },
});
</script>

<template>
    <SelectTrigger
        :class="
            cn(
                'flex h-9 w-full items-center justify-between gap-2 rounded-md border border-input bg-transparent px-3 py-2 text-sm whitespace-nowrap shadow-xs transition-[color,box-shadow] outline-none data-[placeholder]:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/40 disabled:cursor-not-allowed disabled:opacity-50 cursor-pointer [&>span]:line-clamp-1',
                props.class
            )
        "
    >
        <slot />
        <SelectIcon as-child>
            <ChevronDown class="size-4 opacity-50" />
        </SelectIcon>
    </SelectTrigger>
</template>

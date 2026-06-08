<script setup>
import { Label } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps({
    for: { type: String, default: undefined },
    class: { type: null, default: '' },
});
</script>

<template>
    <Label
        :for="props.for"
        :class="
            cn(
                'flex items-center gap-1.5 text-xs font-medium text-muted-foreground select-none group-data-[disabled=true]:opacity-50',
                props.class
            )
        "
    >
        <slot />
    </Label>
</template>

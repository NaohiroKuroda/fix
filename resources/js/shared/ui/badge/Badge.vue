<script setup>
import { cn } from '@/shared/lib/cn';
import { badgeVariants } from '.';

const props = defineProps({
    variant: { type: String, default: 'default' },
    class: { type: null, default: '' },
});
</script>

<template>
    <span :class="cn(badgeVariants({ variant }), props.class)">
        <slot />
    </span>
</template>

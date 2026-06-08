<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({ class: { type: null, default: '' } });
</script>

<template>
    <div :class="cn('font-semibold leading-none tracking-tight', props.class)">
        <slot />
    </div>
</template>

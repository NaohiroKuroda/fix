<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({ class: { type: null, default: '' } });
</script>

<template>
    <div :class="cn('bg-card text-card-foreground flex flex-col rounded-xl border shadow-sm', props.class)">
        <slot />
    </div>
</template>

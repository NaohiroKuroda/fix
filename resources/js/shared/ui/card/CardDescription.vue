<script setup>
import { cn } from '@/shared/lib/cn';
const props = defineProps({ class: { type: null, default: '' } });
</script>

<template>
    <div :class="cn('text-sm text-muted-foreground', props.class)">
        <slot />
    </div>
</template>

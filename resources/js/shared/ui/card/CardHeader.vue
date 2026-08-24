<script setup>
import { cn } from '@/shared/lib/cn';
const props = defineProps({ class: { type: null, default: '' } });
</script>

<template>
    <div :class="cn('flex flex-col gap-1 px-5 py-4', props.class)">
        <slot />
    </div>
</template>

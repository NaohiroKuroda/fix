<script setup>
import { cn } from '@/shared/lib/cn';
const props = defineProps({ class: { type: null, default: '' } });
</script>

<template>
    <div :class="cn('px-5 pb-5', props.class)">
        <slot />
    </div>
</template>

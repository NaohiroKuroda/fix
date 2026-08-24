<script setup>
import { CheckboxRoot, CheckboxIndicator, useForwardPropsEmits } from 'reka-ui';
import { Check, Minus } from 'lucide-vue-next';
import { computed } from 'vue';
import { cn } from '@/shared/lib/cn';

const props = defineProps({
    modelValue: { type: [Boolean, String], default: undefined },
    defaultValue: { type: [Boolean, String], default: undefined },
    disabled: { type: Boolean, default: false },
    value: { type: null, default: undefined },
    class: { type: null, default: '' },
});
const emits = defineEmits(['update:modelValue']);

const delegated = computed(() => {
    const { class: _, ...rest } = props;
    return rest;
});
const forwarded = useForwardPropsEmits(delegated, emits);
</script>

<template>
    <CheckboxRoot
        v-bind="forwarded"
        :class="
            cn(
                'peer size-4 shrink-0 rounded-[4px] border border-input shadow-xs transition-shadow outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/40 disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:bg-primary data-[state=checked]:border-primary data-[state=checked]:text-primary-foreground data-[state=indeterminate]:bg-primary data-[state=indeterminate]:text-primary-foreground cursor-pointer',
                props.class
            )
        "
    >
        <CheckboxIndicator class="flex items-center justify-center text-current">
            <Minus v-if="forwarded.modelValue === 'indeterminate'" class="size-3.5" />
            <Check v-else class="size-3.5" />
        </CheckboxIndicator>
    </CheckboxRoot>
</template>

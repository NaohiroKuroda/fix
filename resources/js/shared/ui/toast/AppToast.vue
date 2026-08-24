<script setup lang="ts">
// 汎用トースト（成功 / エラー）。表示専用。表示制御や自動消去は親（FlashMessages）が担う。
// variant でアイコン・配色を出し分ける。スタイルは Tailwind のみ（インラインスタイル禁止）。
import { computed } from 'vue';
import { CheckCircle2, AlertCircle, X } from 'lucide-vue-next';
import { cn } from '@/shared/lib/cn';

type ToastVariant = 'success' | 'error';

const props = defineProps<{
    variant: ToastVariant;
    message: string;
}>();

defineEmits<{ (e: 'dismiss'): void }>();

const isSuccess = computed(() => props.variant === 'success');
const icon = computed(() => (isSuccess.value ? CheckCircle2 : AlertCircle));

// 角の金アクセントは FELIX 配色に合わせ、成否は緑/赤で明確に区別する。
const panelClass = computed(() =>
    isSuccess.value
        ? 'border-emerald-200 bg-emerald-50 text-emerald-900'
        : 'border-red-200 bg-red-50 text-red-900',
);
const iconClass = computed(() => (isSuccess.value ? 'text-emerald-600' : 'text-red-600'));
const accentClass = computed(() => (isSuccess.value ? 'bg-emerald-500' : 'bg-red-500'));
const title = computed(() => (isSuccess.value ? '成功' : 'エラー'));
</script>

<template>
    <div
        role="alert"
        aria-live="assertive"
        :class="cn(
            'pointer-events-auto relative flex w-full max-w-sm items-start gap-3 overflow-hidden rounded-xl border py-3 pl-4 pr-9 shadow-lg ring-1 ring-black/5 backdrop-blur-sm',
            panelClass,
        )"
    >
        <span aria-hidden="true" :class="cn('absolute inset-y-0 left-0 w-1', accentClass)"></span>
        <component :is="icon" :class="cn('mt-0.5 size-5 shrink-0', iconClass)" />
        <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold opacity-70">{{ title }}</p>
            <p class="break-words text-sm font-medium leading-snug">{{ message }}</p>
        </div>
        <button
            type="button"
            class="absolute right-2 top-2 flex size-6 items-center justify-center rounded-md text-black/40 transition hover:bg-black/5 hover:text-black/70"
            title="閉じる"
            @click="$emit('dismiss')"
        >
            <X class="size-4" />
        </button>
    </div>
</template>

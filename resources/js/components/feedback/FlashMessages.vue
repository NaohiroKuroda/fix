<script setup lang="ts">
// フラッシュメッセージ表示器。Inertia 共有プロパティ flash（success / error）を購読し、
// 画面右上にトーストを積み上げて一定時間で自動消去する。AppLayout に常設して全画面で共用する。
import { onBeforeUnmount, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppToast from '@/components/feedback/AppToast.vue';

interface ToastItem {
    id: number;
    variant: 'success' | 'error';
    message: string;
}

const AUTO_DISMISS_MS = 5000;

const page = usePage();
const toasts = ref<ToastItem[]>([]);
const timers = new Map<number, ReturnType<typeof setTimeout>>();
let seq = 0;

const dismiss = (id: number): void => {
    const timer = timers.get(id);
    if (timer) {
        clearTimeout(timer);
        timers.delete(id);
    }
    toasts.value = toasts.value.filter((toast) => toast.id !== id);
};

const push = (variant: ToastItem['variant'], message: string): void => {
    const id = ++seq;
    toasts.value = [...toasts.value, { id, variant, message }];
    timers.set(id, setTimeout(() => dismiss(id), AUTO_DISMISS_MS));
};

// flash はリダイレクト直後の1回だけ値が入る。immediate で初回読込時の値も拾う。
watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) {
            return;
        }
        if (flash.success) {
            push('success', flash.success);
        }
        if (flash.error) {
            push('error', flash.error);
        }
    },
    { immediate: true, deep: true },
);

onBeforeUnmount(() => {
    timers.forEach((timer) => clearTimeout(timer));
    timers.clear();
});
</script>

<template>
    <Teleport to="body">
        <div
            class="pointer-events-none fixed inset-x-0 top-4 z-[100] flex flex-col items-center gap-2 px-4 sm:inset-x-auto sm:right-4 sm:items-end"
        >
            <TransitionGroup
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="-translate-y-2 opacity-0 sm:translate-x-4 sm:translate-y-0"
                enter-to-class="translate-x-0 translate-y-0 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="translate-x-0 opacity-100"
                leave-to-class="translate-x-4 opacity-0"
            >
                <AppToast
                    v-for="toast in toasts"
                    :key="toast.id"
                    :variant="toast.variant"
                    :message="toast.message"
                    @dismiss="dismiss(toast.id)"
                />
            </TransitionGroup>
        </div>
    </Teleport>
</template>

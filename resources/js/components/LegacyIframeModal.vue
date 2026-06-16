<script setup lang="ts">
// felix_total（laravel-admin）の /admin 画面を iframe で開くモーダル。
// - src が非 null の間だけ表示。
// - felix_total 側からの postMessage（保存/閉じ通知）を受けて閉じる（cross-origin 対応）。
//   ※ felix_total 側の postMessage 実装は cross-auth-cookie-plan.md §5 を参照。
import { computed, onBeforeUnmount, onMounted } from 'vue';
import { X } from 'lucide-vue-next';

const props = defineProps<{ src: string | null }>();
const emit = defineEmits<{ (e: 'close'): void; (e: 'saved'): void }>();

const open = computed(() => props.src !== null);

// 受信を許可する felix_total のオリジン（src から導出）。
const expectedOrigin = computed(() => {
    if (!props.src) return null;
    try {
        return new URL(props.src).origin;
    } catch {
        return null;
    }
});

const onMessage = (event: MessageEvent) => {
    if (!expectedOrigin.value || event.origin !== expectedOrigin.value) return;
    const data = event.data as { type?: string } | null;
    if (!data || typeof data !== 'object') return;

    if (data.type === 'felix-iframe:saved') {
        emit('saved');
        emit('close');
    } else if (data.type === 'felix-iframe:close') {
        emit('close');
    }
};

const onKey = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && open.value) emit('close');
};

onMounted(() => {
    window.addEventListener('message', onMessage);
    window.addEventListener('keydown', onKey);
});
onBeforeUnmount(() => {
    window.removeEventListener('message', onMessage);
    window.removeEventListener('keydown', onKey);
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
            @click.self="emit('close')"
        >
            <div class="flex h-[90vh] w-[95vw] max-w-6xl flex-col overflow-hidden rounded-lg bg-card shadow-xl">
                <div class="flex items-center justify-between border-b px-4 py-2.5">
                    <span class="text-sm font-semibold">felix_total 編集画面</span>
                    <button
                        type="button"
                        class="flex size-8 items-center justify-center rounded-lg text-muted-foreground hover:bg-accent hover:text-foreground transition-colors"
                        title="閉じる"
                        @click="emit('close')"
                    >
                        <X class="size-5" />
                    </button>
                </div>
                <iframe
                    v-if="src"
                    :src="src"
                    class="h-full w-full flex-1 border-0"
                    title="felix_total 編集画面"
                />
            </div>
        </div>
    </Teleport>
</template>

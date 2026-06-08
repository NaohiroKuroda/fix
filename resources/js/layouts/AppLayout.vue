<script setup lang="ts">
// アプリ共通レイアウト（felix_total 風サイドメニュー + コンテンツ）。
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { index as budgetRoute } from '@/routes/execution-budgets';
import { index as statusRoute } from '@/routes/status-management';
import { Wallet, ClipboardList, Menu, Building2 } from 'lucide-vue-next';

interface NavItem {
    label: string;
    href: string;
    icon: unknown;
    active: boolean;
}

const page = usePage();
const path = computed(() => page.url.split('?')[0]);

const nav = computed<NavItem[]>(() => [
    { label: '実行予算一覧', href: budgetRoute.url(), icon: Wallet, active: path.value === '/' || path.value.startsWith('/execution-budgets') },
    { label: 'ステータス管理', href: statusRoute.url(), icon: ClipboardList, active: path.value.startsWith('/status-management') },
]);

const mobileOpen = ref(false);
</script>

<template>
    <div class="min-h-screen bg-muted/30">
        <!-- サイドバー -->
        <aside
            class="fixed inset-y-0 left-0 z-50 w-60 flex flex-col bg-primary text-primary-foreground transition-transform md:translate-x-0"
            :class="mobileOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex items-center gap-2.5 px-5 h-16 border-b border-white/10">
                <span class="flex size-9 items-center justify-center rounded-lg bg-white/15">
                    <Building2 class="size-5" />
                </span>
                <div class="leading-tight">
                    <div class="text-sm font-bold tracking-wide">FELIX</div>
                    <div class="text-[10px] text-white/60">業務管理システム</div>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                <p class="px-3 pb-1 text-[10px] font-semibold uppercase tracking-wider text-white/40">メニュー</p>
                <Link
                    v-for="item in nav"
                    :key="item.href"
                    :href="item.href"
                    class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition-colors"
                    :class="item.active ? 'bg-white/15 font-semibold' : 'text-white/80 hover:bg-white/10'"
                >
                    <component :is="item.icon" class="size-4.5 shrink-0" />
                    {{ item.label }}
                </Link>
            </nav>

            <div class="border-t border-white/10 px-5 py-3 text-[10px] text-white/40">
                new_felix_total
            </div>
        </aside>

        <!-- モバイル用オーバーレイ -->
        <div v-if="mobileOpen" class="fixed inset-0 z-40 bg-black/40 md:hidden" @click="mobileOpen = false" />

        <!-- コンテンツ -->
        <div class="md:pl-60">
            <!-- モバイルヘッダー -->
            <div class="sticky top-0 z-30 flex h-14 items-center gap-3 border-b bg-card px-4 md:hidden">
                <button class="flex size-9 items-center justify-center rounded-lg hover:bg-accent" @click="mobileOpen = true">
                    <Menu class="size-5" />
                </button>
                <span class="font-semibold">FELIX</span>
            </div>

            <slot />
        </div>
    </div>
</template>

<script setup lang="ts">
// アプリ共通レイアウト（felix_total 風サイドメニュー + ヘッダー + コンテンツ）。
// - サイドバーは開閉可能（デスクトップ: collapsed / モバイル: オーバーレイ）
// - ヘッダー右端にユーザー名を表示（felix_total 踏襲）
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { index as budgetRoute } from '@/routes/execution-budgets';
import { index as statusRoute } from '@/routes/status-management';
import { Wallet, ClipboardList, Menu, Building2, PanelLeftClose, PanelLeftOpen, User, LogOut } from 'lucide-vue-next';

interface NavItem {
    label: string;
    href: string;
    icon: unknown;
    active: boolean;
}

const page = usePage();
const path = computed(() => page.url.split('?')[0]);
const userName = computed(() => page.props.auth?.user?.name ?? 'ゲスト');

const nav = computed<NavItem[]>(() => [
    { label: '実行予算一覧', href: budgetRoute.url(), icon: Wallet, active: path.value === '/' || path.value.startsWith('/execution-budgets') },
    { label: 'ステータス管理', href: statusRoute.url(), icon: ClipboardList, active: path.value.startsWith('/status-management') },
]);

const mobileOpen = ref(false);  // モバイル: オーバーレイ表示
const collapsed = ref(false);   // デスクトップ: 折りたたみ

// ログアウト（AuthController@logout へ POST）
const logout = () => router.post('/logout');
</script>

<template>
    <div class="min-h-screen bg-muted/30">
        <!-- サイドバー -->
        <aside
            class="fixed inset-y-0 left-0 z-50 w-60 flex flex-col bg-primary text-primary-foreground transition-transform"
            :class="[
                mobileOpen ? 'translate-x-0' : '-translate-x-full',
                collapsed ? 'md:-translate-x-full' : 'md:translate-x-0',
            ]"
        >
            <div class="flex items-center gap-2.5 px-4 h-14 border-b border-white/10">
                <span class="flex size-9 items-center justify-center rounded-lg bg-white/15 shrink-0">
                    <Building2 class="size-5" />
                </span>
                <div class="leading-tight min-w-0 flex-1">
                    <div class="text-sm font-bold tracking-wide">FELIX</div>
                    <div class="text-[10px] text-white/60">業務管理システム</div>
                </div>
                <button
                    class="flex size-8 items-center justify-center rounded-lg text-white/70 hover:bg-white/10 hover:text-white transition-colors"
                    title="メニューを閉じる"
                    @click="collapsed = true; mobileOpen = false"
                >
                    <PanelLeftClose class="size-5" />
                </button>
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

        <!-- デスクトップ: 折りたたみ時の再オープンボタン（ヘッダー外・閉じた時のみ） -->
        <button
            v-show="collapsed"
            class="fixed left-2 top-2.5 z-50 hidden size-9 items-center justify-center rounded-lg border bg-card text-foreground shadow-sm hover:bg-accent md:flex"
            title="メニューを開く"
            @click="collapsed = false"
        >
            <PanelLeftOpen class="size-5" />
        </button>

        <!-- コンテンツ -->
        <div class="transition-[padding]" :class="collapsed ? 'md:pl-0' : 'md:pl-60'">
            <!-- グローバルヘッダー（左: モバイルのみメニュー / 右: ユーザー名） -->
            <header class="sticky top-0 z-40 flex h-14 items-center gap-3 border-b bg-card px-3 md:px-5">
                <!-- モバイルのみ: オーバーレイメニューを開く -->
                <button
                    class="flex size-9 items-center justify-center rounded-lg text-muted-foreground hover:bg-accent md:hidden"
                    title="メニュー"
                    @click="mobileOpen = true"
                >
                    <Menu class="size-5" />
                </button>
                <span class="font-semibold tracking-tight md:hidden">FELIX</span>

                <!-- 右端: ユーザー名 + ログアウト -->
                <div class="ml-auto flex items-center gap-2.5">
                    <span class="hidden text-sm text-foreground sm:inline">{{ userName }}</span>
                    <span class="flex size-8 items-center justify-center rounded-full bg-primary/10 text-primary">
                        <User class="size-4.5" />
                    </span>
                    <button
                        class="flex size-8 items-center justify-center rounded-lg text-muted-foreground hover:bg-accent hover:text-foreground transition-colors"
                        title="ログアウト"
                        @click="logout"
                    >
                        <LogOut class="size-4.5" />
                    </button>
                </div>
            </header>

            <slot />
        </div>
    </div>
</template>

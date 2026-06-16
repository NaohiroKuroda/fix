<script setup lang="ts">
// アプリ共通レイアウト（felix_total 風サイドメニュー + ヘッダー + コンテンツ）。
// - サイドバーは開閉可能（デスクトップ: collapsed / モバイル: オーバーレイ）
// - ヘッダー右端にユーザー名を表示（felix_total 踏襲）
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ClipboardList, ChevronDown, Menu, Building2, PanelLeftClose, PanelLeftOpen, User, LogOut } from 'lucide-vue-next';

interface NavChild {
    label: string;
    href: string;
    active: boolean;
    /** 未対応件数バッジ（LINE の未読数風）。0/未指定なら非表示。※現状はダミー値。 */
    badge?: number;
}

const page = usePage();
const path = computed(() => page.url.split('?')[0]);
const userName = computed(() => page.props.auth?.user?.name ?? 'ゲスト');

// 見積管理（トグル）。配下の画面はシート名の()内をメニュー名にする。
// ※ badge は未対応件数の表示用（現状はダミー値。今後 Inertia 共有プロパティで実件数を配る）。
const estimateChildren = computed<NavChild[]>(() => [
    { label: 'FELIX→業者依頼前', href: '/estimate-management/quote-request', active: path.value.startsWith('/estimate-management/quote-request'), badge: 5 },
    { label: '業者→FELIX返答済', href: '/estimate-management/vendor-selection', active: path.value.startsWith('/estimate-management/vendor-selection'), badge: 7 },
]);
const estimateMenuOpen = ref(true);

// 親「見積管理」に出す合計バッジ。
const estimateBadgeTotal = computed(() => estimateChildren.value.reduce((sum, c) => sum + (c.badge ?? 0), 0));

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
                    <div class="text-[10px] text-white/60">業務管理システム-FIX-</div>
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
                <!-- 見積管理（トグル）。クリックで配下の画面リンクを開閉する。 -->
                <button
                    type="button"
                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-white/80 transition-colors hover:bg-white/10"
                    @click="estimateMenuOpen = !estimateMenuOpen"
                >
                    <ClipboardList class="size-4.5 shrink-0" />
                    <span class="flex-1 text-left">見積管理</span>
                    <span
                        v-if="estimateBadgeTotal"
                        class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-bold text-white tabular-nums"
                    >
                        <span class="block translate-y-[0.5px] leading-none">{{ estimateBadgeTotal > 99 ? '99+' : estimateBadgeTotal }}</span>
                    </span>
                    <ChevronDown class="size-4 shrink-0 transition-transform" :class="estimateMenuOpen ? '' : '-rotate-90'" />
                </button>
                <div v-show="estimateMenuOpen" class="mt-1 space-y-1 pl-4">
                    <Link
                        v-for="child in estimateChildren"
                        :key="child.href"
                        :href="child.href"
                        class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition-colors"
                        :class="child.active ? 'bg-white/15 font-semibold' : 'text-white/80 hover:bg-white/10'"
                    >
                        <span class="flex-1">{{ child.label }}</span>
                        <span
                            v-if="child.badge"
                            class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-bold text-white tabular-nums"
                        >
                            <span class="block translate-y-[0.5px] leading-none">{{ child.badge > 99 ? '99+' : child.badge }}</span>
                        </span>
                    </Link>
                    <p v-if="!estimateChildren.length" class="px-3 py-2 text-xs text-white/40">準備中</p>
                </div>
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

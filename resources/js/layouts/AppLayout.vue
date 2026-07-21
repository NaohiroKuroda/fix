<script setup lang="ts">
// アプリ共通レイアウト（サイドメニュー + コンテンツ）。
// - グローバルヘッダーは撤去。アカウント名 + ログアウトはサイドバー上部（ロゴ直下）に配置。
// - サイドバーは開閉可能（デスクトップ: collapsed / モバイル: オーバーレイ）
import { computed, provide, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ClipboardList, ChevronDown, Menu, PanelLeftClose, PanelLeftOpen, PackageCheck, Receipt, User, LogOut } from 'lucide-vue-next';
import FlashMessages from '@/components/feedback/FlashMessages.vue';

interface NavChild {
    label: string;
    /** 未実装（プレースホルダ）の場合は href を持たない。 */
    href?: string;
    active?: boolean;
    /** 未対応件数バッジ（LINE の未読数風）。0/未指定なら非表示。※現状はダミー値。 */
    badge?: number;
    /** 2つ目のバッジ（赤以外）。例：業者選定の「差し戻し」件数。0/未指定なら非表示。 */
    badge2?: number;
}

const page = usePage();
const path = computed(() => page.url.split('?')[0]);
const userName = computed(() => page.props.auth?.user?.name ?? 'ゲスト');
// 建設部部長のみ「部長承認」「部長取消承認」を表示する（判定はサーバ側 config/felix.php）。
const isEstimateManager = computed(() => page.props.auth?.user?.isEstimateManager ?? false);

// サイドメニューの未処理件数バッヂ（Inertia 共有プロパティ menuBadges）。
const badges = computed(() => page.props.menuBadges ?? null);

// 見積管理（トグル）。配下はシート名（業務（状態））をそのままメニュー名にする。
// href 付き＝実装済み（リンク）。href 無し＝未実装（プレースホルダ「準備中」）。
// badge は各画面の未処理件数（部長取消申請はバッヂ対象外）。
const estimateChildren = computed<NavChild[]>(() => [
    { label: '見積依頼【FELIX→業者依頼前】', href: '/estimate-management/quote-request', active: path.value.startsWith('/estimate-management/quote-request'), badge: badges.value?.['quote-request'] },
    { label: '業者選定【業者→FELIX返答済】', href: '/estimate-management/vendor-selection', active: path.value.startsWith('/estimate-management/vendor-selection'), badge: badges.value?.['vendor-selection'], badge2: badges.value?.['vendor-selection-rejected'] },
    // 部長承認・部長取消承認は建設部部長のみ表示。
    ...(isEstimateManager.value
        ? [{ label: '部長承認【業者選定済→FELIX(建設部)】', href: '/estimate-management/manager-approval', active: path.value.startsWith('/estimate-management/manager-approval'), badge: badges.value?.['manager-approval'] }]
        : []),
    { label: '部長取消申請【FELIX(担当者)→FELIX(建設部部長)】', href: '/estimate-management/cancel-request', active: path.value.startsWith('/estimate-management/cancel-request') },
    ...(isEstimateManager.value
        ? [{ label: '部長取消承認【FELIX(建設部部長)→FELIX(担当者)】', href: '/estimate-management/cancel-approval', active: path.value.startsWith('/estimate-management/cancel-approval'), badge: badges.value?.['cancel-approval'] }]
        : []),
]);
const estimateMenuOpen = ref(true);

// 親「見積管理」に出す合計バッジ。
const estimateBadgeTotal = computed(() => estimateChildren.value.reduce((sum, c) => sum + (c.badge ?? 0), 0));

// 発注管理（トグル）。業者承諾確認・発注取消承認の2画面のみ（取消申請は業者承諾確認画面内のボタンで行うため独立画面はメニューに出さない）。
// 発注取消承認は見積管理の部長取消承認と同じ方針で建設部部長のみ表示。
const orderChildren = computed<NavChild[]>(() => [
    { label: '業者承諾確認【発注承認済み→承諾待ち】', href: '/order-delivery/order-acceptance', active: path.value.startsWith('/order-delivery/order-acceptance'), badge: badges.value?.['order-acceptance'] },
    ...(isEstimateManager.value
        ? [{ label: '発注取消承認【取消申請中→部長取消承認待ち】', href: '/order-delivery/order-cancel-approval', active: path.value.startsWith('/order-delivery/order-cancel-approval'), badge: badges.value?.['order-cancel-approval'] }]
        : []),
]);
// 完了・納品管理（トグル）。完了確認（提出日・確認日・請求日）と部長完了承認。
const deliveryChildren = computed<NavChild[]>(() => [
    { label: '完了確認【業者承諾済み→提出・確認・請求】', href: '/order-delivery/delivery-report', active: path.value.startsWith('/order-delivery/delivery-report'), badge: badges.value?.['delivery-report-submission'] },
    // 部長完了承認は建設部部長のみ表示。
    ...(isEstimateManager.value
        ? [{ label: '部長完了承認【報告書受領済み→部長承認待ち】', href: '/order-delivery/delivery-approval', active: path.value.startsWith('/order-delivery/delivery-approval'), badge: badges.value?.['delivery-approval'] }]
        : []),
]);

// 請求管理（トグル）。請求取消承認は建設部部長のみ表示のため、メニュー自体を部長限定にする。
const billingChildren = computed<NavChild[]>(() => [
    { label: '請求取消承認【請求書作成済み→取消確認】', href: '/order-delivery/invoice-approval', active: path.value.startsWith('/order-delivery/invoice-approval') },
]);
const showBillingMenu = computed(() => isEstimateManager.value);

// 現在地の判定（発注管理＝業者承諾確認・発注取消承認、完了・納品管理＝納品系、請求管理＝請求系）。
const isOrderPath = (p: string): boolean => p.startsWith('/order-delivery/order-acceptance') || p.startsWith('/order-delivery/order-cancel-approval');
const isDeliveryPath = (p: string): boolean => p.startsWith('/order-delivery/delivery-');
const isBillingPath = (p: string): boolean => p.startsWith('/order-delivery/invoice-');

// 該当画面にいる間はメニューを開いたままにする（初期値も現在地で判定）。
// 発注／納品／請求メニューを開いたときは他のメニューを閉じる（アコーディオンは排他）。
const orderMenuOpen = ref(isOrderPath(path.value));
const deliveryMenuOpen = ref(isDeliveryPath(path.value));
const billingMenuOpen = ref(isBillingPath(path.value));
if (orderMenuOpen.value || deliveryMenuOpen.value || billingMenuOpen.value) {
    estimateMenuOpen.value = false;
}
watch(() => path.value, (p) => {
    if (isOrderPath(p)) {
        orderMenuOpen.value = true;
        deliveryMenuOpen.value = false;
        billingMenuOpen.value = false;
        estimateMenuOpen.value = false;
    } else if (isDeliveryPath(p)) {
        deliveryMenuOpen.value = true;
        orderMenuOpen.value = false;
        billingMenuOpen.value = false;
        estimateMenuOpen.value = false;
    } else if (isBillingPath(p)) {
        billingMenuOpen.value = true;
        orderMenuOpen.value = false;
        deliveryMenuOpen.value = false;
        estimateMenuOpen.value = false;
    }
});
// 発注管理メニューのトグル。開くときは他のメニューを閉じる。
const toggleOrderMenu = (): void => {
    orderMenuOpen.value = !orderMenuOpen.value;
    if (orderMenuOpen.value) {
        estimateMenuOpen.value = false;
        deliveryMenuOpen.value = false;
        billingMenuOpen.value = false;
    }
};
// 完了・納品管理メニューのトグル。開くときは他のメニューを閉じる。
const toggleDeliveryMenu = (): void => {
    deliveryMenuOpen.value = !deliveryMenuOpen.value;
    if (deliveryMenuOpen.value) {
        estimateMenuOpen.value = false;
        orderMenuOpen.value = false;
        billingMenuOpen.value = false;
    }
};
// 請求管理メニューのトグル。開くときは他のメニューを閉じる。
const toggleBillingMenu = (): void => {
    billingMenuOpen.value = !billingMenuOpen.value;
    if (billingMenuOpen.value) {
        estimateMenuOpen.value = false;
        orderMenuOpen.value = false;
        deliveryMenuOpen.value = false;
    }
};
const orderBadgeTotal = computed(() => orderChildren.value.reduce((sum, c) => sum + (c.badge ?? 0), 0));
const deliveryBadgeTotal = computed(() => deliveryChildren.value.reduce((sum, c) => sum + (c.badge ?? 0), 0));
const billingBadgeTotal = computed(() => billingChildren.value.reduce((sum, c) => sum + (c.badge ?? 0), 0));

// ラベルを最初の「【」の前で2行に分割する（業務名 / 【状態】）。
const splitLabel = (label: string): { head: string; tail: string } => {
    const i = label.search(/【/);
    return i < 0 ? { head: label, tail: '' } : { head: label.slice(0, i), tail: label.slice(i) };
};

const mobileOpen = ref(false);  // モバイル: オーバーレイ表示
// felix_total から iframe 埋め込みされた場合は、サイドバーを畳んだ状態で開く
// （単独タブ表示時は従来どおり開いた状態）。
const isEmbedded = typeof window !== 'undefined' && window.self !== window.top;
const collapsed = ref(isEmbedded);   // デスクトップ: 折りたたみ

// 折りたたみ状態を子（各画面ヘッダー）へ共有。
// 閉じた時は左上に再オープンボタンが浮くため、画面側でタイトルの左余白を空ける用途。
provide('sidebarCollapsed', collapsed);

// ログアウト（AuthController@logout へ POST）
const logout = () => router.post('/logout');
</script>

<template>
    <div class="min-h-screen bg-muted/30">
        <!-- フラッシュメッセージ（成功 / エラー）。全画面共通で右上に表示 -->
        <FlashMessages />

        <!-- サイドバー -->
        <aside
            class="fixed inset-y-0 left-0 z-50 w-72 flex flex-col bg-gradient-to-b from-zinc-900 to-black text-primary-foreground transition-transform"
            :class="[
                mobileOpen ? 'translate-x-0' : '-translate-x-full',
                collapsed ? 'md:-translate-x-full' : 'md:translate-x-0',
            ]"
        >
            <div class="flex items-center gap-2.5 px-4 h-14 border-b border-white/10">
                <div class="flex min-w-0 flex-1 items-center">
                    <img src="/images/header_rogo_pc.svg" alt="FELIX" class="h-8 w-auto" />
                </div>
                <button
                    class="flex size-8 items-center justify-center rounded-lg text-white/70 hover:bg-white/10 hover:text-white transition-colors"
                    title="メニューを閉じる"
                    @click="collapsed = true; mobileOpen = false"
                >
                    <PanelLeftClose class="size-5" />
                </button>
            </div>

            <!-- アカウント（ロゴ直下）：ユーザー名 + ログアウト -->
            <div class="flex items-center gap-2.5 border-b border-white/10 px-4 py-3">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-white/10 text-white">
                    <User class="size-4.5" />
                </span>
                <span class="min-w-0 flex-1 truncate text-sm text-white/90">{{ userName }}</span>
                <button
                    class="flex size-8 items-center justify-center rounded-lg text-white/70 transition-colors hover:bg-white/10 hover:text-white"
                    title="ログアウト"
                    @click="logout"
                >
                    <LogOut class="size-4.5" />
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                <p class="px-3 pb-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-[#c4a35b]/80">メニュー</p>
                <!-- 見積管理（トグル）。クリックで配下の画面リンクを開閉する。 -->
                <button
                    type="button"
                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-base font-medium text-white/80 transition-colors hover:bg-white/10"
                    @click="estimateMenuOpen = !estimateMenuOpen"
                >
                    <ClipboardList class="size-4.5 shrink-0" />
                    <span class="flex-1 text-left">見積管理</span>
                    <span
                        v-if="estimateBadgeTotal"
                        class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-green-600 px-1.5 text-[10px] font-bold text-white tabular-nums"
                    >
                        <span class="block translate-y-[0.5px] leading-none">{{ estimateBadgeTotal > 99 ? '99+' : estimateBadgeTotal }}</span>
                    </span>
                    <ChevronDown class="size-4 shrink-0 transition-transform" :class="estimateMenuOpen ? '' : '-rotate-90'" />
                </button>
                <div v-show="estimateMenuOpen" class="mt-1 space-y-1 pl-4">
                    <template v-for="child in estimateChildren" :key="child.label">
                        <!-- 実装済み：リンク -->
                        <Link
                            v-if="child.href"
                            :href="child.href"
                            class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] leading-snug transition"
                            :class="child.active
                                ? 'bg-white/12 font-semibold text-white shadow-[inset_3px_0_0_#c4a35b,inset_0_1px_0_rgba(255,255,255,0.2)] backdrop-blur-sm'
                                : 'text-white/75 hover:bg-white/10'"
                        >
                            <span class="flex-1">
                                <span class="block">{{ splitLabel(child.label).head }}</span>
                                <span v-if="splitLabel(child.label).tail" class="block text-[11px] leading-tight opacity-70">{{ splitLabel(child.label).tail }}</span>
                            </span>
                            <span
                                v-if="child.badge"
                                class="inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-green-600 px-1.5 text-[10px] font-bold text-white tabular-nums"
                            >
                                <span class="block translate-y-[0.5px] leading-none">{{ child.badge > 99 ? '99+' : child.badge }}</span>
                            </span>
                            <!-- 2つ目のバッジ（赤以外）：業者選定の差し戻し件数。現状の赤バッジの右隣。 -->
                            <span
                                v-if="child.badge2"
                                class="inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-bold text-white tabular-nums"
                                title="部長承認で否認され業者選定へ差し戻された件数"
                            >
                                <span class="block translate-y-[0.5px] leading-none">{{ child.badge2 > 99 ? '99+' : child.badge2 }}</span>
                            </span>
                        </Link>
                        <!-- 未実装：準備中プレースホルダ -->
                        <div
                            v-else
                            class="flex cursor-not-allowed items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] leading-snug text-white/35"
                            title="準備中"
                        >
                            <span class="flex-1">
                                <span class="block">{{ splitLabel(child.label).head }}</span>
                                <span v-if="splitLabel(child.label).tail" class="block text-[11px] leading-tight opacity-70">{{ splitLabel(child.label).tail }}</span>
                            </span>
                            <span class="shrink-0 rounded bg-white/10 px-1.5 py-0.5 text-[9px] text-white/45">準備中</span>
                        </div>
                    </template>
                </div>

                <!-- 発注管理（トグル）。業者承諾確認のみ。 -->
                <button
                    type="button"
                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-base font-medium text-white/80 transition-colors hover:bg-white/10"
                    @click="toggleOrderMenu"
                >
                    <ClipboardList class="size-4.5 shrink-0" />
                    <span class="flex-1 text-left">発注管理</span>
                    <span
                        v-if="orderBadgeTotal"
                        class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-green-600 px-1.5 text-[10px] font-bold text-white tabular-nums"
                    >
                        <span class="block translate-y-[0.5px] leading-none">{{ orderBadgeTotal > 99 ? '99+' : orderBadgeTotal }}</span>
                    </span>
                    <ChevronDown class="size-4 shrink-0 transition-transform" :class="orderMenuOpen ? '' : '-rotate-90'" />
                </button>
                <div v-show="orderMenuOpen" class="mt-1 space-y-1 pl-4">
                    <Link
                        v-for="child in orderChildren"
                        :key="child.label"
                        :href="child.href!"
                        class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] leading-snug transition"
                        :class="child.active
                            ? 'bg-white/12 font-semibold text-white shadow-[inset_3px_0_0_#c4a35b,inset_0_1px_0_rgba(255,255,255,0.2)] backdrop-blur-sm'
                            : 'text-white/75 hover:bg-white/10'"
                    >
                        <span class="flex-1">
                            <span class="block">{{ splitLabel(child.label).head }}</span>
                            <span v-if="splitLabel(child.label).tail" class="block text-[11px] leading-tight opacity-70">{{ splitLabel(child.label).tail }}</span>
                        </span>
                        <span
                            v-if="child.badge"
                            class="inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-green-600 px-1.5 text-[10px] font-bold text-white tabular-nums"
                        >
                            <span class="block translate-y-[0.5px] leading-none">{{ child.badge > 99 ? '99+' : child.badge }}</span>
                        </span>
                    </Link>
                </div>

                <!-- 完了・納品管理（トグル）。完了確認と部長完了承認。 -->
                <button
                    type="button"
                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-base font-medium text-white/80 transition-colors hover:bg-white/10"
                    @click="toggleDeliveryMenu"
                >
                    <PackageCheck class="size-4.5 shrink-0" />
                    <span class="flex-1 text-left">完了・納品管理</span>
                    <span
                        v-if="deliveryBadgeTotal"
                        class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-green-600 px-1.5 text-[10px] font-bold text-white tabular-nums"
                    >
                        <span class="block translate-y-[0.5px] leading-none">{{ deliveryBadgeTotal > 99 ? '99+' : deliveryBadgeTotal }}</span>
                    </span>
                    <ChevronDown class="size-4 shrink-0 transition-transform" :class="deliveryMenuOpen ? '' : '-rotate-90'" />
                </button>
                <div v-show="deliveryMenuOpen" class="mt-1 space-y-1 pl-4">
                    <Link
                        v-for="child in deliveryChildren"
                        :key="child.label"
                        :href="child.href!"
                        class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] leading-snug transition"
                        :class="child.active
                            ? 'bg-white/12 font-semibold text-white shadow-[inset_3px_0_0_#c4a35b,inset_0_1px_0_rgba(255,255,255,0.2)] backdrop-blur-sm'
                            : 'text-white/75 hover:bg-white/10'"
                    >
                        <span class="flex-1">
                            <span class="block">{{ splitLabel(child.label).head }}</span>
                            <span v-if="splitLabel(child.label).tail" class="block text-[11px] leading-tight opacity-70">{{ splitLabel(child.label).tail }}</span>
                        </span>
                        <span
                            v-if="child.badge"
                            class="inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-green-600 px-1.5 text-[10px] font-bold text-white tabular-nums"
                        >
                            <span class="block translate-y-[0.5px] leading-none">{{ child.badge > 99 ? '99+' : child.badge }}</span>
                        </span>
                    </Link>
                </div>

                <!-- 請求管理（トグル）。請求承認と部長請求取消確認。建設部部長のみ表示。 -->
                <template v-if="showBillingMenu">
                    <button
                        type="button"
                        class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-base font-medium text-white/80 transition-colors hover:bg-white/10"
                        @click="toggleBillingMenu"
                    >
                        <Receipt class="size-4.5 shrink-0" />
                        <span class="flex-1 text-left">請求管理</span>
                        <span
                            v-if="billingBadgeTotal"
                            class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-green-600 px-1.5 text-[10px] font-bold text-white tabular-nums"
                        >
                            <span class="block translate-y-[0.5px] leading-none">{{ billingBadgeTotal > 99 ? '99+' : billingBadgeTotal }}</span>
                        </span>
                        <ChevronDown class="size-4 shrink-0 transition-transform" :class="billingMenuOpen ? '' : '-rotate-90'" />
                    </button>
                    <div v-show="billingMenuOpen" class="mt-1 space-y-1 pl-4">
                        <Link
                            v-for="child in billingChildren"
                            :key="child.label"
                            :href="child.href!"
                            class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] leading-snug transition"
                            :class="child.active
                                ? 'bg-white/12 font-semibold text-white shadow-[inset_3px_0_0_#c4a35b,inset_0_1px_0_rgba(255,255,255,0.2)] backdrop-blur-sm'
                                : 'text-white/75 hover:bg-white/10'"
                        >
                            <span class="flex-1">
                                <span class="block">{{ splitLabel(child.label).head }}</span>
                                <span v-if="splitLabel(child.label).tail" class="block text-[11px] leading-tight opacity-70">{{ splitLabel(child.label).tail }}</span>
                            </span>
                            <span
                                v-if="child.badge"
                                class="inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-green-600 px-1.5 text-[10px] font-bold text-white tabular-nums"
                            >
                                <span class="block translate-y-[0.5px] leading-none">{{ child.badge > 99 ? '99+' : child.badge }}</span>
                            </span>
                        </Link>
                    </div>
                </template>
            </nav>

            <!-- フッター（コピーライト風）：サイドバー最下部にシステム名を控えめに表示。 -->
            <div class="shrink-0 border-t border-white/10 px-4 py-3 text-center text-[10px] tracking-wide text-white/40">
                © 業務管理システム-FIX-
            </div>
        </aside>

        <!-- モバイル用オーバーレイ -->
        <div v-if="mobileOpen" class="fixed inset-0 z-40 bg-black/40 md:hidden" @click="mobileOpen = false" />

        <!-- デスクトップ: 折りたたみ時の再オープンボタン（閉じた時のみ） -->
        <button
            v-show="collapsed"
            class="fixed left-2 top-2.5 z-50 hidden size-9 items-center justify-center rounded-lg border bg-card text-foreground shadow-sm hover:bg-accent md:flex"
            title="メニューを開く"
            @click="collapsed = false"
        >
            <PanelLeftOpen class="size-5" />
        </button>

        <!-- モバイル: メニューを開く（ヘッダー撤去に伴う最小限の開閉ボタン） -->
        <button
            v-show="!mobileOpen"
            class="fixed left-3 top-3 z-40 flex size-10 items-center justify-center rounded-lg border bg-card text-foreground shadow-sm md:hidden"
            title="メニュー"
            @click="mobileOpen = true"
        >
            <Menu class="size-5" />
        </button>

        <!-- コンテンツ（グローバルヘッダーは撤去。アカウント/ログアウトはサイドバーへ移動） -->
        <div class="transition-[padding]" :class="collapsed ? 'md:pl-0' : 'md:pl-72'">
            <slot />
        </div>
    </div>
</template>

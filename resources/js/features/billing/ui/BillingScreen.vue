<script setup lang="ts">
// 請求（もらい）系の共通画面コンテナ。見積作成 / 見積承認 / 見積取消申請 / 見積取消承認 /
// 発注書確認 で共用し、mode（BILLING_MODE_CONFIG）で操作を出し分ける。
//
// もらいは相見積・業者選定が発生しない（金額は TEL 合意済みで FELIX が代理入力する）ため、
// 支払側（quotation-flow）とは列構成・操作が異なる。詳細設計:
//   docs/detailed-design/quotations/06〜09_請求_*_詳細設計.md
//   docs/detailed-design/orders/02_請求_発注書確認_詳細設計.md
//
// ※ 現時点は**モック**。一覧はサーバの固定データ、送信は成功トーストを返すだけ。
import { computed, inject, reactive, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';
import { SIDEBAR_COLLAPSED } from '@/shared/ui/layouts';
import { FilterBar } from '@/shared/ui/filter-bar';
import { Pager } from '@/shared/ui/pager';
import { useFelixTheme } from '@/shared/lib/felix-theme';
import BillingProjectCard from './BillingProjectCard.vue';
import BillingQuotationModal, { type BillingQuotationInput } from './BillingQuotationModal.vue';
import { BILLING_MODE_CONFIG } from '../model/billing-mode';
import type {
    BillingFilters,
    BillingMode,
    BillingPagination,
    BillingProject,
    BillingRow,
} from '../model/billing';
import type { ProjectFilters } from '@/shared/api';

const props = defineProps<{
    mode: BillingMode;
    glass?: boolean;
    projects: BillingProject[];
    pagination: BillingPagination;
    filters: BillingFilters;
}>();

const isThemed = computed(() => props.glass === true);
const config = computed(() => BILLING_MODE_CONFIG[props.mode]);
const { rootClass, stickyBgClass, glassPanelClass, headingClass, onGlassTextClass, pagerBtnClass } =
    useFelixTheme(isThemed);

// サイドバー折りたたみ時は左上に再オープンボタンが浮くため、タイトル行の左に余白を確保する。
const sidebarCollapsed = inject(SIDEBAR_COLLAPSED, ref(false));

// 明細の開閉（既定は開く）。
const collapsed = reactive<Record<number, boolean>>({});
const isOpen = (id: number): boolean => collapsed[id] !== true;
const toggle = (id: number): void => {
    collapsed[id] = isOpen(id);
};
const anyOpen = computed(() => props.projects.some((p) => isOpen(p.id)));
const toggleAllProjects = (): void => {
    const close = anyOpen.value;
    props.projects.forEach((p) => {
        collapsed[p.id] = close;
    });
};

const allRows = computed<BillingRow[]>(() => props.projects.flatMap((p) => p.rows));

// pick モード（見積承認）の選択。1つの真実は checked（partnerId → 真偽）。
const checked = reactive<Record<number, boolean>>({});
const checkedKeys = computed(() => new Set(allRows.value.map((r) => r.partnerId).filter((id) => checked[id])));
const toggleRow = (row: BillingRow): void => {
    checked[row.partnerId] = !checked[row.partnerId];
};
const bulkAllSelected = computed(
    () => allRows.value.length > 0 && allRows.value.every((r) => checkedKeys.value.has(r.partnerId)),
);
const toggleSelectAll = (): void => {
    const select = !bulkAllSelected.value;
    allRows.value.forEach((r) => {
        checked[r.partnerId] = select;
    });
};

// 送信は Inertia useForm（processing 統合）。理由は取消系のみ使う。
const form = useForm<{ partnerIds: number[]; reason: string }>({ partnerIds: [], reason: '' });
const actionEnabled = computed(() => !form.processing && checkedKeys.value.size > 0);
const actionBtnClass = computed(() => {
    if (!isThemed.value) {
        return actionEnabled.value
            ? 'h-9 rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground transition hover:opacity-90'
            : 'h-9 cursor-not-allowed rounded-md border bg-card px-4 text-sm font-semibold text-muted-foreground';
    }
    return actionEnabled.value
        ? 'h-9 rounded-xl border border-[#c4a35b] bg-[#c4a35b] px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-[#b3923f]'
        : 'h-9 cursor-not-allowed rounded-xl border border-[#c4a35b]/40 bg-[#c4a35b]/10 px-4 text-sm font-semibold text-[#8a6a25]/60 backdrop-blur-md';
});

/** ヘッダーの一括アクション（見積承認）。 */
const submitAction = (): void => {
    if (!actionEnabled.value || config.value.actionUrl === null) {
        return;
    }
    form.partnerIds = [...checkedKeys.value];
    form.reason = '';
    form.post(config.value.actionUrl, {
        preserveScroll: true,
        onSuccess: () => {
            Object.keys(checked).forEach((key) => delete checked[Number(key)]);
        },
    });
};

// 取消申請 / 取消承認：1件ずつ理由入力モーダルで実行する。
const reasonModalOpen = ref(false);
const reasonTarget = ref<BillingRow | null>(null);
const openReasonModal = (row: BillingRow): void => {
    form.partnerIds = [row.partnerId];
    form.reason = '';
    form.clearErrors();
    reasonTarget.value = row;
    reasonModalOpen.value = true;
};
const closeReasonModal = (): void => {
    reasonModalOpen.value = false;
    reasonTarget.value = null;
};
const submitWithReason = (): void => {
    if (!form.reason.trim() || form.processing || config.value.actionUrl === null) {
        return;
    }
    form.post(config.value.actionUrl, {
        preserveScroll: true,
        onSuccess: () => closeReasonModal(),
    });
};

// ③ 見積作成モーダル。
const quotationModalOpen = ref(false);
const quotationTarget = ref<BillingRow | null>(null);
const quotationBuilding = ref('');
const openQuotationModal = (row: BillingRow, buildingName: string): void => {
    quotationTarget.value = row;
    quotationBuilding.value = buildingName;
    quotationModalOpen.value = true;
};
const submitQuotation = (payload: BillingQuotationInput): void => {
    if (config.value.actionUrl === null) {
        return;
    }
    router.post(config.value.actionUrl, { ...payload }, {
        preserveScroll: true,
        onSuccess: () => {
            quotationModalOpen.value = false;
            quotationTarget.value = null;
        },
    });
};

// 発注書プレビュー（⑨）。モックのため内容はプレースホルダ。
const orderDocOpen = ref(false);
const orderDocTarget = ref<BillingRow | null>(null);
const openOrderDoc = (row: BillingRow): void => {
    orderDocTarget.value = row;
    orderDocOpen.value = true;
};

/** 行ボタン（pick 以外）の押下。モードごとに開くモーダルを振り分ける。 */
const onRowAction = (row: BillingRow, buildingName: string): void => {
    switch (config.value.kind) {
        case 'modal':
            openQuotationModal(row, buildingName);
            break;
        case 'per-row':
            openReasonModal(row);
            break;
        case 'view':
            openOrderDoc(row);
            break;
        default:
            break;
    }
};

// felix_total を開く iframe モーダル（見積先の詳細 / 業者追加）。モックでは URL 未設定。
const iframeOpen = ref(false);
const iframeUrl = ref<string | null>(null);
const iframeTitle = ref('');
const openIframe = (payload: { url: string | null; title: string }): void => {
    iframeUrl.value = payload.url;
    iframeTitle.value = payload.title;
    iframeOpen.value = true;
};

// やり取り（チャット）。実装は支払側と共通化する想定。モックでは案内のみ出す。
const chatOpen = ref(false);
const chatTarget = ref<BillingRow | null>(null);
const openChat = (row: BillingRow): void => {
    chatTarget.value = row;
    chatOpen.value = true;
};

// 絞り込み・ページ送り（サーバ側フィルタ）。
const onSearch = (payload: ProjectFilters): void => {
    router.get(window.location.pathname, { ...payload }, { preserveState: true, preserveScroll: true });
};
const goToPage = (page: number): void => {
    router.get(
        window.location.pathname,
        {
            keyword: props.filters.keyword || undefined,
            itemLabel: props.filters.itemLabel || undefined,
            vendor: props.filters.vendor || undefined,
            page,
        },
        { preserveState: true, preserveScroll: true },
    );
};
</script>

<template>
    <Head :title="`見積管理 - 【請求】${config.title}`" />

    <div :class="rootClass">
        <!-- 背景の装飾ブロブ（ガラスのぼかし対象） -->
        <div v-if="isThemed" aria-hidden="true" class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -left-20 -top-20 size-96 rounded-full bg-[#3f3f46]/30 blur-3xl"></div>
            <div class="absolute -top-10 right-10 size-80 rounded-full bg-[#c4a35b]/40 blur-3xl"></div>
            <div class="absolute left-1/3 top-1/3 size-112 rounded-full bg-[#52525b]/25 blur-3xl"></div>
            <div class="absolute -bottom-24 right-1/4 size-96 rounded-full bg-[#c4a35b]/20 blur-3xl"></div>
        </div>

        <div class="relative">
            <!-- 固定ヘッダー（タイトル＋状態＋一括アクション＋絞り込み） -->
            <div class="sticky top-0 z-30 space-y-3 px-4 pb-3 pt-4 md:px-6" :class="stickyBgClass">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 transition-[padding]" :class="sidebarCollapsed ? 'md:pl-12' : ''">
                    <span v-if="isThemed" aria-hidden="true" class="h-7 w-1.5 rounded-full bg-[#c4a35b]"></span>
                    <h1 class="text-2xl font-bold tracking-tight" :class="headingClass">【請求】{{ config.title }}</h1>
                    <span class="text-sm" :class="onGlassTextClass">状態：{{ config.statusLabel }}</span>
                    <span class="rounded-md bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800">モック</span>
                    <button
                        v-if="config.actionLabel"
                        type="button"
                        :class="actionBtnClass"
                        :disabled="!actionEnabled"
                        :title="actionEnabled ? '' : config.hint"
                        @click="submitAction"
                    >
                        {{ form.processing ? config.processingLabel : config.actionLabel
                        }}<span v-if="!form.processing && checkedKeys.size"> （{{ checkedKeys.size }}）</span>
                    </button>
                </div>

                <FilterBar :filters="filters" :glass="glass" @search="onSearch" />
            </div>

            <!-- スクロール本体 -->
            <div class="space-y-4 px-4 pb-6 pt-3 md:px-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" :class="pagerBtnClass" @click="toggleAllProjects">
                            {{ anyOpen ? '明細を全て閉じる' : '明細を全て開く' }}
                        </button>
                        <button v-if="config.kind === 'pick'" type="button" :class="pagerBtnClass" @click="toggleSelectAll">
                            {{ bulkAllSelected ? config.bulkClearLabel : config.bulkSelectLabel }}
                        </button>
                    </div>
                    <Pager :pagination="pagination" :glass="glass" @change="goToPage" />
                </div>

                <BillingProjectCard
                    v-for="project in projects"
                    :key="project.id"
                    :project="project"
                    :mode="mode"
                    :glass="glass"
                    :open="isOpen(project.id)"
                    :selected-keys="checkedKeys"
                    @toggle="toggle(project.id)"
                    @row-toggle="toggleRow"
                    @row-action="(row) => onRowAction(row, project.name)"
                    @open-chat="openChat"
                    @open-iframe="openIframe"
                />

                <div v-if="!projects.length" class="p-8 text-center" :class="[glassPanelClass, onGlassTextClass]">
                    対象の請求先がありません。
                </div>

                <div class="flex justify-end pt-1">
                    <Pager :pagination="pagination" :glass="glass" @change="goToPage" />
                </div>
            </div>
        </div>
    </div>

    <!-- ③ 見積作成モーダル -->
    <BillingQuotationModal
        :open="quotationModalOpen"
        :row="quotationTarget"
        :building-name="quotationBuilding"
        :processing="form.processing"
        @close="quotationModalOpen = false"
        @submit="submitQuotation"
    />

    <!-- 取消申請 / 取消承認：理由入力モーダル（理由必須） -->
    <div v-if="reasonModalOpen && reasonTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="closeReasonModal">
        <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center gap-3 border-l-4 border-l-[#c4a35b] bg-primary px-4 py-3 text-primary-foreground">
                <p class="min-w-0 flex-1 truncate text-sm font-bold">【請求】{{ config.title }}</p>
                <button type="button" class="rounded-lg p-1 hover:bg-white/10" @click="closeReasonModal"><X class="size-5" /></button>
            </div>
            <div class="space-y-3 p-4">
                <p class="text-sm text-slate-700">
                    <span class="font-bold">{{ reasonTarget.vendorName }}</span> を{{ config.columnLabel }}します。
                </p>
                <label class="block text-sm">
                    <span class="mb-1 block text-xs text-slate-500">理由 <span class="text-red-600">*</span></span>
                    <textarea
                        v-model="form.reason"
                        rows="4"
                        class="w-full rounded-lg border border-slate-300 bg-white p-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    ></textarea>
                </label>
                <p class="text-xs text-slate-500">入力した理由は、この項目のやり取り（コメント）に記録されます。</p>
            </div>
            <div class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3">
                <button type="button" class="h-9 rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-100" @click="closeReasonModal">
                    キャンセル
                </button>
                <button
                    type="button"
                    class="h-9 rounded-xl px-4 text-sm font-semibold transition"
                    :class="form.reason.trim() && !form.processing
                        ? 'border border-[#c4a35b] bg-[#c4a35b] text-white hover:bg-[#b3923f]'
                        : 'cursor-not-allowed border border-[#c4a35b]/40 bg-[#c4a35b]/10 text-[#8a6a25]/60'"
                    :disabled="!form.reason.trim() || form.processing"
                    @click="submitWithReason"
                >
                    {{ form.processing ? config.processingLabel : 'OK' }}
                </button>
            </div>
        </div>
    </div>

    <!-- ⑨ 発注書プレビュー（モック） -->
    <div v-if="orderDocOpen && orderDocTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="orderDocOpen = false">
        <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center gap-3 border-l-4 border-l-[#c4a35b] bg-primary px-4 py-3 text-primary-foreground">
                <p class="min-w-0 flex-1 truncate text-sm font-bold">発注書プレビュー</p>
                <button type="button" class="rounded-lg p-1 hover:bg-white/10" @click="orderDocOpen = false"><X class="size-5" /></button>
            </div>
            <div class="space-y-3 p-6">
                <div class="relative rounded-xl border border-slate-300 p-6">
                    <p class="text-center text-lg font-bold tracking-widest">発 注 書</p>
                    <dl class="mt-4 space-y-1 text-sm">
                        <div class="flex gap-2"><dt class="w-24 text-slate-500">パートナー</dt><dd>{{ orderDocTarget.vendorName }}</dd></div>
                        <div class="flex gap-2"><dt class="w-24 text-slate-500">項目</dt><dd>{{ orderDocTarget.itemName }}</dd></div>
                        <div class="flex gap-2"><dt class="w-24 text-slate-500">承諾日</dt><dd>{{ orderDocTarget.acceptedAt ?? '未承諾' }}</dd></div>
                    </dl>
                    <!-- ⑨ 業者が承諾済みなら「承認済」のハンコが押された状態になる。 -->
                    <span
                        v-if="orderDocTarget.acceptedAt"
                        class="absolute right-6 top-6 rotate-[-12deg] rounded-md border-4 border-red-500 px-3 py-1 text-lg font-bold tracking-widest text-red-500 opacity-80"
                    >承認済</span>
                </div>
                <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">
                    モックのためレイアウトのみです。実際の発注書は felix_total 側の帳票を表示します。
                </p>
            </div>
        </div>
    </div>

    <!-- iframe（見積先の詳細 / 業者追加）。モックでは URL 未設定。 -->
    <div v-if="iframeOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="iframeOpen = false">
        <div class="flex h-[80vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center gap-3 border-l-4 border-l-[#c4a35b] bg-primary px-4 py-3 text-primary-foreground">
                <p class="min-w-0 flex-1 truncate text-sm font-bold">{{ iframeTitle }}</p>
                <button type="button" class="rounded-lg p-1 hover:bg-white/10" @click="iframeOpen = false"><X class="size-5" /></button>
            </div>
            <iframe v-if="iframeUrl" :src="iframeUrl" class="flex-1" frameborder="0"></iframe>
            <div v-else class="flex flex-1 items-center justify-center p-6 text-sm text-slate-500">
                モックのため、felix_total の画面は開きません。
            </div>
        </div>
    </div>

    <!-- やり取り（チャット）。モックでは案内のみ。 -->
    <div v-if="chatOpen && chatTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="chatOpen = false">
        <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center gap-3 border-l-4 border-l-[#c4a35b] bg-primary px-4 py-3 text-primary-foreground">
                <p class="min-w-0 flex-1 truncate text-sm font-bold">やり取り：{{ chatTarget.itemName }}</p>
                <button type="button" class="rounded-lg p-1 hover:bg-white/10" @click="chatOpen = false"><X class="size-5" /></button>
            </div>
            <div class="p-6 text-sm text-slate-600">
                モックのため未接続です。実装時は支払側（見積管理）と同じコメントスレッドを利用します。
                <span class="mt-2 block text-xs text-slate-500">コメント {{ chatTarget.messageCount }} 件 / 未読 {{ chatTarget.unreadCount }} 件</span>
            </div>
        </div>
    </div>
</template>

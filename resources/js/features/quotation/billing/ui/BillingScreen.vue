<script setup lang="ts">
// 請求（もらい）系の共通画面コンテナ。見積作成 / 見積承認 / 見積取消申請 / 見積取消承認 /
// 発注書確認 で共用し、mode（BILLING_MODE_CONFIG）で操作を出し分ける。
//
// もらいは相見積・業者選定が発生しない（金額は TEL 合意済みで FELIX が代理入力する）ため、
// 支払側（features/payable）とは列構成・操作が異なる。詳細設計:
//   docs/detailed-design/quotations/06〜09_請求_*_詳細設計.md
//   docs/detailed-design/orders/02_請求_発注書確認_詳細設計.md
//
import { computed, inject, reactive, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { X, Ban } from 'lucide-vue-next';
import { SIDEBAR_COLLAPSED } from '@/shared/ui/layouts';
import { FilterBar } from '@/shared/ui/filter-bar';
import { Pager } from '@/shared/ui/pager';
import { CommentThreadModal } from '@/shared/ui/comment-thread';
import { useFelixTheme } from '@/shared/lib/felix-theme';
import { index as quotationMessagesIndex, store as quotationMessagesStore } from '@/shared/api/routes/quotation-management/payable-messages';
import { index as billingMessagesIndex, store as billingMessagesStore } from '@/shared/api/routes/quotation-management/billing-messages';
import BillingProjectCard from './BillingProjectCard.vue';
import BillingQuotationModal, { type BillingQuotationInput } from './BillingQuotationModal.vue';
import { BILLING_MODE_CONFIG, billingRowKey } from '../model/billing-mode';
import type {
    BillingFilters,
    BillingMasters,
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
    /** 見積作成モーダルの選択肢（拠点 / 部署 / 単位）。見積作成画面のみ渡る。 */
    masters?: BillingMasters;
}>();

/** モーダルへ渡すマスタ（未指定の画面では空の選択肢にする）。 */
const modalMasters = computed<BillingMasters>(
    () => props.masters ?? { branches: [], departments: [], units: [] },
);

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
const anyOpen = computed(() => displayProjects.value.some((p) => isOpen(p.id)));
const toggleAllProjects = (): void => {
    const close = anyOpen.value;
    props.projects.forEach((p) => {
        collapsed[p.id] = close;
    });
};

const allRows = computed<BillingRow[]>(() => props.projects.flatMap((p) => p.rows));

// 区分。請求系画面の初期値は「請求」。「全て」にすると支払取引先も同じ一覧に並ぶ（表示のみ）。
type PartnerKind = 'all' | 'payable' | 'billing';
const kind = computed<PartnerKind>(() => (props.filters.kind === 'all' ? 'all' : 'billing'));
const kindOptions: { value: PartnerKind; label: string }[] = [
    { value: 'all', label: '全て' },
    { value: 'billing', label: '請求' },
];
/** 区分を切り替える。参照するテーブルが変わるためサーバから取り直す。 */
const setKind = (value: PartnerKind): void => {
    if (value === kind.value) {
        return;
    }
    router.get(
        window.location.pathname,
        {
            keyword: props.filters.keyword || undefined,
            itemLabel: props.filters.itemLabel || undefined,
            vendor: props.filters.vendor || undefined,
            kind: value,
        },
        { preserveState: true, preserveScroll: true },
    );
};

// 画面ごとの絞り込み（処理フロー I列）。初期値は I列の太字に合わせる。
/** 【請求】見積作成：見積の有無（全て / 見積作成済み / 見積未作成）。初期＝全て。 */
type QuotationFilter = 'all' | 'created' | 'not-created';
const quotationFilter = ref<QuotationFilter>('all');
const quotationFilterOptions: { value: QuotationFilter; label: string }[] = [
    { value: 'all', label: '全て' },
    { value: 'created', label: '見積作成済み' },
    { value: 'not-created', label: '見積未作成' },
];
/** 【請求】見積承認：見積作成ずみ（初期 ON）。 */
const createdOnly = ref(props.mode === 'billing-quote-approval');
// 「この画面で操作できる行（operable）だけに絞る」チェックボックス。
// 文言は画面ごと（見積作成=作成可能 / 見積承認=未承認 / 見積取消申請=申請可能）。
// 出す画面では**初期表示 ON**（着手すべき行だけを最初に見せる）。外すと全件表示に戻る。
const operableFilterLabel = computed(() => config.value.operableFilterLabel ?? null);
const operableOnly = ref(operableFilterLabel.value !== null);

/** 画面に出す案件（クライアント側の行フィルタを適用したもの）。 */
const displayProjects = computed(() => {
    const rowFilters: ((row: BillingRow) => boolean)[] = [];
    if (quotationFilter.value === 'created') {
        rowFilters.push((r) => r.quotationAmount !== null);
    } else if (quotationFilter.value === 'not-created') {
        rowFilters.push((r) => r.quotationAmount === null);
    }
    if (createdOnly.value) {
        rowFilters.push((r) => r.quotationAmount !== null);
    }
    if (operableOnly.value && operableFilterLabel.value !== null) {
        rowFilters.push((r) => r.operable);
    }
    if (rowFilters.length === 0) {
        return props.projects;
    }
    // 絞り込みは**自区分（請求）の行にだけ**効かせる。区分「全て」で並ぶ支払行は「表示のみ」なので
    // 絞り込み対象にせず、絞り込み後も請求行が残った項目（itemName）にだけそのまま並べる。
    return props.projects
        .map((p) => {
            const keep = (r: BillingRow): boolean => rowFilters.every((f) => f(r));
            const items = new Set(p.rows.filter((r) => r.billingTarget && keep(r)).map((r) => r.itemName));
            return { ...p, rows: p.rows.filter((r) => (r.billingTarget ? keep(r) : items.has(r.itemName))) };
        })
        .filter((p) => p.rows.length > 0);
});

// pick モード（見積承認）の選択。1つの真実は checked（行キー → 真偽）。
// キーは区分込み（billingRowKey）。区分フィルタ「全て」では支払取引先も並び、
// partnerId だけだと請求行と id が衝突して選択状態が混線するため。
const checked = reactive<Record<string, boolean>>({});
const checkedKeys = computed(() => new Set(allRows.value.map(billingRowKey).filter((key) => checked[key])));
const toggleRow = (row: BillingRow): void => {
    // operable = 処理フロー J列の対象ステータスか。false の行は一覧に出すが操作させない（K列）。
    if (!row.operable) {
        return;
    }
    const key = billingRowKey(row);
    checked[key] = !checked[key];
};
/** 一括選択の対象（操作できる行だけ）。 */
const bulkSelectableRows = computed(() => allRows.value.filter((r) => r.operable));
const bulkAllSelected = computed(
    () => bulkSelectableRows.value.length > 0 && bulkSelectableRows.value.every((r) => checkedKeys.value.has(billingRowKey(r))),
);
const toggleSelectAll = (): void => {
    const select = !bulkAllSelected.value;
    bulkSelectableRows.value.forEach((r) => {
        checked[billingRowKey(r)] = select;
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
    form.partnerIds = allRows.value.filter((r) => checkedKeys.value.has(billingRowKey(r))).map((r) => r.partnerId);
    form.reason = '';
    form.post(config.value.actionUrl, {
        preserveScroll: true,
        onSuccess: () => {
            Object.keys(checked).forEach((key) => delete checked[key]);
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

// 否認（見積承認 / 見積取消承認）：理由入力モーダル → 差し戻し or 却下。
// - 見積承認 …… 承認せず ③ 見積作成（DRAFT）へ差し戻す
// - 見積取消承認 … 取消を認めず、承認済み（APPROVED）のまま据え置く
const rejectConfig = computed(() => config.value.reject ?? null);
const rejectUrl = computed(() => rejectConfig.value?.url ?? null);
const rejectForm = useForm<{ partnerIds: number[]; reason: string }>({ partnerIds: [], reason: '' });
const rejectTarget = ref<BillingRow | null>(null);
const rejectModalOpen = ref(false);
const openReject = (row: BillingRow): void => {
    rejectForm.reset();
    rejectForm.clearErrors();
    rejectForm.partnerIds = [row.partnerId];
    rejectTarget.value = row;
    rejectModalOpen.value = true;
};
const closeReject = (): void => {
    rejectModalOpen.value = false;
    rejectTarget.value = null;
};
const submitReject = (): void => {
    if (!rejectForm.reason.trim() || rejectForm.processing || rejectUrl.value === null) {
        return;
    }
    rejectForm.post(rejectUrl.value, {
        preserveScroll: true,
        onSuccess: () => closeReject(),
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
    // 見積書ファイル（File）を含むため multipart で送る（forceFormData）。
    router.post(config.value.actionUrl, { ...payload }, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            quotationModalOpen.value = false;
            quotationTarget.value = null;
        },
    });
};

/**
 * 行ボタン（pick 以外）の押下。モードごとに開くモーダルを振り分ける。
 * 発注書確認（view）の「発注書」ボタンは felix_total の帳票を iframe で開くため、
 * カード側から直接 open-iframe を投げる（支払側の発注書ボタンと同じ）。
 */
const onRowAction = (row: BillingRow, buildingName: string): void => {
    if (!row.operable) {
        return;
    }
    switch (config.value.kind) {
        case 'modal':
            openQuotationModal(row, buildingName);
            break;
        case 'per-row':
            openReasonModal(row);
            break;
        default:
            break;
    }
};

// felix_total を開く iframe モーダル（見積先の詳細 / 業者追加）。
const iframeOpen = ref(false);
const iframeUrl = ref<string | null>(null);
const iframeTitle = ref('');
const openIframe = (payload: { url: string | null; title: string }): void => {
    iframeUrl.value = payload.url;
    iframeTitle.value = payload.title;
    iframeOpen.value = true;
};

const page = usePage();
// やり取り（チャット）：建物予算項目（t_building_budget_items）単位のスレッド。
// 取得・投稿・添付の UI は共通コンポーネント（CommentThreadModal）で支払側と共用する。
// 区分「全て」では支払（はらい）の行も並ぶため、行の区分に応じて叩き先を切り替える
// （請求＝billing-messages / 支払＝quotation-messages）。同一項目なら中身は同じスレッド。
const myRole = computed<'manager' | 'staff'>(() => (page.props.auth?.user?.isEstimateManager ? 'manager' : 'staff'));
const chatOpen = ref(false);
const chatTarget = ref<BillingRow | null>(null);
const chatBuilding = ref('');
const chatIndexUrl = computed<string | null>(() => {
    const row = chatTarget.value;
    if (row == null) {
        return null;
    }
    return row.billingTarget ? billingMessagesIndex(row.partnerId).url : quotationMessagesIndex(row.partnerId).url;
});
const chatStoreUrl = computed<string | null>(() => {
    const row = chatTarget.value;
    if (row == null) {
        return null;
    }
    return row.billingTarget ? billingMessagesStore(row.partnerId).url : quotationMessagesStore(row.partnerId).url;
});
const openChat = (row: BillingRow, buildingName = ''): void => {
    chatTarget.value = row;
    chatBuilding.value = buildingName;
    chatOpen.value = true;
    // 開いた時点で既読化される（GET index でポインタ更新）。一覧の未読バッジを即時クリア。
    row.unreadCount = 0;
};
const closeChat = (): void => {
    chatOpen.value = false;
    chatTarget.value = null;
};
// 投稿成功時：一覧の「やり取り」件数バッジ・コメント有無を楽観的に更新。
const onChatPosted = (): void => {
    if (chatTarget.value) {
        chatTarget.value.messageCount = (chatTarget.value.messageCount ?? 0) + 1;
        chatTarget.value.hasComments = true;
    }
};

// 絞り込み・ページ送り（サーバ側フィルタ）。
const onSearch = (payload: ProjectFilters): void => {
    router.get(window.location.pathname, { ...payload, kind: kind.value }, { preserveState: true, preserveScroll: true });
};
const goToPage = (page: number): void => {
    router.get(
        window.location.pathname,
        {
            keyword: props.filters.keyword || undefined,
            itemLabel: props.filters.itemLabel || undefined,
            vendor: props.filters.vendor || undefined,
            kind: kind.value,
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
                        <!--
                            区分（支払 / 請求）の切り替え。請求系画面の初期値は「請求」。
                            「支払」に切り替えると支払取引先を表示のみで参照できる（操作は不可）。
                        -->
                        <div class="inline-flex items-center gap-0.5 rounded-lg border border-primary/20 bg-white/70 p-0.5 backdrop-blur-md">
                            <button
                                v-for="opt in kindOptions"
                                :key="opt.value"
                                type="button"
                                class="rounded-md px-3 py-1.5 text-sm font-bold transition"
                                :class="kind === opt.value ? 'bg-[#c4a35b] text-white shadow-sm' : 'text-primary hover:bg-primary/10'"
                                :title="opt.value === 'all' ? '支払（はらい）の取引先も表示のみで並べる' : ''"
                                @click="setKind(opt.value)"
                            >
                                {{ opt.label }}
                            </button>
                        </div>
                        <button v-if="config.kind === 'pick'" type="button" :class="pagerBtnClass" @click="toggleSelectAll">
                            {{ bulkAllSelected ? config.bulkClearLabel : config.bulkSelectLabel }}
                        </button>
                        <!-- 【請求】見積作成：見積の有無（処理フロー I列・初期＝全て）。 -->
                        <div
                            v-if="mode === 'billing-quote-create'"
                            class="inline-flex items-center gap-0.5 rounded-lg border border-primary/20 bg-white/70 p-0.5 backdrop-blur-md"
                        >
                            <button
                                v-for="opt in quotationFilterOptions"
                                :key="opt.value"
                                type="button"
                                class="rounded-md px-3 py-1.5 text-sm font-bold transition"
                                :class="quotationFilter === opt.value ? 'bg-[#c4a35b] text-white shadow-sm' : 'text-primary hover:bg-primary/10'"
                                @click="quotationFilter = opt.value"
                            >
                                {{ opt.label }}
                            </button>
                        </div>
                        <!-- 【請求】見積承認：見積作成ずみ（処理フロー I列・初期 ON）。 -->
                        <label
                            v-if="mode === 'billing-quote-approval'"
                            class="inline-flex cursor-pointer select-none items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-bold backdrop-blur-md transition"
                            :class="createdOnly
                                ? 'border-[#c4a35b] bg-[#c4a35b]/15 text-[#8a6d2f]'
                                : 'border-primary/20 bg-white/70 text-primary hover:bg-primary/10'"
                        >
                            <input type="checkbox" v-model="createdOnly" class="size-4 cursor-pointer accent-[#c4a35b]" />
                            見積作成済み
                        </label>
                        <!--
                            この画面で操作できる行だけに絞る（処理フロー I列・初期 ON）。
                            文言は画面ごと（見積作成=作成可能 / 見積承認=未承認 / 見積取消申請=申請可能）。
                        -->
                        <label
                            v-if="operableFilterLabel"
                            class="inline-flex cursor-pointer select-none items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-bold backdrop-blur-md transition"
                            :class="operableOnly
                                ? 'border-[#c4a35b] bg-[#c4a35b]/15 text-[#8a6d2f]'
                                : 'border-primary/20 bg-white/70 text-primary hover:bg-primary/10'"
                        >
                            <input type="checkbox" v-model="operableOnly" class="size-4 cursor-pointer accent-[#c4a35b]" />
                            {{ operableFilterLabel }}
                        </label>
                    </div>
                    <Pager :pagination="pagination" :glass="glass" @change="goToPage" />
                </div>

                <BillingProjectCard
                    v-for="project in displayProjects"
                    :key="project.id"
                    :project="project"
                    :mode="mode"
                    :glass="glass"
                    :open="isOpen(project.id)"
                    :selected-keys="checkedKeys"
                    @toggle="toggle(project.id)"
                    @row-toggle="toggleRow"
                    @row-action="(row) => onRowAction(row, project.name)"
                    @reject="openReject"
                    @open-chat="(row, buildingName) => openChat(row, buildingName)"
                    @open-iframe="openIframe"
                />

                <div v-if="!displayProjects.length" class="p-8 text-center" :class="[glassPanelClass, onGlassTextClass]">
                    {{ operableOnly && operableFilterLabel
                        ? `「${operableFilterLabel}」に該当する請求先がありません。`
                        : '対象の請求先がありません。' }}
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
        :masters="modalMasters"
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

    <!-- 否認モーダル（【請求】見積取消承認）：理由を入力して取消を却下する（承認済みのまま据え置き）。 -->
    <div v-if="rejectModalOpen && rejectTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="closeReject">
        <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center gap-2 border-b px-4 py-3">
                <Ban class="size-5 text-red-600" />
                <span class="text-sm font-semibold text-slate-800">{{ rejectConfig?.modalTitle }}</span>
            </div>
            <div class="space-y-3 px-4 py-4">
                <p class="text-sm text-slate-600">
                    請求先「<span class="font-semibold text-slate-800">{{ rejectTarget.vendorName }}</span>」{{ rejectConfig?.description }}
                </p>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-500">否認理由<span class="text-red-500">（必須）</span></label>
                    <textarea
                        v-model="rejectForm.reason"
                        rows="4"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#c4a35b] focus:outline-none focus:ring-2 focus:ring-[#c4a35b]/30"
                        placeholder="否認の理由を入力してください"
                    />
                    <p v-if="rejectForm.errors.reason" class="mt-1 text-xs text-red-600">{{ rejectForm.errors.reason }}</p>
                </div>
                <p class="text-xs text-slate-500">入力した理由は、この項目のやり取り（コメント）に記録されます。</p>
            </div>
            <div class="flex justify-end gap-2 border-t px-4 py-3">
                <button
                    type="button"
                    class="h-9 rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                    @click="closeReject"
                >
                    キャンセル
                </button>
                <button
                    type="button"
                    class="h-9 rounded-xl px-4 text-sm font-semibold text-white transition"
                    :class="rejectForm.reason.trim() && !rejectForm.processing
                        ? 'bg-red-600 hover:bg-red-700'
                        : 'cursor-not-allowed bg-red-300'"
                    :disabled="!rejectForm.reason.trim() || rejectForm.processing"
                    @click="submitReject"
                >
                    {{ rejectForm.processing ? '否認中…' : '否認する' }}
                </button>
            </div>
        </div>
    </div>

    <!-- iframe（見積先の詳細 / 業者追加）。移行元（source_id）が無い取引先は URL を持たない。 -->
    <div v-if="iframeOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="iframeOpen = false">
        <div class="flex h-[80vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center gap-3 border-l-4 border-l-[#c4a35b] bg-primary px-4 py-3 text-primary-foreground">
                <p class="min-w-0 flex-1 truncate text-sm font-bold">{{ iframeTitle }}</p>
                <button type="button" class="rounded-lg p-1 hover:bg-white/10" @click="iframeOpen = false"><X class="size-5" /></button>
            </div>
            <iframe v-if="iframeUrl" :src="iframeUrl" class="flex-1" frameborder="0"></iframe>
            <div v-else class="flex flex-1 items-center justify-center p-6 text-sm text-slate-500">
                移行元（source_id）が無いため、felix_total の画面は開けません。
            </div>
        </div>
    </div>

    <!-- やり取り（チャット）モーダル：支払側と共通のコメントスレッド（項目単位）。 -->
    <CommentThreadModal
        :open="chatOpen"
        :index-url="chatIndexUrl"
        :store-url="chatStoreUrl"
        :item-name="chatTarget?.itemName"
        :building-name="chatBuilding"
        :my-role="myRole"
        @close="closeChat"
        @posted="onChatPosted"
    />
</template>

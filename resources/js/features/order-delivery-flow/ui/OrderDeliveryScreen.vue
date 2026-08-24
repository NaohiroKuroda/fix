<script setup lang="ts">
// 発注〜納品〜請求フローの共通画面コンテナ。7画面（mode）で共用する。
// 見積管理（QuotationManagementScreen）とほぼ同じUI：物件カード＋右端ボタン＋項目横チャット。
// 業者はシステムに登録しないため、ファイル添付は持たず、全画面「選択→ヘッダー確定／否認」だけ。
// チャットは見積管理と同じ項目単位コメント（quotation-management の quotation-messages ルート）を流用する。
import { computed, inject, nextTick, reactive, ref, type Ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { X, Ban, MessageSquare, Send, Paperclip, FileText, XCircle, CheckCircle2, ReceiptText } from 'lucide-vue-next';
import { index as quotationMessagesIndex, store as quotationMessagesStore } from '@/shared/api/routes/quotation-management/quotation-messages';
import { SIDEBAR_COLLAPSED } from '@/shared/ui/layouts';
import { FilterBar } from '@/shared/ui/filter-bar';
import { Pager } from '@/shared/ui/pager';
import OrderProjectCard from './OrderProjectCard.vue';
import { useFelixTheme } from '@/shared/lib/felix-theme';
import { orderRowKey, ORDER_DELIVERY_MODE_CONFIG } from '../model/order-delivery-mode';
import type { OrderDeliveryFilters, OrderDeliveryMode, OrderDeliveryPagination, OrderDeliveryProject, OrderDeliveryRow } from '../model/order-delivery';
import type { QuotationChatMessage } from '@/shared/api';
import type { ProjectFilters } from '@/shared/api';

const props = defineProps<{
    mode: OrderDeliveryMode;
    projects: OrderDeliveryProject[];
    pagination: OrderDeliveryPagination;
    filters: OrderDeliveryFilters;
    /** ヘッダー一括アクションの送信先URL。 */
    actionUrl: string;
    /** 否認（差し戻し）の送信先URL（該当画面のみ）。 */
    rejectUrl?: string | null;
    /** 再通知の送信先URL（業者承諾確認画面のみ）。 */
    renotifyUrl?: string | null;
    /**
     * 理由必須の取消アクション送信先URL。
     * 業者承諾確認画面（取消申請・追加列）／発注取消承認画面（取消承認・主操作）で使う。
     */
    cancelActionUrl?: string | null;
}>();

const isThemed = ref(true); // 見積管理と同じリキッドグラス調にする。
const config = computed(() => ORDER_DELIVERY_MODE_CONFIG[props.mode]);
const { rootClass, stickyBgClass, glassPanelClass, headingClass, onGlassTextClass, pagerBtnClass } = useFelixTheme(isThemed);

// タイトル行左の余白（サイドバー折りたたみ時に再オープンボタンと重ならないよう）。
const sidebarCollapsed = inject(SIDEBAR_COLLAPSED, ref(false));

// 明細カードの開閉（既定は開く）。
const collapsed = reactive<Record<number, boolean>>({});
const isOpen = (id: number): boolean => collapsed[id] !== true;
const toggle = (id: number): void => { collapsed[id] = isOpen(id); };
const anyOpen = computed(() => props.projects.some((p) => isOpen(p.id)));
const toggleAllProjects = (): void => {
    const close = anyOpen.value;
    props.projects.forEach((p) => { collapsed[p.id] = close; });
};

const allRows = computed<OrderDeliveryRow[]>(() => props.projects.flatMap((p) => p.rows));

// 選択（checkbox / pick-button 共通）。1つの真実は checked（行キー→真偽）。
const checked = reactive<Record<string, boolean>>({});
const checkedKeys = computed(() => new Set(Object.keys(checked).filter((k) => checked[k])));
const anyChecked = computed(() => checkedKeys.value.size > 0);
const toggleRow = (row: OrderDeliveryRow): void => {
    const key = orderRowKey(row);
    checked[key] = !checked[key];
};
const selectedCount = computed(() => checkedKeys.value.size);

// 一括「全て選択 / 全て解除」。
const bulkAllSelected = computed(() => allRows.value.length > 0 && allRows.value.every((r) => checked[orderRowKey(r)]));
const toggleSelectAll = (): void => {
    const select = !bulkAllSelected.value;
    allRows.value.forEach((r) => { checked[orderRowKey(r)] = select; });
};

// ヘッダー一括アクション送信。
const form = useForm<{ ids: number[] }>({ ids: [] });
const actionEnabled = computed(() => !form.processing && anyChecked.value);
const submitAction = (): void => {
    if (!actionEnabled.value) {
        return;
    }
    form.ids = allRows.value.filter((r) => checkedKeys.value.has(orderRowKey(r))).map((r) => r.companyId);
    if (form.ids.length === 0) {
        return;
    }
    form.post(props.actionUrl, {
        preserveScroll: true,
        onSuccess: () => {
            Object.keys(checked).forEach((key) => delete checked[key]);
        },
    });
};

// 否認モーダル（理由必須）。
const rejectForm = useForm<{ id: number | null; reason: string }>({ id: null, reason: '' });
const rejectTarget = ref<OrderDeliveryRow | null>(null);
const rejectModalOpen = ref(false);
const openReject = (row: OrderDeliveryRow): void => {
    rejectForm.reset();
    rejectForm.clearErrors();
    rejectForm.id = row.companyId;
    rejectTarget.value = row;
    rejectModalOpen.value = true;
};
const closeReject = (): void => {
    rejectModalOpen.value = false;
    rejectTarget.value = null;
};
const submitReject = (): void => {
    if (!rejectForm.reason.trim() || rejectForm.processing || !props.rejectUrl) {
        return;
    }
    rejectForm.post(props.rejectUrl, { preserveScroll: true, onSuccess: () => closeReject() });
};

// 取消モーダル（理由必須）：部長取消申請／取消承認画面（QuotationManagementScreen）と同じ、理由入力→確定の流れ。
// 業者承諾確認では追加列の「取消申請」、発注取消承認では主操作の「取消承認」として使う（mode で文言を出し分け）。
const isCancelApprovalMode = computed(() => props.mode === 'order-cancel-approval');
const cancelModalTitle = computed(() => (isCancelApprovalMode.value ? '取消承認' : '取消申請'));
const cancelModalReasonLabel = computed(() => `${cancelModalTitle.value}の理由`);
const cancelModalProcessingLabel = computed(() => (isCancelApprovalMode.value ? '承認中…' : '申請中…'));
const cancelForm = useForm<{ ids: number[]; reason: string }>({ ids: [], reason: '' });
const cancelTarget = ref<OrderDeliveryRow | null>(null);
const cancelModalOpen = ref(false);
const openCancelRequest = (row: OrderDeliveryRow): void => {
    cancelForm.reset();
    cancelForm.clearErrors();
    cancelForm.ids = [row.companyId];
    cancelTarget.value = row;
    cancelModalOpen.value = true;
};
const closeCancelRequest = (): void => {
    cancelModalOpen.value = false;
    cancelTarget.value = null;
};
const submitCancelRequest = (): void => {
    if (!cancelForm.reason.trim() || cancelForm.processing || !props.cancelActionUrl) {
        return;
    }
    cancelForm.post(props.cancelActionUrl, { preserveScroll: true, onSuccess: () => closeCancelRequest() });
};

// 完了確認画面：請求日クリック → 請求情報モーダル（金額・作成日・承認状況を表示するだけ）。
const invoiceModalOpen = ref(false);
const invoiceTarget = ref<OrderDeliveryRow | null>(null);
const openInvoice = (row: OrderDeliveryRow): void => {
    invoiceTarget.value = row;
    invoiceModalOpen.value = true;
};
const closeInvoice = (): void => {
    invoiceModalOpen.value = false;
    invoiceTarget.value = null;
};

// 発注書（felix_total の発注書確認画面）を開く iframe モーダル。見積管理と同じ仕組み。
const iframeOpen = ref(false);
const iframeUrl = ref<string | null>(null);
const iframeTitle = ref('');
const openIframe = (payload: { url: string | null; title: string }): void => {
    iframeUrl.value = payload.url;
    iframeTitle.value = payload.title;
    iframeOpen.value = true;
};
const closeIframe = (): void => {
    iframeOpen.value = false;
    iframeUrl.value = null;
};

// 承諾確認フィルタ（全て / 承諾済 / 未完了）。業者承諾確認画面のみ。業者側の承諾有無（vendorAcceptedAt）で絞る。
// これは我々がチェックする操作ではなく、業者が発注を承諾したかどうかの抽出条件。
type AcceptanceFilter = 'all' | 'confirmed' | 'pending';
const acceptance = computed<AcceptanceFilter>(() => (props.filters.acceptance as AcceptanceFilter | undefined) ?? 'pending');
const acceptanceOptions: { value: AcceptanceFilter; label: string }[] = [
    { value: 'all', label: '全て' },
    { value: 'confirmed', label: '承諾済' },
    { value: 'pending', label: '未完了' },
];
const showAcceptanceFilter = computed(() => props.mode === 'order-acceptance');
// acceptance は既定（pending＝現状維持の絞り込み）のとき URL から省く。
const acceptanceParam = (value: AcceptanceFilter): AcceptanceFilter | undefined => (value === 'pending' ? undefined : value);

// 請求月フィルタ（先月 / 当月 / 来月）。完了確認画面のみ。報告書提出日（業者承諾日、月末17:00締め）で絞る。
type BillingMonthFilter = 'last' | 'current' | 'next';
const billingMonth = computed<BillingMonthFilter>(() => (props.filters.billingMonth as BillingMonthFilter | undefined) ?? 'current');
const billingMonthOptions: { value: BillingMonthFilter; label: string }[] = [
    { value: 'last', label: '先月' },
    { value: 'current', label: '当月' },
    { value: 'next', label: '翌月' },
];
const showBillingMonthFilter = computed(() => config.value.isCompletionCheck);
// billingMonth は既定（current＝当月）のとき URL から省く。
const billingMonthParam = (value: BillingMonthFilter): BillingMonthFilter | undefined => (value === 'current' ? undefined : value);

// 絞り込み（物件名 / 項目名 / 見積先）。見積管理の QuotationFilterBar を流用。
const onSearch = (payload: ProjectFilters): void => {
    router.get(
        window.location.pathname,
        {
            keyword: payload.keyword || undefined,
            itemLabel: payload.itemLabel || undefined,
            vendor: payload.vendor || undefined,
            acceptance: acceptanceParam(acceptance.value),
            billingMonth: billingMonthParam(billingMonth.value),
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};
const goToPage = (pageNumber: number): void => {
    router.get(
        window.location.pathname,
        {
            keyword: props.filters.keyword || undefined,
            itemLabel: props.filters.itemLabel || undefined,
            vendor: props.filters.vendor || undefined,
            acceptance: acceptanceParam(acceptance.value),
            billingMonth: billingMonthParam(billingMonth.value),
            page: pageNumber,
        },
        { preserveState: true },
    );
};
// 承諾確認フィルタを切り替える（ページは1に戻す）。
const setAcceptance = (value: AcceptanceFilter): void => {
    if (value === acceptance.value) {
        return;
    }
    router.get(
        window.location.pathname,
        {
            keyword: props.filters.keyword || undefined,
            itemLabel: props.filters.itemLabel || undefined,
            vendor: props.filters.vendor || undefined,
            acceptance: acceptanceParam(value),
            billingMonth: billingMonthParam(billingMonth.value),
        },
        { preserveState: true, preserveScroll: true },
    );
};
// 請求月フィルタを切り替える（ページは1に戻す）。
const setBillingMonth = (value: BillingMonthFilter): void => {
    if (value === billingMonth.value) {
        return;
    }
    router.get(
        window.location.pathname,
        {
            keyword: props.filters.keyword || undefined,
            itemLabel: props.filters.itemLabel || undefined,
            vendor: props.filters.vendor || undefined,
            acceptance: acceptanceParam(acceptance.value),
            billingMonth: billingMonthParam(value),
        },
        { preserveState: true, preserveScroll: true },
    );
};

// 再通知（業者承諾確認画面）：業者へ承諾催促を通知する。
const renotify = (row: OrderDeliveryRow): void => {
    if (!props.renotifyUrl) {
        return;
    }
    router.post(props.renotifyUrl, { id: row.companyId }, { preserveScroll: true, preserveState: true, only: ['flash'] });
};

// ===== やり取り（チャット）：見積管理と同じ項目単位コメント。companyId=t_cost_quotations.id で流用。 =====
const page = usePage();
const myRole = computed<'manager' | 'staff'>(() => (page.props.auth?.user?.isEstimateManager ? 'manager' : 'staff'));
const chatOpen = ref(false);
const chatTarget = ref<OrderDeliveryRow | null>(null);
const chatBuilding = ref('');
const chatMessages = ref<QuotationChatMessage[]>([]);
const chatBody = ref('');
const chatLoading = ref(false);
const chatSending = ref(false);
const chatError = ref<string | null>(null);
const chatScroll = ref<HTMLElement | null>(null);
const chatFiles = ref<File[]>([]);
const chatDragOver = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const openFilePicker = (): void => fileInput.value?.click();
const addFiles = (list: FileList | null): void => {
    if (!list || list.length === 0) {
        return;
    }
    chatFiles.value = [...chatFiles.value, ...Array.from(list)];
};
const onFileInputChange = (event: Event): void => {
    const input = event.target as HTMLInputElement;
    addFiles(input.files);
    input.value = '';
};
const removeChatFile = (index: number): void => {
    chatFiles.value = chatFiles.value.filter((_, i) => i !== index);
};
const onChatDrop = (event: DragEvent): void => {
    chatDragOver.value = false;
    addFiles(event.dataTransfer?.files ?? null);
};
const canSendChat = computed(() => (chatBody.value.trim().length > 0 || chatFiles.value.length > 0) && !chatSending.value);
const formatFileSize = (bytes: number): string => {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
};
const xsrfToken = (): string =>
    decodeURIComponent(document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN='))?.split('=')[1] ?? '');
const scrollChatToBottom = (): void => {
    void nextTick(() => {
        if (chatScroll.value) {
            chatScroll.value.scrollTop = chatScroll.value.scrollHeight;
        }
    });
};
const openChat = async (row: OrderDeliveryRow, buildingName = ''): Promise<void> => {
    chatTarget.value = row;
    chatBuilding.value = buildingName;
    chatMessages.value = [];
    chatBody.value = '';
    chatFiles.value = [];
    chatError.value = null;
    chatOpen.value = true;
    row.unreadCount = 0;
    await fetchMessages();
};
const closeChat = (): void => {
    chatOpen.value = false;
    chatTarget.value = null;
    chatFiles.value = [];
    chatDragOver.value = false;
};
const fetchMessages = async (): Promise<void> => {
    const id = chatTarget.value?.companyId;
    if (id == null) {
        return;
    }
    chatLoading.value = true;
    try {
        const res = await fetch(quotationMessagesIndex(id).url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        const data = await res.json();
        chatMessages.value = data.messages ?? [];
        scrollChatToBottom();
    } catch {
        chatError.value = 'メッセージの取得に失敗しました。';
    } finally {
        chatLoading.value = false;
    }
};
const sendChat = async (): Promise<void> => {
    const id = chatTarget.value?.companyId;
    const body = chatBody.value.trim();
    if (id == null || chatSending.value || (!body && chatFiles.value.length === 0)) {
        return;
    }
    chatSending.value = true;
    chatError.value = null;
    try {
        const fd = new FormData();
        fd.append('body', body);
        chatFiles.value.forEach((file) => fd.append('files[]', file));
        const res = await fetch(quotationMessagesStore(id).url, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-XSRF-TOKEN': xsrfToken() },
            credentials: 'same-origin',
            body: fd,
        });
        if (!res.ok) {
            throw new Error('failed');
        }
        const data = await res.json();
        if (data.message) {
            chatMessages.value.push(data.message);
        }
        chatBody.value = '';
        chatFiles.value = [];
        if (chatTarget.value) {
            chatTarget.value.messageCount = (chatTarget.value.messageCount ?? 0) + 1;
            chatTarget.value.hasComments = true;
        }
        scrollChatToBottom();
    } catch {
        chatError.value = '送信に失敗しました。';
    } finally {
        chatSending.value = false;
    }
};
</script>

<template>
    <Head :title="`発注・納品管理 - ${config.title}`" />

    <div :class="rootClass">
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -left-20 -top-20 size-96 rounded-full bg-[#3f3f46]/30 blur-3xl"></div>
            <div class="absolute -top-10 right-10 size-80 rounded-full bg-[#c4a35b]/40 blur-3xl"></div>
            <div class="absolute left-1/3 top-1/3 size-112 rounded-full bg-[#52525b]/25 blur-3xl"></div>
            <div class="absolute -bottom-24 right-1/4 size-96 rounded-full bg-[#c4a35b]/20 blur-3xl"></div>
        </div>

        <div class="relative">
            <div class="sticky top-0 z-30 space-y-3 px-4 pb-3 pt-4 md:px-6" :class="stickyBgClass">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 transition-[padding]" :class="sidebarCollapsed ? 'md:pl-12' : ''">
                    <span aria-hidden="true" class="h-7 w-1.5 rounded-full bg-[#c4a35b]"></span>
                    <h1 class="text-2xl font-bold tracking-tight" :class="headingClass">{{ config.title }}</h1>
                    <span class="text-sm" :class="onGlassTextClass">状態：{{ config.statusLabel }}</span>
                    <span v-if="config.isCompletionCheck" class="text-xl font-bold text-red-600">
                        ※月末１７：００までに提出された完了報告書が当月請求の対象です。
                    </span>
                    <button
                        v-if="!config.isPerRowAction && !config.isCompletionCheck && !config.showAcceptedDate"
                        type="button"
                        :class="actionEnabled
                            ? 'h-9 rounded-xl border border-[#c4a35b] bg-[#c4a35b] px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-[#b3923f]'
                            : 'h-9 cursor-not-allowed rounded-xl border border-[#c4a35b]/40 bg-[#c4a35b]/10 px-4 text-sm font-semibold text-[#8a6a25]/60 backdrop-blur-md'"
                        :disabled="!actionEnabled"
                        :title="actionEnabled ? '' : config.hint"
                        @click="submitAction"
                    >
                        {{ form.processing ? config.processingLabel : config.actionLabel
                        }}<span v-if="!form.processing && selectedCount"> （{{ selectedCount }}）</span>
                    </button>
                </div>

                <!-- 完了確認のみ：請求月フィルタ（先月 / 当月 / 来月）。絞り込み検索より前に置く。 -->
                <div v-if="showBillingMonthFilter" class="flex items-center gap-2">
                    <span class="text-sm font-semibold" :class="onGlassTextClass">請求月</span>
                    <div class="inline-flex items-center gap-0.5 rounded-lg border border-primary/20 bg-white/70 p-0.5 backdrop-blur-md">
                        <button
                            v-for="opt in billingMonthOptions"
                            :key="opt.value"
                            type="button"
                            class="rounded-md px-3 py-1.5 text-sm font-bold transition"
                            :class="billingMonth === opt.value ? 'bg-[#c4a35b] text-white shadow-sm' : 'text-primary hover:bg-primary/10'"
                            @click="setBillingMonth(opt.value)"
                        >
                            {{ opt.label }}
                        </button>
                    </div>
                </div>

                <FilterBar :filters="filters" glass @search="onSearch" />
            </div>

            <div class="space-y-4 px-4 pb-6 pt-3 md:px-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" :class="pagerBtnClass" @click="toggleAllProjects">
                            {{ anyOpen ? '明細を全て閉じる' : '明細を全て開く' }}
                        </button>
                        <button v-if="!config.isPerRowAction && !config.isCompletionCheck && !config.showAcceptedDate" type="button" :class="pagerBtnClass" @click="toggleSelectAll">
                            {{ bulkAllSelected ? config.bulkClearLabel : config.bulkSelectLabel }}
                        </button>
                        <!-- 業者承諾確認のみ：承諾確認フィルタ（全て / 承諾済 / 未完了）。業者側の承諾有無で絞る。 -->
                        <div
                            v-if="showAcceptanceFilter"
                            class="inline-flex items-center gap-0.5 rounded-lg border border-primary/20 bg-white/70 p-0.5 backdrop-blur-md"
                        >
                            <button
                                v-for="opt in acceptanceOptions"
                                :key="opt.value"
                                type="button"
                                class="rounded-md px-3 py-1.5 text-sm font-bold transition"
                                :class="acceptance === opt.value ? 'bg-[#c4a35b] text-white shadow-sm' : 'text-primary hover:bg-primary/10'"
                                @click="setAcceptance(opt.value)"
                            >
                                {{ opt.label }}
                            </button>
                        </div>
                    </div>
                    <Pager :pagination="pagination" glass @change="goToPage" />
                </div>

                <OrderProjectCard
                    v-for="project in projects"
                    :key="project.id"
                    :project="project"
                    :config="config"
                    glass
                    :open="isOpen(project.id)"
                    :selected-keys="checkedKeys"
                    @toggle="toggle(project.id)"
                    @row-toggle="toggleRow"
                    @reject="openReject"
                    @cancel-request="openCancelRequest"
                    @open-invoice="openInvoice"
                    @open-chat="openChat"
                    @open-iframe="openIframe"
                />

                <div v-if="!projects.length" class="p-8 text-center" :class="[glassPanelClass, onGlassTextClass]">
                    対象の案件がありません。
                </div>

                <div class="flex justify-end pt-1">
                    <Pager :pagination="pagination" glass @change="goToPage" />
                </div>
            </div>
        </div>
    </div>

    <!-- やり取り（チャット）モーダル：見積管理と同じ。 -->
    <Teleport to="body">
        <div v-if="chatOpen" class="fixed inset-0 z-[90] flex items-center justify-center bg-black/50 p-4" @click.self="closeChat">
            <div
                class="relative flex h-[80vh] w-full max-w-lg flex-col overflow-hidden rounded-xl bg-white shadow-2xl"
                @dragenter.prevent="chatDragOver = true"
                @dragover.prevent="chatDragOver = true"
                @dragleave.prevent="chatDragOver = false"
                @drop.prevent="onChatDrop"
            >
                <div v-if="chatDragOver" class="pointer-events-none absolute inset-0 z-10 flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-[#c4a35b] bg-[#c4a35b]/10 backdrop-blur-sm">
                    <Paperclip class="size-8 text-[#c4a35b]" />
                    <p class="text-sm font-semibold text-[#8a6a25]">ここにファイルをドロップして添付</p>
                </div>
                <div class="flex items-start gap-2 border-b px-4 py-3">
                    <MessageSquare class="mt-0.5 size-5 text-[#c4a35b]" />
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-slate-800">コメント履歴</p>
                        <p v-if="chatBuilding" class="truncate text-[11px] text-slate-400">{{ chatBuilding }}</p>
                        <p class="truncate text-xs text-slate-500">{{ chatTarget?.itemName || '項目' }}</p>
                    </div>
                    <button type="button" class="flex size-8 items-center justify-center rounded-md text-slate-500 transition hover:bg-slate-100 hover:text-slate-800" title="閉じる" @click="closeChat">
                        <X class="size-5" />
                    </button>
                </div>
                <div ref="chatScroll" class="flex-1 space-y-3 overflow-y-auto bg-slate-50 px-4 py-4">
                    <p v-if="chatLoading" class="py-8 text-center text-sm text-slate-400">読み込み中…</p>
                    <p v-else-if="!chatMessages.length" class="py-8 text-center text-sm text-slate-400">まだコメントはありません。最初のコメントを残しましょう。</p>
                    <div v-for="m in chatMessages" :key="m.id" class="flex" :class="m.isMine ? 'justify-end' : 'justify-start'">
                        <div class="flex max-w-[80%] flex-col" :class="m.isMine ? 'items-end' : 'items-start'">
                            <div class="mb-1 flex flex-wrap items-center gap-1.5 text-[11px] text-slate-500" :class="m.isMine ? 'flex-row-reverse' : ''">
                                <span class="rounded px-1 py-0.5 font-bold leading-none" :class="m.senderRole === 'manager' ? 'bg-[#c4a35b]/15 text-[#8a6a25]' : 'bg-slate-200 text-slate-600'">{{ m.senderRole === 'manager' ? '部長' : '部下' }}</span>
                                <span class="font-semibold text-slate-700">{{ m.senderName }}</span>
                                <span v-if="m.isMine" class="rounded bg-slate-100 px-1 py-0.5 leading-none text-slate-400">自分</span>
                            </div>
                            <div class="rounded-2xl px-3 py-2 shadow-sm" :class="m.isMine ? 'rounded-br-sm border border-[#c4a35b] bg-[#c4a35b] text-white' : 'rounded-bl-sm border border-slate-200 bg-white text-slate-800'">
                                <div v-if="m.body" class="whitespace-pre-wrap break-words text-sm">{{ m.body }}</div>
                                <div v-if="m.files.length" class="mt-2 flex flex-wrap gap-2" :class="m.isMine ? 'justify-end' : ''">
                                    <template v-for="f in m.files" :key="f.id">
                                        <a v-if="f.isImage" :href="f.downloadUrl" :download="f.name" :title="`${f.name} をダウンロード`" class="block overflow-hidden rounded-lg border border-slate-200 transition hover:border-[#c4a35b]">
                                            <img :src="f.url" :alt="f.name" class="size-24 object-cover" />
                                        </a>
                                        <a v-else :href="f.downloadUrl" :download="f.name" :title="`${f.name} をダウンロード`" class="inline-flex max-w-[220px] items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs text-slate-700 transition hover:border-[#c4a35b] hover:bg-[#c4a35b]/10">
                                            <FileText class="size-4 shrink-0 text-[#c4a35b]" />
                                            <span class="truncate">{{ f.name }}</span>
                                            <span class="shrink-0 text-slate-400">{{ formatFileSize(f.size) }}</span>
                                        </a>
                                    </template>
                                </div>
                            </div>
                            <span class="mt-1 text-[10px] text-slate-400">{{ m.createdAt }}</span>
                        </div>
                    </div>
                </div>
                <div class="border-t px-3 py-3">
                    <p v-if="chatError" class="mb-1 text-xs text-red-600">{{ chatError }}</p>
                    <div v-if="chatFiles.length" class="mb-2 flex flex-wrap gap-1.5">
                        <span v-for="(f, i) in chatFiles" :key="`${f.name}-${i}`" class="inline-flex max-w-[220px] items-center gap-1.5 rounded-lg border border-[#c4a35b]/40 bg-[#c4a35b]/10 px-2 py-1 text-xs text-[#8a6a25]">
                            <FileText class="size-3.5 shrink-0" />
                            <span class="truncate">{{ f.name }}</span>
                            <span class="shrink-0 text-[#8a6a25]/60">{{ formatFileSize(f.size) }}</span>
                            <button type="button" class="shrink-0 rounded p-0.5 text-[#8a6a25]/70 transition hover:bg-[#c4a35b]/20 hover:text-[#8a6a25]" title="添付を取り消す" @click="removeChatFile(i)">
                                <X class="size-3.5" />
                            </button>
                        </span>
                    </div>
                    <div class="flex items-end gap-2">
                        <input ref="fileInput" type="file" multiple class="hidden" @change="onFileInputChange" />
                        <button type="button" class="flex size-10 shrink-0 items-center justify-center rounded-lg border border-slate-300 text-slate-500 transition hover:border-[#c4a35b] hover:bg-[#c4a35b]/10 hover:text-[#8a6a25]" title="ファイルを添付" @click="openFilePicker">
                            <Paperclip class="size-5" />
                        </button>
                        <textarea v-model="chatBody" rows="2" class="flex-1 resize-none rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#c4a35b] focus:outline-none focus:ring-2 focus:ring-[#c4a35b]/30" placeholder="コメントを入力（Enterで改行）" @keydown.enter.exact.prevent="sendChat" />
                        <button type="button" class="flex h-10 shrink-0 items-center gap-1.5 rounded-lg px-3 text-sm font-semibold text-white transition" :class="canSendChat ? 'bg-[#c4a35b] hover:bg-[#b3923f]' : 'cursor-not-allowed bg-[#c4a35b]/40'" :disabled="!canSendChat" @click="sendChat">
                            <Send class="size-4" />{{ chatSending ? '送信中' : 'コメントする' }}
                        </button>
                    </div>
                    <p class="mt-1 text-[11px] text-slate-400">あなたは「{{ myRole === 'manager' ? '部長' : '部下' }}」としてコメントします。ファイルはドラッグ&ドロップでも添付できます。</p>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- 否認モーダル -->
    <Teleport to="body">
        <div v-if="rejectModalOpen" class="fixed inset-0 z-[90] flex items-center justify-center bg-black/50 p-4" @click.self="closeReject">
            <div class="w-full max-w-md overflow-hidden rounded-xl bg-white shadow-2xl">
                <div class="flex items-center gap-2 border-b px-4 py-3">
                    <Ban class="size-5 text-red-600" />
                    <span class="text-sm font-semibold text-slate-800">否認（差し戻し）</span>
                </div>
                <div class="space-y-3 px-4 py-4">
                    <p class="text-sm text-slate-600">
                        見積先「<span class="font-semibold text-slate-800">{{ rejectTarget?.vendorName }}</span>」を否認し、前段へ差し戻します。
                    </p>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-500">否認理由<span class="text-red-500">（必須）</span></label>
                        <textarea v-model="rejectForm.reason" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#c4a35b] focus:outline-none focus:ring-2 focus:ring-[#c4a35b]/30" placeholder="差し戻しの理由を入力してください" />
                        <p v-if="rejectForm.errors.reason" class="mt-1 text-xs text-red-600">{{ rejectForm.errors.reason }}</p>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t px-4 py-3">
                    <button type="button" class="h-9 rounded-md border bg-card px-4 text-sm font-semibold text-muted-foreground transition hover:bg-accent" @click="closeReject">キャンセル</button>
                    <button type="button" class="h-9 rounded-md px-4 text-sm font-semibold text-white transition" :class="rejectForm.reason.trim() && !rejectForm.processing ? 'bg-red-600 hover:bg-red-700' : 'cursor-not-allowed bg-red-300'" :disabled="!rejectForm.reason.trim() || rejectForm.processing" @click="submitReject">
                        {{ rejectForm.processing ? '否認中…' : '否認する' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- 取消モーダル（理由必須）：業者承諾確認＝取消申請 / 発注取消承認＝取消承認。 -->
    <Teleport to="body">
        <div v-if="cancelModalOpen" class="fixed inset-0 z-[90] flex items-center justify-center bg-black/50 p-4" @click.self="closeCancelRequest">
            <div class="w-full max-w-md overflow-hidden rounded-xl bg-white shadow-2xl">
                <div class="flex items-center gap-2 border-b px-4 py-3">
                    <XCircle class="size-5 text-[#8a6a25]" />
                    <span class="text-sm font-semibold text-slate-800">{{ cancelModalTitle }}</span>
                </div>
                <div class="space-y-3 px-4 py-4">
                    <p class="text-sm text-slate-600">
                        見積先「<span class="font-semibold text-slate-800">{{ cancelTarget?.vendorName }}</span>」の発注取消を{{ isCancelApprovalMode ? '承認' : '申請' }}します。
                    </p>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-500">{{ cancelModalReasonLabel }}<span class="text-red-500">（必須）</span></label>
                        <textarea v-model="cancelForm.reason" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#c4a35b] focus:outline-none focus:ring-2 focus:ring-[#c4a35b]/30" :placeholder="`${cancelModalTitle}の理由を入力してください`" />
                        <p v-if="cancelForm.errors.reason" class="mt-1 text-xs text-red-600">{{ cancelForm.errors.reason }}</p>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t px-4 py-3">
                    <button type="button" class="h-9 rounded-md border bg-card px-4 text-sm font-semibold text-muted-foreground transition hover:bg-accent" @click="closeCancelRequest">キャンセル</button>
                    <button
                        type="button"
                        class="h-9 rounded-md px-4 text-sm font-semibold text-white transition"
                        :class="cancelForm.reason.trim() && !cancelForm.processing ? 'bg-[#c4a35b] hover:bg-[#b3923f]' : 'cursor-not-allowed bg-[#c4a35b]/40'"
                        :disabled="!cancelForm.reason.trim() || cancelForm.processing"
                        @click="submitCancelRequest"
                    >
                        {{ cancelForm.processing ? cancelModalProcessingLabel : cancelModalTitle }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- 請求情報モーダル（完了確認画面：請求日クリック）。読み取り専用の詳細表示のみ。 -->
    <Teleport to="body">
        <div v-if="invoiceModalOpen" class="fixed inset-0 z-[90] flex items-center justify-center bg-black/50 p-4" @click.self="closeInvoice">
            <div class="w-full max-w-md overflow-hidden rounded-xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b px-4 py-3">
                    <div class="flex items-center gap-2">
                        <ReceiptText class="size-5 text-[#c4a35b]" />
                        <span class="text-sm font-semibold text-slate-800">請求情報</span>
                    </div>
                    <button type="button" class="flex size-8 items-center justify-center rounded-md text-slate-500 transition hover:bg-slate-100 hover:text-slate-800" title="閉じる" @click="closeInvoice">
                        <X class="size-5" />
                    </button>
                </div>
                <dl class="space-y-3 px-4 py-4 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">見積先</dt>
                        <dd class="font-semibold text-slate-800">{{ invoiceTarget?.vendorName }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">項目</dt>
                        <dd class="font-semibold text-slate-800">{{ invoiceTarget?.itemName }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">請求金額（税抜）</dt>
                        <dd class="font-semibold tabular-nums text-slate-800">
                            {{ invoiceTarget?.invoiceAmount != null ? `¥${invoiceTarget.invoiceAmount.toLocaleString()}` : '—' }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">請求書作成日</dt>
                        <dd class="tabular-nums text-slate-800">{{ invoiceTarget?.invoiceSubmittedAt ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">承認状況</dt>
                        <dd class="text-slate-800">
                            <span v-if="invoiceTarget?.invoiceStatus === 'APPROVED'" class="inline-flex items-center gap-1 font-semibold text-emerald-600">
                                <CheckCircle2 class="size-4" />承認済み
                            </span>
                            <span v-else class="font-semibold text-amber-700">部長承認待ち</span>
                        </dd>
                    </div>
                    <div v-if="invoiceTarget?.invoiceApprovedAt" class="flex items-center justify-between">
                        <dt class="text-slate-500">承認日</dt>
                        <dd class="tabular-nums text-slate-800">{{ invoiceTarget.invoiceApprovedAt }}</dd>
                    </div>
                </dl>
                <div class="flex justify-end border-t px-4 py-3">
                    <button type="button" class="h-9 rounded-md border bg-card px-4 text-sm font-semibold text-muted-foreground transition hover:bg-accent" @click="closeInvoice">閉じる</button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- 発注書（felix_total の発注書確認画面）を開く iframe モーダル。 -->
    <Teleport to="body">
        <div v-if="iframeOpen" class="fixed inset-0 z-[90] flex items-center justify-center bg-black/50 p-4" @click.self="closeIframe">
            <div class="relative flex h-[88vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b px-4 py-2.5">
                    <span class="text-sm font-semibold text-slate-800">{{ iframeTitle }}</span>
                    <button type="button" class="flex size-8 items-center justify-center rounded-md text-slate-500 transition hover:bg-slate-100 hover:text-slate-800" title="閉じる" @click="closeIframe">
                        <X class="size-5" />
                    </button>
                </div>
                <iframe v-if="iframeUrl" :src="iframeUrl" class="w-full flex-1" :title="iframeTitle" />
                <div v-else class="flex flex-1 items-center justify-center px-6 text-center text-sm text-slate-500">
                    発注書の URL が未設定です（移行元データが無い物件の可能性があります）。
                </div>
            </div>
        </div>
    </Teleport>
</template>

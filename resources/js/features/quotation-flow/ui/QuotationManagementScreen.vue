<script setup lang="ts">
// 見積管理の共通画面コンテナ（状態統括）。見積り依頼 / 業者選定 … で共用し、mode で出し分ける。
// 子: QuotationFilterBar（絞り込み）/ QuotationPager（ページ送り）/ QuotationProjectCard（明細）。
// 配色は useFelixTheme に集約。glass=true は「明細は白／それ以外はリキッドグラス（FELIX 紺＋金）」。
// 操作は mode（QUOTATION_MODE_CONFIG）で出し分ける：
// - 見積依頼（quote-request）：未依頼行をチェックで選び、ヘッダー送信で相見積依頼を記録。
// - 業者選定（vendor-selection）：サーバの採用状態を初期値にボタンでトグル（業者の選び替え）。
// - 部長承認 / 取消申請 / 取消承認（pick-button）：未処理行をボタンで選び、ヘッダー確定で送信。
// いずれも成功時はサーバが back() → 一覧再読込 + flash メッセージ。
// ※ 業者へのメール通知（felix_total のトークン発行＋送信）は本フェーズ未対応。
import { computed, inject, nextTick, reactive, ref, type Ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { X, Ban, MessageSquare, Send, Paperclip, FileText } from 'lucide-vue-next';
import type { RouteDefinition } from '@/shared/api/wayfinder';
import { send as sendQuoteRequestRoute } from '@/shared/api/routes/quotation-management/quote-request';
import { confirm as confirmVendorSelectionRoute, provisional as provisionalRoute } from '@/shared/api/routes/quotation-management/vendor-selection';
import { confirm as confirmManagerApprovalRoute, reject as rejectManagerApprovalRoute } from '@/shared/api/routes/quotation-management/manager-approval';
import { confirm as confirmCancelRequestRoute } from '@/shared/api/routes/quotation-management/cancel-request';
import { confirm as confirmCancelApprovalRoute } from '@/shared/api/routes/quotation-management/cancel-approval';
import { index as quotationMessagesIndex, store as quotationMessagesStore } from '@/shared/api/routes/quotation-management/quotation-messages';
import { SIDEBAR_COLLAPSED } from '@/shared/ui/layouts';
import { FilterBar } from '@/shared/ui/filter-bar';
import { Pager } from '@/shared/ui/pager';
import QuotationProjectCard from './QuotationProjectCard.vue';
import { useFelixTheme } from '@/shared/lib/felix-theme';
import { quotationRowKey, QUOTATION_MODE_CONFIG } from '../model/quotation-mode';
import type { QuotationChatMessage } from '@/shared/api';
import type {
    QuotationManagementFilters,
    QuotationManagementMode,
    QuotationManagementPagination,
    QuotationManagementProject,
    QuotationManagementRow,
} from '../model/quotation';

const props = defineProps<{
    title: string;
    statusLabel: string;
    mode: QuotationManagementMode;
    actionLabel?: string | null;
    glass?: boolean;
    projects: QuotationManagementProject[];
    pagination: QuotationManagementPagination;
    filters: QuotationManagementFilters;
}>();

const isThemed = computed(() => props.glass === true);
const config = computed(() => QUOTATION_MODE_CONFIG[props.mode]);
// toggle = 業者選定（サーバ状態を初期値にトグル）／pick = チェック/ボタンで未処理行を選ぶ。
const isToggleMode = computed(() => config.value.kind === 'toggle-button');
const { rootClass, stickyBgClass, glassPanelClass, headingClass, onGlassTextClass, pagerBtnClass } =
    useFelixTheme(isThemed);

// サイドバー折りたたみ時は左上に再オープンボタンが浮くため、タイトル行の左に余白を確保する
// （AppLayout から provide。単独利用時は false 既定で余白なし）。
const sidebarCollapsed = inject(SIDEBAR_COLLAPSED, ref(false));

// mode → 送信先ルート。
const actionRoute = computed<RouteDefinition<'post'>>(() => {
    switch (props.mode) {
        case 'vendor-selection':
            return confirmVendorSelectionRoute();
        case 'manager-approval':
            return confirmManagerApprovalRoute();
        case 'cancel-request':
            return confirmCancelRequestRoute();
        case 'cancel-approval':
            return confirmCancelApprovalRoute();
        default:
            return sendQuoteRequestRoute();
    }
});

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

const allRows = computed<QuotationManagementRow[]>(() => props.projects.flatMap((p) => p.rows));
// 行が「処理済み」か（mode ごとのサーバ側フラグ。例: 見積依頼=requested / 部長承認=approved）。
// reselectable（見積依頼）はロックせず、依頼済みでも選択・再依頼できる。
const isApplied = (row: QuotationManagementRow): boolean =>
    !config.value.reselectable && row[config.value.appliedKey] === true;

// pick モード（見積依頼 / 部長承認 / 取消申請 / 取消承認）の選択。
// 1つの真実は checked（行キー→真偽）。未処理行だけを選べる。表示用に Set を派生。
const checked = reactive<Record<string, boolean>>({});
const checkedKeys = computed(() => new Set(Object.keys(checked).filter((k) => checked[k])));
const toggleRow = (row: QuotationManagementRow): void => {
    // operable = 処理フロー J列の対象ステータスか。false の行は一覧に出すが操作させない（K列）。
    if (row.companyId == null || isApplied(row) || !row.operable) {
        return;
    }
    const key = quotationRowKey(row);
    checked[key] = !checked[key];
};
// 選べるのは「見積先（業者）が紐づき、かつ未処理」の行だけ（処理済みは再操作不可）。
const selectableRows = computed<QuotationManagementRow[]>(() =>
    allRows.value.filter((r) => r.companyId != null && !isApplied(r) && r.operable),
);
const anyChecked = computed(() => checkedKeys.value.size > 0);

// toggle モード（業者選定）の選定状態。サーバの選定フラグ（appliedKey=selected）を
// 初期値に、ローカル上書き（押下）で管理する。
// 送信成功後はリロードされるため上書きはクリアする。
const selectionOverride = reactive<Record<string, boolean>>({});
const serverSelected = (row: QuotationManagementRow): boolean => row[config.value.appliedKey] === true;
// 見積回答あり（相見積に金額が入っている）。回答が無い業者は選定不可。
const hasQuoteAnswer = (row: QuotationManagementRow): boolean => row.quotePrice != null && row.quotePrice > 0;
const isRowSelected = (row: QuotationManagementRow): boolean => {
    const key = quotationRowKey(row);
    return key in selectionOverride ? selectionOverride[key] : serverSelected(row);
};
const toggleSelect = (row: QuotationManagementRow): void => {
    if (row.companyId == null || !hasQuoteAnswer(row) || !row.operable) {
        return;
    }
    const key = quotationRowKey(row);
    selectionOverride[key] = !isRowSelected(row);
};
// 現在「選定済（押下）」の行キー集合。明細カードのボタン表示に使う。
const vendorSelectedKeys = computed(
    () => new Set(allRows.value.filter((r) => r.companyId != null && isRowSelected(r)).map((r) => quotationRowKey(r))),
);
// サーバ状態から1つでも変更（押下）があるか。確定ボタンの活性判定に使う。
const vendorDirty = computed(() =>
    allRows.value.some((r) => {
        const key = quotationRowKey(r);
        return key in selectionOverride && selectionOverride[key] !== serverSelected(r);
    }),
);

// 明細カードへ渡す「有効な行キー集合」と行トグル（モードで出し分け）。
const activeKeys = computed(() => (isToggleMode.value ? vendorSelectedKeys.value : checkedKeys.value));
const onRowToggle = (row: QuotationManagementRow): void => {
    if (isToggleMode.value) {
        toggleSelect(row);
    } else {
        toggleRow(row);
    }
};
const selectedCount = computed(() => (isToggleMode.value ? vendorSelectedKeys.value.size : checkedKeys.value.size));

// 仮選定（見積依頼画面）：FELIXが依頼したい業者の印。
// 表示はサーバ値(row.provisional=is_drafted)＋ローカル上書き。チェック時に即時保存する。
// 保存は既存の vendor-selection/provisional（is_drafted を書くだけ）を流用する。
const provisionalOverride = reactive<Record<string, boolean>>({});
const provisionalKeys = computed(() => {
    const set = new Set<string>();
    for (const project of props.projects) {
        for (const row of project.rows) {
            const key = quotationRowKey(row);
            const on = key in provisionalOverride ? provisionalOverride[key] : row.provisional === true;
            if (on) {
                set.add(key);
            }
        }
    }
    return set;
});
const toggleProvisional = (row: QuotationManagementRow): void => {
    if (row.companyId == null) {
        return;
    }
    const key = quotationRowKey(row);
    const current = provisionalKeys.value.has(key);
    const next = !current;
    provisionalOverride[key] = next; // 楽観的に反映
    // is_drafted をサーバ保存。成功/失敗は flash で表示。失敗時は元に戻す。
    router.post(
        provisionalRoute().url,
        { companyId: row.companyId, drafted: next },
        {
            preserveScroll: true,
            preserveState: true,
            only: ['flash'],
            onError: () => {
                provisionalOverride[key] = current;
            },
        },
    );
};

// 一覧の表示切替フィルタ（見積依頼画面）。クライアント側で行を絞り込む。
// - 仮選定のみ表示：チェックした仮選定の行だけ（仮選定はローカル状態）。
// - 未依頼のみ表示：まだ見積依頼を送っていない行だけ（送信回数 0 = requested=false）。
const provisionalOnly = ref(false);
// 業者選定：既に選定済み（未申請でない）行だけに絞る。初期 OFF。
const selectedOnly = ref(false);
// 部長承認：未承認（この画面で承認できる行＝operable）だけに絞る。初期 ON。
const unapprovedOnly = ref(props.mode === 'manager-approval');
// 見積依頼画面は初期表示で「未依頼のみ表示」をチェック済みにする（未依頼の見積先から着手できるように）。
const unrequestedOnly = ref(props.mode === 'quote-request');
const displayProjects = computed<QuotationManagementProject[]>(() => {
    const rowFilters: ((row: QuotationManagementRow) => boolean)[] = [];
    if (provisionalOnly.value) {
        rowFilters.push((r) => provisionalKeys.value.has(quotationRowKey(r)));
    }
    if (unrequestedOnly.value) {
        rowFilters.push((r) => r.sendCount === 0);
    }
    if (selectedOnly.value) {
        rowFilters.push((r) => r.selected);
    }
    if (unapprovedOnly.value) {
        rowFilters.push((r) => r.operable);
    }
    if (rowFilters.length === 0) {
        return props.projects;
    }
    return props.projects
        .map((p) => ({ ...p, rows: p.rows.filter((r) => rowFilters.every((f) => f(r))) }))
        .filter((p) => p.rows.length > 0);
});

// felix_total を開く汎用 iframe モーダル（見積先の詳細 / 業者追加 など）。
// 明細カードから { url, title } を受け取って開く。閉じたら一覧を再取得して反映する。
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
    // iframe で見積先の追加・編集をした可能性があるため、一覧を再取得して反映する。
    // （renew は felix_total と同じ DB を読むので、再取得すれば変更が出る。
    //  reload はスクロール位置・ローカル状態を維持する）
    // flash も併せて取り直す。部分リロードで返却しないと前回値が引き継がれ、
    // 直前の送信成功トーストが再表示されてしまう。
    router.reload({ only: ['projects', 'pagination', 'flash'] });
};

// 請求先行（billingTarget・見積依頼画面のみ）：チェック不要、押下で即座に単体で見積送信する。
const billingSendForm = useForm<{ companyIds: number[] }>({ companyIds: [] });
const submitBillingSend = (row: QuotationManagementRow): void => {
    if (row.companyId == null || billingSendForm.processing) {
        return;
    }
    billingSendForm.companyIds = [row.companyId];
    billingSendForm.submit(sendQuoteRequestRoute(), { preserveScroll: true });
};

// 一括「全て選択」（全モード共通）。
// toggle（業者選定）= 見積回答ありの業者行、pick（見積依頼 / 部長承認 / 取消申請 / 取消承認）= 未処理の業者行が対象。
const bulkSelectableRows = computed<QuotationManagementRow[]>(() =>
    isToggleMode.value
        ? allRows.value.filter((r) => r.companyId != null && hasQuoteAnswer(r) && r.operable)
        : selectableRows.value,
);
// 対象行がすべて選択状態か（ボタン表示名の出し分けに使う）。
const bulkAllSelected = computed(() => {
    const rows = bulkSelectableRows.value;
    if (rows.length === 0) {
        return false;
    }
    return isToggleMode.value
        ? rows.every((r) => isRowSelected(r))
        : rows.every((r) => checkedKeys.value.has(quotationRowKey(r)));
});
// 全て選択 / 全て解除（現在の状態を反転）。
const toggleSelectAll = (): void => {
    const select = !bulkAllSelected.value;
    if (isToggleMode.value) {
        bulkSelectableRows.value.forEach((r) => {
            selectionOverride[quotationRowKey(r)] = select;
        });
    } else {
        bulkSelectableRows.value.forEach((r) => {
            checked[quotationRowKey(r)] = select;
        });
    }
};

// 送信は Inertia useForm（processing 統合）+ Wayfinder アクションで行う。
// reason は取消申請 / 取消承認 のみ使用（それ以外の送信では空のまま・サーバは無視）。
const form = useForm<{ companyIds: number[]; reason: string }>({ companyIds: [], reason: '' });

// 取消申請 / 取消承認 は「1レコードごとに理由入力モーダル → OK で単体実行」にする（否認と同様）。
// これらの画面ではヘッダーの一括アクションボタン・一括選択は出さない。
const isPerRowAction = computed(() => props.mode === 'cancel-request' || props.mode === 'cancel-approval');
const actionModalOpen = ref(false);
const actionTarget = ref<QuotationManagementRow | null>(null);
const closeActionModal = (): void => {
    actionModalOpen.value = false;
    actionTarget.value = null;
};
// 行の取消申請 / 取消承認ボタン：対象1件で理由モーダルを開く。
const openRowAction = (row: QuotationManagementRow): void => {
    if (row.companyId == null) {
        return;
    }
    form.companyIds = [row.companyId];
    form.reason = '';
    form.clearErrors();
    actionTarget.value = row;
    actionModalOpen.value = true;
};

// 主役アクションの活性判定（金ベタ＝有効 / 淡色＝無効）。
// pick=チェックあり / 業者選定=サーバ状態からの変更（押下）あり、で活性化する。
const actionEnabled = computed(() => {
    if (form.processing) {
        return false;
    }
    return isToggleMode.value ? vendorDirty.value && vendorSelectedKeys.value.size > 0 : anyChecked.value;
});

// 実際の送信処理（reason は取消系のみ設定済み）。成功で選択状態とモーダルをクリア。
const performActionSubmit = (): void => {
    form.submit(actionRoute.value, {
        preserveScroll: true,
        onSuccess: () => {
            // 処理済みは再読込後の一覧に反映される。ローカルの選択/選定状態はクリアする。
            Object.keys(checked).forEach((key) => delete checked[key]);
            Object.keys(selectionOverride).forEach((key) => delete selectionOverride[key]);
            closeActionModal();
        },
    });
};
// ヘッダーの一括アクション（見積依頼 / 業者選定 / 部長承認）。取消系はヘッダーに出さない。
const submitAction = (): void => {
    if (!actionEnabled.value) {
        return;
    }
    const keys = isToggleMode.value ? vendorSelectedKeys.value : checkedKeys.value;
    const companyIds = allRows.value
        .filter((row) => row.companyId != null && keys.has(quotationRowKey(row)))
        .map((row) => row.companyId as number);
    if (companyIds.length === 0) {
        return;
    }
    form.companyIds = companyIds;
    form.reason = '';
    performActionSubmit();
};
// 取消申請 / 取消承認：理由モーダルの OK。理由必須。
const submitActionWithReason = (): void => {
    if (!form.reason.trim() || form.processing) {
        return;
    }
    performActionSubmit();
};
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

// 否認（部長承認画面）：理由入力モーダル → 業者選定へ差し戻し（approval_status を DRAFT に戻す）。
const rejectForm = useForm<{ companyId: number | null; reason: string }>({ companyId: null, reason: '' });
const rejectTarget = ref<QuotationManagementRow | null>(null);
const rejectModalOpen = ref(false);
const openReject = (row: QuotationManagementRow): void => {
    if (row.companyId == null) {
        return;
    }
    rejectForm.reset();
    rejectForm.clearErrors();
    rejectForm.companyId = row.companyId;
    rejectTarget.value = row;
    rejectModalOpen.value = true;
};
const closeReject = (): void => {
    rejectModalOpen.value = false;
    rejectTarget.value = null;
};
const submitReject = (): void => {
    if (!rejectForm.reason.trim() || rejectForm.processing) {
        return;
    }
    rejectForm.post(rejectManagerApprovalRoute().url, {
        preserveScroll: true,
        onSuccess: () => closeReject(),
    });
};

// やり取り（チャット）：見積先（companyId=t_cost_quotations.id）単位のスレッド。
// 業者選定（部下=staff）と部長承認（部長=manager）が同じ見積についてコメントを交わす。
const page = usePage();
const myRole = computed<'manager' | 'staff'>(() => (page.props.auth?.user?.isEstimateManager ? 'manager' : 'staff'));
const chatOpen = ref(false);
const chatTarget = ref<QuotationManagementRow | null>(null);
const chatBuilding = ref('');
const chatMessages = ref<QuotationChatMessage[]>([]);
const chatBody = ref('');
const chatLoading = ref(false);
const chatSending = ref(false);
const chatError = ref<string | null>(null);
const chatScroll = ref<HTMLElement | null>(null);

// 添付ファイル（送信前の保留リスト）。ドラッグ&ドロップ／ファイル選択で追加する。
const chatFiles = ref<File[]>([]);
const chatDragOver = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const openFilePicker = (): void => fileInput.value?.click();
// 添付の制約（06_添付ファイル_詳細設計 §1・§2）。サーバ側 StoreQuotationMessageRequest と一致させる。
const ALLOWED_EXTENSIONS = [
    'jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif',
    'pdf', 'txt',
    'xlsx', 'xls', 'docx', 'doc', 'pptx', 'ppt', 'csv',
    'dwg', 'dxf', 'jww', 'jwc', 'sfc', 'p21',
    'zip',
];
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
const MAX_FILES = 5;

// アップロード前チェック：拡張子ホワイトリスト外・10MB超・5件超をこの場で弾いてメッセージ表示する。
const addFiles = (list: FileList | null): void => {
    if (!list || list.length === 0) {
        return;
    }

    const rejected: string[] = [];
    const accepted: File[] = [];
    for (const file of Array.from(list)) {
        const ext = file.name.includes('.') ? (file.name.split('.').pop() ?? '').toLowerCase() : '';
        if (!ALLOWED_EXTENSIONS.includes(ext)) {
            rejected.push(`${file.name}（非対応の形式）`);
            continue;
        }
        if (file.size > MAX_FILE_SIZE) {
            rejected.push(`${file.name}（10MB超）`);
            continue;
        }
        accepted.push(file);
    }

    const room = Math.max(0, MAX_FILES - chatFiles.value.length);
    const toAdd = accepted.slice(0, room);
    if (toAdd.length > 0) {
        chatFiles.value = [...chatFiles.value, ...toAdd];
    }

    const errors: string[] = [];
    if (rejected.length > 0) {
        errors.push(`次のファイルは添付できません: ${rejected.join('、')}`);
    }
    if (accepted.length > toAdd.length) {
        errors.push(`添付は${MAX_FILES}件までです。`);
    }
    if (errors.length > 0) {
        chatError.value = errors.join(' ');
    }
};
const onFileInputChange = (event: Event): void => {
    const input = event.target as HTMLInputElement;
    addFiles(input.files);
    input.value = ''; // 同じファイルを続けて選べるようクリア。
};
const removeChatFile = (index: number): void => {
    chatFiles.value = chatFiles.value.filter((_, i) => i !== index);
};
const onChatDrop = (event: DragEvent): void => {
    chatDragOver.value = false;
    addFiles(event.dataTransfer?.files ?? null);
};
// 送信可否：本文か添付のいずれかがあり、送信中でないこと。
const canSendChat = computed(() => (chatBody.value.trim().length > 0 || chatFiles.value.length > 0) && !chatSending.value);
const formatFileSize = (bytes: number): string => {
    if (bytes < 1024) {
        return `${bytes} B`;
    }
    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }
    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
};

// CSRF（axios 未導入のため XSRF-TOKEN クッキーを fetch ヘッダへ手当てする）。
const xsrfToken = (): string =>
    decodeURIComponent(document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN='))?.split('=')[1] ?? '');

const scrollChatToBottom = (): void => {
    void nextTick(() => {
        if (chatScroll.value) {
            chatScroll.value.scrollTop = chatScroll.value.scrollHeight;
        }
    });
};
const openChat = async (row: QuotationManagementRow, buildingName = ''): Promise<void> => {
    if (row.companyId == null) {
        return;
    }
    chatTarget.value = row;
    chatBuilding.value = buildingName;
    chatMessages.value = [];
    chatBody.value = '';
    chatFiles.value = [];
    chatError.value = null;
    chatOpen.value = true;
    // 開いた時点で既読化される（GET index でポインタ更新）。一覧の未読バッジを即時クリア。
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
        const res = await fetch(quotationMessagesIndex(id).url, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
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
    // 本文・添付いずれか必須。
    if (id == null || chatSending.value || (!body && chatFiles.value.length === 0)) {
        return;
    }
    chatSending.value = true;
    chatError.value = null;
    try {
        // 添付を含めるため multipart/form-data で送る（Content-Type はブラウザに任せる）。
        const form = new FormData();
        form.append('body', body);
        chatFiles.value.forEach((file) => form.append('files[]', file));
        const res = await fetch(quotationMessagesStore(id).url, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-XSRF-TOKEN': xsrfToken() },
            credentials: 'same-origin',
            body: form,
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
        // 一覧の「やり取り」件数バッジ・コメント有無を楽観的に更新（ボタンを選定色へ）。
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

// コメントのやり取りの有無フィルタ（全画面共通・サーバ駆動）。現在値はサーバの filters から（既定=全て）。
type CommentFilter = 'all' | 'has' | 'none';
const commentFilter = computed<CommentFilter>(() => props.filters.comment ?? 'all');
const commentFilterOptions: { value: CommentFilter; label: string }[] = [
    { value: 'all', label: '全て' },
    { value: 'has', label: 'コメントあり' },
    { value: 'none', label: 'コメントなし' },
];
// comment は既定（all）のとき URL から省く。
const commentParam = (value: CommentFilter): CommentFilter | undefined => (value === 'all' ? undefined : value);

// 回答状態フィルタ（全て / 回答あり / 回答なし）。相見積額（最新の相見積履歴）の有無で絞る。
// 業者選定・見積依頼で使用。現在値はサーバの filters から（既定=全て）。
type AnswerFilter = 'all' | 'answered' | 'unanswered';
const answer = computed<AnswerFilter>(() => props.filters.answer ?? 'all');
// 見積依頼・業者選定とも「見積回答あり/なし」のラベルにする。
const answerOptions: { value: AnswerFilter; label: string }[] = [
    { value: 'all', label: '全て' },
    { value: 'answered', label: '見積回答あり' },
    { value: 'unanswered', label: '見積回答なし' },
];
// この回答状態フィルタを出す画面（相見積額の有無で絞れる画面）。
// 回答状態フィルタを出す画面（処理フロー I列「全て・見積回答あり・見積回答なし」）。
const showAnswerFilter = computed(() =>
    ['quote-request', 'vendor-selection', 'manager-approval'].includes(props.mode),
);
// 仮選定のみ表示を出す画面（同上）。
const showProvisionalFilter = computed(() =>
    ['quote-request', 'vendor-selection', 'manager-approval'].includes(props.mode),
);
// answer は既定（all）のとき URL から省く。
const answerParam = (value: AnswerFilter): AnswerFilter | undefined => (value === 'all' ? undefined : value);
const onSearch = (payload: QuotationManagementFilters): void => {
    router.get(
        window.location.pathname,
        {
            keyword: payload.keyword || undefined,
            itemLabel: payload.itemLabel || undefined,
            vendor: payload.vendor || undefined,
            answer: answerParam(answer.value),
            comment: commentParam(commentFilter.value),
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
            answer: answerParam(answer.value),
            comment: commentParam(commentFilter.value),
            page: pageNumber,
        },
        { preserveState: true },
    );
};
// 回答状態フィルタを切り替える（ページは1に戻す）。
const setAnswer = (value: AnswerFilter): void => {
    if (value === answer.value) {
        return;
    }
    router.get(
        window.location.pathname,
        {
            keyword: props.filters.keyword || undefined,
            itemLabel: props.filters.itemLabel || undefined,
            vendor: props.filters.vendor || undefined,
            answer: answerParam(value),
            comment: commentParam(commentFilter.value),
        },
        { preserveState: true, preserveScroll: true },
    );
};
// コメント有無フィルタを切り替える（ページは1に戻す）。
const setComment = (value: CommentFilter): void => {
    if (value === commentFilter.value) {
        return;
    }
    router.get(
        window.location.pathname,
        {
            keyword: props.filters.keyword || undefined,
            itemLabel: props.filters.itemLabel || undefined,
            vendor: props.filters.vendor || undefined,
            answer: answerParam(answer.value),
            comment: commentParam(value),
        },
        { preserveState: true, preserveScroll: true },
    );
};
</script>

<template>
    <Head :title="`見積管理 - ${title}`" />

    <div :class="rootClass">
        <!-- 背景の装飾ブロブ（ガラスのぼかし対象。自身を overflow-hidden で内側にクリップ） -->
        <div v-if="isThemed" aria-hidden="true" class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -left-20 -top-20 size-96 rounded-full bg-[#3f3f46]/30 blur-3xl"></div>
            <div class="absolute -top-10 right-10 size-80 rounded-full bg-[#c4a35b]/40 blur-3xl"></div>
            <div class="absolute left-1/3 top-1/3 size-112 rounded-full bg-[#52525b]/25 blur-3xl"></div>
            <div class="absolute -bottom-24 right-1/4 size-96 rounded-full bg-[#c4a35b]/20 blur-3xl"></div>
        </div>

        <div class="relative">
            <!-- 固定ヘッダー（タイトル＋業務/状態＋見積依頼送信＋絞り込み） -->
            <div class="sticky top-0 z-30 space-y-3 px-4 pb-3 pt-4 md:px-6" :class="stickyBgClass">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 transition-[padding]" :class="sidebarCollapsed ? 'md:pl-12' : ''">
                    <span v-if="isThemed" aria-hidden="true" class="h-7 w-1.5 rounded-full bg-[#c4a35b]"></span>
                    <h1 class="text-2xl font-bold tracking-tight" :class="headingClass">{{ title }}</h1>
                    <span class="text-sm" :class="onGlassTextClass">状態：{{ statusLabel }}</span>
                    <button
                        v-if="actionLabel && !isPerRowAction"
                        type="button"
                        :class="actionBtnClass"
                        :disabled="!actionEnabled"
                        :title="actionEnabled ? '' : config.hint"
                        @click="submitAction"
                    >
                        {{ form.processing ? config.processingLabel : actionLabel
                        }}<span v-if="!form.processing && selectedCount"> （{{ selectedCount }}）</span>
                    </button>
                </div>

                <FilterBar :filters="filters" :glass="glass" @search="onSearch" />
            </div>

            <!-- スクロール本体 -->
            <div class="space-y-4 px-4 pb-6 pt-3 md:px-6">
                <!-- 一括操作 + ページネーション（上） -->
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" :class="pagerBtnClass" @click="toggleAllProjects">
                            {{ anyOpen ? '明細を全て閉じる' : '明細を全て開く' }}
                        </button>
                        <button v-if="!isPerRowAction" type="button" :class="pagerBtnClass" @click="toggleSelectAll">
                            {{ bulkAllSelected ? config.bulkClearLabel : config.bulkSelectLabel }}
                        </button>
                        <!-- 業者選定・見積依頼：回答状態フィルタ（全て / 回答あり / 回答なし）。相見積額の有無で絞る。 -->
                        <div
                            v-if="showAnswerFilter"
                            class="inline-flex items-center gap-0.5 rounded-lg border border-primary/20 bg-white/70 p-0.5 backdrop-blur-md"
                        >
                            <button
                                v-for="opt in answerOptions"
                                :key="opt.value"
                                type="button"
                                class="rounded-md px-3 py-1.5 text-sm font-bold transition"
                                :class="answer === opt.value ? 'bg-[#c4a35b] text-white shadow-sm' : 'text-primary hover:bg-primary/10'"
                                @click="setAnswer(opt.value)"
                            >
                                {{ opt.label }}
                            </button>
                        </div>
                        <!-- 見積依頼のみ：仮選定した見積先だけにクライアント側で絞り込むトグル（チェックON/OFFが一目で分かる）。 -->
                        <label
                            v-if="showProvisionalFilter"
                            class="inline-flex cursor-pointer select-none items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-bold backdrop-blur-md transition"
                            :class="provisionalOnly
                                ? 'border-[#c4a35b] bg-[#c4a35b]/15 text-[#8a6d2f]'
                                : 'border-primary/20 bg-white/70 text-primary hover:bg-primary/10'"
                        >
                            <input
                                type="checkbox"
                                v-model="provisionalOnly"
                                class="size-4 cursor-pointer accent-[#c4a35b]"
                            />
                            仮選定のみ表示
                        </label>
                        <!-- 見積依頼のみ：未依頼（送信回数0）の行だけにクライアント側で絞り込むトグル。 -->
                        <label
                            v-if="mode === 'quote-request'"
                            class="inline-flex cursor-pointer select-none items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-bold backdrop-blur-md transition"
                            :class="unrequestedOnly
                                ? 'border-[#c4a35b] bg-[#c4a35b]/15 text-[#8a6d2f]'
                                : 'border-primary/20 bg-white/70 text-primary hover:bg-primary/10'"
                        >
                            <input
                                type="checkbox"
                                v-model="unrequestedOnly"
                                class="size-4 cursor-pointer accent-[#c4a35b]"
                            />
                            未依頼のみ表示
                        </label>
                        <!-- 業者選定のみ：既に選定済みの行だけに絞る（処理フロー I列・初期 OFF）。 -->
                        <label
                            v-if="mode === 'vendor-selection'"
                            class="inline-flex cursor-pointer select-none items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-bold backdrop-blur-md transition"
                            :class="selectedOnly
                                ? 'border-[#c4a35b] bg-[#c4a35b]/15 text-[#8a6d2f]'
                                : 'border-primary/20 bg-white/70 text-primary hover:bg-primary/10'"
                        >
                            <input type="checkbox" v-model="selectedOnly" class="size-4 cursor-pointer accent-[#c4a35b]" />
                            業者選定済み
                        </label>
                        <!-- 部長承認のみ：この画面で承認できる行だけに絞る（処理フロー I列・初期 ON）。 -->
                        <label
                            v-if="mode === 'manager-approval'"
                            class="inline-flex cursor-pointer select-none items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-bold backdrop-blur-md transition"
                            :class="unapprovedOnly
                                ? 'border-[#c4a35b] bg-[#c4a35b]/15 text-[#8a6d2f]'
                                : 'border-primary/20 bg-white/70 text-primary hover:bg-primary/10'"
                        >
                            <input type="checkbox" v-model="unapprovedOnly" class="size-4 cursor-pointer accent-[#c4a35b]" />
                            未承認
                        </label>
                        <!-- コメントのやり取りの有無フィルタ（全画面共通）。 -->
                        <div class="inline-flex items-center gap-0.5 rounded-lg border border-primary/20 bg-white/70 p-0.5 backdrop-blur-md">
                            <button
                                v-for="opt in commentFilterOptions"
                                :key="opt.value"
                                type="button"
                                class="rounded-md px-3 py-1.5 text-sm font-bold transition"
                                :class="commentFilter === opt.value ? 'bg-[#c4a35b] text-white shadow-sm' : 'text-primary hover:bg-primary/10'"
                                @click="setComment(opt.value)"
                            >
                                {{ opt.label }}
                            </button>
                        </div>
                    </div>
                    <Pager :pagination="pagination" :glass="glass" @change="goToPage" />
                </div>

                <QuotationProjectCard
                    v-for="project in displayProjects"
                    :key="project.id"
                    :project="project"
                    :mode="mode"
                    :glass="glass"
                    :open="isOpen(project.id)"
                    :selected-keys="activeKeys"
                    :provisional-keys="provisionalKeys"
                    @toggle="toggle(project.id)"
                    @row-toggle="onRowToggle"
                    @row-action="openRowAction"
                    @provisional-toggle="toggleProvisional"
                    @reject="openReject"
                    @open-chat="openChat"
                    @open-iframe="openIframe"
                    @billing-send="submitBillingSend"
                />

                <div v-if="!displayProjects.length" class="p-8 text-center" :class="[glassPanelClass, onGlassTextClass]">
                    {{ unapprovedOnly
                        ? '未承認の見積先がありません。'
                        : selectedOnly
                            ? '選定済みの見積先がありません。'
                            : unrequestedOnly && !provisionalOnly
                                ? '未依頼の見積先がありません。'
                                : provisionalOnly
                                    ? '仮選定された見積先がありません。'
                                    : '対象の案件がありません。' }}
                </div>

                <!-- ページネーション（下） -->
                <div class="flex justify-end pt-1">
                    <Pager :pagination="pagination" :glass="glass" @change="goToPage" />
                </div>
            </div>
        </div>
    </div>

    <!-- やり取り（チャット）モーダル：部下⇔部長が見積についてコメントを交わす。 -->
    <Teleport to="body">
        <div
            v-if="chatOpen"
            class="fixed inset-0 z-[90] flex items-center justify-center bg-black/50 p-4"
            @click.self="closeChat"
        >
            <div
                class="relative flex h-[80vh] w-full max-w-lg flex-col overflow-hidden rounded-xl bg-white shadow-2xl"
                @dragenter.prevent="chatDragOver = true"
                @dragover.prevent="chatDragOver = true"
                @dragleave.prevent="chatDragOver = false"
                @drop.prevent="onChatDrop"
            >
                <!-- ドラッグ中のドロップ案内オーバーレイ -->
                <div
                    v-if="chatDragOver"
                    class="pointer-events-none absolute inset-0 z-10 flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-[#c4a35b] bg-[#c4a35b]/10 backdrop-blur-sm"
                >
                    <Paperclip class="size-8 text-[#c4a35b]" />
                    <p class="text-sm font-semibold text-[#8a6a25]">ここにファイルをドロップして添付</p>
                </div>
                <!-- ヘッダー：対象の項目・見積先 -->
                <div class="flex items-start gap-2 border-b px-4 py-3">
                    <MessageSquare class="mt-0.5 size-5 text-[#c4a35b]" />
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-slate-800">コメント履歴</p>
                        <p v-if="chatBuilding" class="truncate text-[11px] text-slate-400">{{ chatBuilding }}</p>
                        <p class="truncate text-xs text-slate-500">{{ chatTarget?.itemName || '項目' }}</p>
                    </div>
                    <button
                        type="button"
                        class="flex size-8 items-center justify-center rounded-md text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                        title="閉じる"
                        @click="closeChat"
                    >
                        <X class="size-5" />
                    </button>
                </div>

                <!-- コメント履歴一覧（LINE風：自分の投稿は右寄せ、相手は左寄せ） -->
                <div ref="chatScroll" class="flex-1 space-y-3 overflow-y-auto bg-slate-50 px-4 py-4">
                    <p v-if="chatLoading" class="py-8 text-center text-sm text-slate-400">読み込み中…</p>
                    <p v-else-if="!chatMessages.length" class="py-8 text-center text-sm text-slate-400">
                        まだコメントはありません。最初のコメントを残しましょう。
                    </p>
                    <div v-for="m in chatMessages" :key="m.id" class="flex" :class="m.isMine ? 'justify-end' : 'justify-start'">
                        <div class="flex max-w-[80%] flex-col" :class="m.isMine ? 'items-end' : 'items-start'">
                            <!-- 送信者情報（名前）。自分は右寄せに並べる。 -->
                            <div
                                class="mb-1 flex flex-wrap items-center gap-1.5 text-[11px] text-slate-500"
                                :class="m.isMine ? 'flex-row-reverse' : ''"
                            >
                                <span class="font-semibold text-slate-700">{{ m.senderName }}</span>
                                <span v-if="m.isMine" class="rounded bg-slate-100 px-1 py-0.5 leading-none text-slate-400">自分</span>
                            </div>
                            <!-- 吹き出し：自分＝ゴールド（右）、相手＝白（左）。 -->
                            <div
                                class="rounded-2xl px-3 py-2 shadow-sm"
                                :class="m.isMine
                                    ? 'rounded-br-sm border border-[#c4a35b] bg-[#c4a35b] text-white'
                                    : 'rounded-bl-sm border border-slate-200 bg-white text-slate-800'"
                            >
                                <div v-if="m.body" class="whitespace-pre-wrap break-words text-sm">{{ m.body }}</div>
                                <!-- 添付ファイル：サムネのある画像はサムネ表示、その他（HEIC・文書等）はファイル名リンク。クリックは常にダウンロード。 -->
                                <div v-if="m.files.length" class="mt-2 flex flex-wrap gap-2" :class="m.isMine ? 'justify-end' : ''">
                                    <template v-for="f in m.files" :key="f.id">
                                        <a
                                            v-if="f.thumbUrl"
                                            :href="f.downloadUrl"
                                            :download="f.name"
                                            :title="`${f.name} をダウンロード`"
                                            class="block overflow-hidden rounded-lg border border-slate-200 transition hover:border-[#c4a35b]"
                                        >
                                            <img :src="f.thumbUrl" :alt="f.name" class="size-24 object-cover" />
                                        </a>
                                        <a
                                            v-else
                                            :href="f.downloadUrl"
                                            :download="f.name"
                                            :title="`${f.name} をダウンロード`"
                                            class="inline-flex max-w-[220px] items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs text-slate-700 transition hover:border-[#c4a35b] hover:bg-[#c4a35b]/10"
                                        >
                                            <FileText class="size-4 shrink-0 text-[#c4a35b]" />
                                            <span class="truncate">{{ f.name }}</span>
                                            <span class="shrink-0 text-slate-400">{{ formatFileSize(f.size) }}</span>
                                        </a>
                                    </template>
                                </div>
                            </div>
                            <!-- 送信時刻 -->
                            <span class="mt-1 text-[10px] text-slate-400">{{ m.createdAt }}</span>
                        </div>
                    </div>
                </div>

                <!-- 入力 -->
                <div class="border-t px-3 py-3">
                    <p v-if="chatError" class="mb-1 text-xs text-red-600">{{ chatError }}</p>
                    <!-- 添付予定ファイルの一覧（送信前・個別に取り消し可）。 -->
                    <div v-if="chatFiles.length" class="mb-2 flex flex-wrap gap-1.5">
                        <span
                            v-for="(f, i) in chatFiles"
                            :key="`${f.name}-${i}`"
                            class="inline-flex max-w-[220px] items-center gap-1.5 rounded-lg border border-[#c4a35b]/40 bg-[#c4a35b]/10 px-2 py-1 text-xs text-[#8a6a25]"
                        >
                            <FileText class="size-3.5 shrink-0" />
                            <span class="truncate">{{ f.name }}</span>
                            <span class="shrink-0 text-[#8a6a25]/60">{{ formatFileSize(f.size) }}</span>
                            <button
                                type="button"
                                class="shrink-0 rounded p-0.5 text-[#8a6a25]/70 transition hover:bg-[#c4a35b]/20 hover:text-[#8a6a25]"
                                title="添付を取り消す"
                                @click="removeChatFile(i)"
                            >
                                <X class="size-3.5" />
                            </button>
                        </span>
                    </div>
                    <div class="flex items-end gap-2">
                        <!-- ファイル選択（端末内のファイルを開く）。実体は隠し input。 -->
                        <input ref="fileInput" type="file" multiple class="hidden" accept=".jpg,.jpeg,.png,.gif,.webp,.heic,.heif,.pdf,.txt,.xlsx,.xls,.docx,.doc,.pptx,.ppt,.csv,.dwg,.dxf,.jww,.jwc,.sfc,.p21,.zip" @change="onFileInputChange" />
                        <button
                            type="button"
                            class="flex size-10 shrink-0 items-center justify-center rounded-lg border border-slate-300 text-slate-500 transition hover:border-[#c4a35b] hover:bg-[#c4a35b]/10 hover:text-[#8a6a25]"
                            title="ファイルを添付"
                            @click="openFilePicker"
                        >
                            <Paperclip class="size-5" />
                        </button>
                        <textarea
                            v-model="chatBody"
                            rows="2"
                            class="flex-1 resize-none rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#c4a35b] focus:outline-none focus:ring-2 focus:ring-[#c4a35b]/30"
                            placeholder="コメントを入力（Enterで改行）"
                            @keydown.enter.exact.prevent="sendChat"
                        />
                        <button
                            type="button"
                            class="flex h-10 shrink-0 items-center gap-1.5 rounded-lg px-3 text-sm font-semibold text-white transition"
                            :class="canSendChat ? 'bg-[#c4a35b] hover:bg-[#b3923f]' : 'cursor-not-allowed bg-[#c4a35b]/40'"
                            :disabled="!canSendChat"
                            @click="sendChat"
                        >
                            <Send class="size-4" />{{ chatSending ? '送信中' : 'コメントする' }}
                        </button>
                    </div>
                    <p class="mt-1 text-[11px] text-slate-400">
                        あなたは「{{ myRole === 'manager' ? '部長' : '部下' }}」としてコメントします。ファイルはドラッグ&ドロップでも添付できます。
                    </p>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- 否認モーダル（部長承認画面）：理由を入力して業者選定へ差し戻す。 -->
    <Teleport to="body">
        <div
            v-if="rejectModalOpen"
            class="fixed inset-0 z-[90] flex items-center justify-center bg-black/50 p-4"
            @click.self="closeReject"
        >
            <div class="w-full max-w-md overflow-hidden rounded-xl bg-white shadow-2xl">
                <div class="flex items-center gap-2 border-b px-4 py-3">
                    <Ban class="size-5 text-red-600" />
                    <span class="text-sm font-semibold text-slate-800">否認（業者選定へ差し戻し）</span>
                </div>
                <div class="space-y-3 px-4 py-4">
                    <p class="text-sm text-slate-600">
                        見積先「<span class="font-semibold text-slate-800">{{ rejectTarget?.vendorName }}</span>」を否認し、業者選定へ差し戻します。
                    </p>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-500">否認理由<span class="text-red-500">（必須）</span></label>
                        <textarea
                            v-model="rejectForm.reason"
                            rows="4"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#c4a35b] focus:outline-none focus:ring-2 focus:ring-[#c4a35b]/30"
                            placeholder="差し戻しの理由を入力してください"
                        />
                        <p v-if="rejectForm.errors.reason" class="mt-1 text-xs text-red-600">{{ rejectForm.errors.reason }}</p>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t px-4 py-3">
                    <button
                        type="button"
                        class="h-9 rounded-md border bg-card px-4 text-sm font-semibold text-muted-foreground transition hover:bg-accent"
                        @click="closeReject"
                    >
                        キャンセル
                    </button>
                    <button
                        type="button"
                        class="h-9 rounded-md px-4 text-sm font-semibold text-white transition"
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
    </Teleport>

    <!-- 取消申請 / 取消承認モーダル：理由を入力して処理する（否認と同様の動き）。 -->
    <Teleport to="body">
        <div
            v-if="actionModalOpen"
            class="fixed inset-0 z-[90] flex items-center justify-center bg-black/50 p-4"
            @click.self="closeActionModal"
        >
            <div class="w-full max-w-md overflow-hidden rounded-xl bg-white shadow-2xl">
                <div class="flex items-center gap-2 border-b px-4 py-3">
                    <Ban class="size-5 text-[#c4a35b]" />
                    <span class="text-sm font-semibold text-slate-800">{{ actionLabel }}の理由</span>
                </div>
                <div class="space-y-3 px-4 py-4">
                    <p class="text-sm text-slate-600">
                        見積先「<span class="font-semibold text-slate-800">{{ actionTarget?.vendorName }}</span>」を{{ actionLabel }}します。
                    </p>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-500">{{ actionLabel }}の理由<span class="text-red-500">（必須）</span></label>
                        <textarea
                            v-model="form.reason"
                            rows="4"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#c4a35b] focus:outline-none focus:ring-2 focus:ring-[#c4a35b]/30"
                            :placeholder="`${actionLabel}の理由を入力してください`"
                        />
                        <p v-if="form.errors.reason" class="mt-1 text-xs text-red-600">{{ form.errors.reason }}</p>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t px-4 py-3">
                    <button
                        type="button"
                        class="h-9 rounded-md border bg-card px-4 text-sm font-semibold text-muted-foreground transition hover:bg-accent"
                        @click="closeActionModal"
                    >
                        キャンセル
                    </button>
                    <button
                        type="button"
                        class="h-9 rounded-md px-4 text-sm font-semibold text-white transition"
                        :class="form.reason.trim() && !form.processing
                            ? 'bg-[#c4a35b] hover:bg-[#b3923f]'
                            : 'cursor-not-allowed bg-[#c4a35b]/40'"
                        :disabled="!form.reason.trim() || form.processing"
                        @click="submitActionWithReason"
                    >
                        {{ form.processing ? config.processingLabel : actionLabel }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- felix_total を開く汎用 iframe モーダル（見積先の詳細 / 業者追加 など）。 -->
    <Teleport to="body">
        <div
            v-if="iframeOpen"
            class="fixed inset-0 z-[90] flex items-center justify-center bg-black/50 p-4"
            @click.self="closeIframe"
        >
            <div class="relative flex h-[88vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b px-4 py-2.5">
                    <span class="text-sm font-semibold text-slate-800">{{ iframeTitle }}</span>
                    <button
                        type="button"
                        class="flex size-8 items-center justify-center rounded-md text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                        title="閉じる"
                        @click="closeIframe"
                    >
                        <X class="size-5" />
                    </button>
                </div>
                <iframe v-if="iframeUrl" :src="iframeUrl" class="w-full flex-1" :title="iframeTitle" />
                <div v-else class="flex flex-1 items-center justify-center px-6 text-center text-sm text-slate-500">
                    画面の URL が未設定です（felix_total 連携の env を確認してください）。
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
// 見積管理の共通画面コンテナ（状態統括）。見積り依頼 / 発注業者選定 … で共用し、mode で出し分ける。
// 子: EstimateFilterBar（絞り込み）/ EstimatePager（ページ送り）/ EstimateProjectCard（明細）。
// 配色は useEstimateTheme に集約。glass=true は「明細は白／それ以外はリキッドグラス（FELIX 紺＋金）」。
// 操作は mode（ESTIMATE_MODE_CONFIG）で出し分ける：
// - 見積依頼（quote-request）：未依頼行をチェックで選び、ヘッダー送信で相見積依頼を記録。
// - 発注業者選定（vendor-selection）：サーバの採用状態を初期値にボタンでトグル（業者の選び替え）。
// - 部長承認 / 取消申請 / 取消承認（pick-button）：未処理行をボタンで選び、ヘッダー確定で送信。
// いずれも成功時はサーバが back() → 一覧再読込 + flash メッセージ。
// ※ 業者へのメール通知（felix_total のトークン発行＋送信）は本フェーズ未対応。
import { computed, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import type { RouteDefinition } from '@/wayfinder';
import { send as sendQuoteRequestRoute } from '@/routes/estimate-management/quote-request';
import { confirm as confirmVendorSelectionRoute } from '@/routes/estimate-management/vendor-selection';
import { confirm as confirmDesignSelectionRoute } from '@/routes/estimate-management/design-selection';
import { confirm as confirmManagerApprovalRoute } from '@/routes/estimate-management/manager-approval';
import { confirm as confirmCancelRequestRoute } from '@/routes/estimate-management/cancel-request';
import { confirm as confirmCancelApprovalRoute } from '@/routes/estimate-management/cancel-approval';
import AppLayout from '@/layouts/AppLayout.vue';
import EstimateFilterBar from '@/components/estimate-management/EstimateFilterBar.vue';
import EstimatePager from '@/components/estimate-management/EstimatePager.vue';
import EstimateProjectCard from '@/components/estimate-management/EstimateProjectCard.vue';
import { useEstimateTheme } from '@/composables/useEstimateTheme';
import { estimateRowKey, ESTIMATE_MODE_CONFIG } from '@/lib/estimate-management';
import type {
    EstimateManagementFilters,
    EstimateManagementMode,
    EstimateManagementPagination,
    EstimateManagementProject,
    EstimateManagementRow,
} from '@/types/estimate-management';

const props = defineProps<{
    title: string;
    statusLabel: string;
    mode: EstimateManagementMode;
    actionLabel?: string | null;
    glass?: boolean;
    projects: EstimateManagementProject[];
    pagination: EstimateManagementPagination;
    filters: EstimateManagementFilters;
}>();

const isThemed = computed(() => props.glass === true);
const config = computed(() => ESTIMATE_MODE_CONFIG[props.mode]);
// toggle = 発注業者選定（サーバ状態を初期値にトグル）／pick = チェック/ボタンで未処理行を選ぶ。
const isToggleMode = computed(() => config.value.kind === 'toggle-button');
const { rootClass, stickyBgClass, glassPanelClass, headingClass, onGlassTextClass, pagerBtnClass } =
    useEstimateTheme(isThemed);

// mode → 送信先ルート。
const actionRoute = computed<RouteDefinition<'post'>>(() => {
    switch (props.mode) {
        case 'vendor-selection':
            return confirmVendorSelectionRoute();
        case 'design-selection':
            return confirmDesignSelectionRoute();
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

const allRows = computed<EstimateManagementRow[]>(() => props.projects.flatMap((p) => p.rows));
// 行が「処理済み」か（mode ごとのサーバ側フラグ。例: 見積依頼=requested / 部長承認=approved）。
const isApplied = (row: EstimateManagementRow): boolean => row[config.value.appliedKey] === true;

// pick モード（見積依頼 / 部長承認 / 取消申請 / 取消承認）の選択。
// 1つの真実は checked（行キー→真偽）。未処理行だけを選べる。表示用に Set を派生。
const checked = reactive<Record<string, boolean>>({});
const checkedKeys = computed(() => new Set(Object.keys(checked).filter((k) => checked[k])));
const toggleRow = (row: EstimateManagementRow): void => {
    if (row.companyId == null || isApplied(row)) {
        return;
    }
    const key = estimateRowKey(row);
    checked[key] = !checked[key];
};
// 選べるのは「見積先（業者）が紐づき、かつ未処理」の行だけ（処理済みは再操作不可）。
const selectableRows = computed<EstimateManagementRow[]>(() =>
    allRows.value.filter((r) => r.companyId != null && !isApplied(r)),
);
const anyChecked = computed(() => checkedKeys.value.size > 0);

// toggle モード（発注業者選定 / 設計部選定）の選定状態。サーバの選定フラグ（mode ごとの
// appliedKey。発注=selected / 設計=designSelected）を初期値に、ローカル上書き（押下）で管理する。
// 送信成功後はリロードされるため上書きはクリアする。
const selectionOverride = reactive<Record<string, boolean>>({});
const serverSelected = (row: EstimateManagementRow): boolean => row[config.value.appliedKey] === true;
const isRowSelected = (row: EstimateManagementRow): boolean => {
    const key = estimateRowKey(row);
    return key in selectionOverride ? selectionOverride[key] : serverSelected(row);
};
const toggleSelect = (row: EstimateManagementRow): void => {
    if (row.companyId == null) {
        return;
    }
    const key = estimateRowKey(row);
    selectionOverride[key] = !isRowSelected(row);
};
// 現在「選定済（押下）」の行キー集合。明細カードのボタン表示に使う。
const vendorSelectedKeys = computed(
    () => new Set(allRows.value.filter((r) => r.companyId != null && isRowSelected(r)).map((r) => estimateRowKey(r))),
);
// サーバ状態から1つでも変更（押下）があるか。確定ボタンの活性判定に使う。
const vendorDirty = computed(() =>
    allRows.value.some((r) => {
        const key = estimateRowKey(r);
        return key in selectionOverride && selectionOverride[key] !== serverSelected(r);
    }),
);

// 明細カードへ渡す「有効な行キー集合」と行トグル（モードで出し分け）。
const activeKeys = computed(() => (isToggleMode.value ? vendorSelectedKeys.value : checkedKeys.value));
const onRowToggle = (row: EstimateManagementRow): void => {
    if (isToggleMode.value) {
        toggleSelect(row);
    } else {
        toggleRow(row);
    }
};
const selectedCount = computed(() => (isToggleMode.value ? vendorSelectedKeys.value.size : checkedKeys.value.size));

// 一括「全て選択」（全モード共通）。
// toggle（発注業者選定 / 設計部承認）= 業者行すべて、pick（見積依頼 / 部長承認 / 取消申請 / 取消承認）= 未処理の業者行が対象。
const bulkSelectableRows = computed<EstimateManagementRow[]>(() =>
    isToggleMode.value ? allRows.value.filter((r) => r.companyId != null) : selectableRows.value,
);
// 対象行がすべて選択状態か（ボタン表示名の出し分けに使う）。
const bulkAllSelected = computed(() => {
    const rows = bulkSelectableRows.value;
    if (rows.length === 0) {
        return false;
    }
    return isToggleMode.value
        ? rows.every((r) => isRowSelected(r))
        : rows.every((r) => checkedKeys.value.has(estimateRowKey(r)));
});
// 全て選択 / 全て解除（現在の状態を反転）。
const toggleSelectAll = (): void => {
    const select = !bulkAllSelected.value;
    if (isToggleMode.value) {
        bulkSelectableRows.value.forEach((r) => {
            selectionOverride[estimateRowKey(r)] = select;
        });
    } else {
        bulkSelectableRows.value.forEach((r) => {
            checked[estimateRowKey(r)] = select;
        });
    }
};

// 送信は Inertia useForm（processing 統合）+ Wayfinder アクションで行う。
const form = useForm<{ companyIds: number[] }>({ companyIds: [] });

// 主役アクションの活性判定（金ベタ＝有効 / 淡色＝無効）。
// pick=チェックあり / 発注業者選定=サーバ状態からの変更（押下）あり、で活性化する。
const actionEnabled = computed(() => {
    if (form.processing) {
        return false;
    }
    return isToggleMode.value ? vendorDirty.value && vendorSelectedKeys.value.size > 0 : anyChecked.value;
});

// 主役アクション。選択（押下）された見積先（companyId のある行）をサーバへ送る。
// 成功時はサーバが back() でリダイレクト → 一覧が再読込され、flash 成功メッセージを表示する。
const submitAction = (): void => {
    if (!actionEnabled.value) {
        return;
    }
    const keys = isToggleMode.value ? vendorSelectedKeys.value : checkedKeys.value;
    const companyIds = allRows.value
        .filter((row) => row.companyId != null && keys.has(estimateRowKey(row)))
        .map((row) => row.companyId as number);
    if (companyIds.length === 0) {
        return;
    }
    form.companyIds = companyIds;
    form.submit(actionRoute.value, {
        preserveScroll: true,
        onSuccess: () => {
            // 処理済みは再読込後の一覧に反映される。ローカルの選択/選定状態はクリアする。
            Object.keys(checked).forEach((key) => delete checked[key]);
            Object.keys(selectionOverride).forEach((key) => delete selectionOverride[key]);
        },
    });
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

const onSearch = (payload: EstimateManagementFilters): void => {
    router.get(
        window.location.pathname,
        { keyword: payload.keyword || undefined, itemLabel: payload.itemLabel || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};
const goToPage = (pageNumber: number): void => {
    router.get(
        window.location.pathname,
        { keyword: props.filters.keyword || undefined, itemLabel: props.filters.itemLabel || undefined, page: pageNumber },
        { preserveState: true },
    );
};
</script>

<template>
    <AppLayout>
        <Head :title="`見積管理 - ${title}`" />

        <div :class="rootClass">
            <!-- 背景の装飾ブロブ（ガラスのぼかし対象。自身を overflow-hidden で内側にクリップ） -->
            <div v-if="isThemed" aria-hidden="true" class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -left-20 -top-20 size-96 rounded-full bg-[#22336b]/30 blur-3xl"></div>
                <div class="absolute -top-10 right-10 size-80 rounded-full bg-[#c4a35b]/40 blur-3xl"></div>
                <div class="absolute left-1/3 top-1/3 size-112 rounded-full bg-[#3b4f8f]/25 blur-3xl"></div>
                <div class="absolute -bottom-24 right-1/4 size-96 rounded-full bg-[#c4a35b]/20 blur-3xl"></div>
            </div>

            <div class="relative">
                <!-- 固定ヘッダー（タイトル＋業務/状態＋見積依頼送信＋絞り込み） -->
                <div class="sticky top-0 z-30 space-y-3 px-4 pb-3 pt-4 md:px-6" :class="stickyBgClass">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                        <span v-if="isThemed" aria-hidden="true" class="h-7 w-1.5 rounded-full bg-[#c4a35b]"></span>
                        <h1 class="text-2xl font-bold tracking-tight" :class="headingClass">{{ title }}</h1>
                        <span class="text-sm" :class="onGlassTextClass">状態：{{ statusLabel }}</span>
                        <button
                            v-if="actionLabel"
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

                    <EstimateFilterBar :filters="filters" :glass="glass" @search="onSearch" />
                </div>

                <!-- スクロール本体 -->
                <div class="space-y-4 px-4 pb-6 pt-3 md:px-6">
                    <!-- 一括操作 + ページネーション（上） -->
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" :class="pagerBtnClass" @click="toggleAllProjects">
                                {{ anyOpen ? '明細を全て閉じる' : '明細を全て開く' }}
                            </button>
                            <button type="button" :class="pagerBtnClass" @click="toggleSelectAll">
                                {{ bulkAllSelected ? config.bulkClearLabel : config.bulkSelectLabel }}
                            </button>
                        </div>
                        <EstimatePager :pagination="pagination" :glass="glass" @change="goToPage" />
                    </div>

                    <EstimateProjectCard
                        v-for="project in projects"
                        :key="project.id"
                        :project="project"
                        :mode="mode"
                        :glass="glass"
                        :open="isOpen(project.id)"
                        :selected-keys="activeKeys"
                        @toggle="toggle(project.id)"
                        @row-toggle="onRowToggle"
                    />

                    <div v-if="!projects.length" class="p-8 text-center" :class="[glassPanelClass, onGlassTextClass]">
                        対象の案件がありません。
                    </div>

                    <!-- ページネーション（下） -->
                    <div class="flex justify-end pt-1">
                        <EstimatePager :pagination="pagination" :glass="glass" @change="goToPage" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

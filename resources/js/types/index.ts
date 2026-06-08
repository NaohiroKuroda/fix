// ステータス管理ドメインの型定義（サーバの JsonResource と一致させる）

export type LabelMap = Record<string, string>;

/** 状態 + ラベル + 日付（選定・承認系セル） */
export interface StateCell {
    state: string;
    label: string;
    date: string;
    denial?: string;
}

/** 見積UP / 依頼（件数つき） */
export interface CountStateCell {
    state: string;
    label: string;
    date: string;
    count: number;
}

/** 工期セル */
export interface PeriodCell {
    date: string;
    label: string;
    set: boolean;
}

/** 承認納期日 / 発注書送付日 */
export interface DateStateCell {
    date: string;
    state: string;
    label: string;
}

/** 必須ファイル */
export interface RequiredCell {
    count: number;
    state: string;
    label: string;
}

/** 発注先（業者）— EstimateUnitCompanyResource */
export interface UnitCompany {
    name: string;
    payName: string | null;
    adopted: boolean;
    changed: boolean;
    emphasized: boolean;
    lastPrice: number | null;
    selected: boolean;
    estimateUp: CountStateCell;
    request: CountStateCell;
    buildSelect: StateCell;
    designSelect: StateCell;
    execApproval: StateCell;
    approval1: StateCell;
    approval2: StateCell;
    approval3: StateCell;
}

/** 見積明細ユニット — EstimateUnitResource */
export interface EstimateUnit {
    id: number;
    label: string;
    subCateLabel: string;
    useFlg: number;
    masterPrice: number | null;
    estimatePrice: number | null;
    construct: PeriodCell;
    completion: PeriodCell;
    replyDate: DateStateCell;
    orderDate: DateStateCell;
    requiredFile: RequiredCell;
    companies: UnitCompany[];
}

/** 案件（見積）— EstimateResource */
export interface Estimate {
    id: number;
    name: string;
    units: EstimateUnit[];
}

/** ページネーション情報 */
export interface Pagination {
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
    from: number | null;
    to: number | null;
}

/** 検索フォームの値 */
export interface Filters {
    keyword: string;
    subCate: string;
    company: string;
    companySelectStatus: string;
    completeStatus: string;
    constructAt: string;
    completionAt: string;
    adoptionFlg: boolean;
    changedFlg: boolean;
}

/** 検索プルダウンの選択肢 */
export interface FilterOptions {
    subCate: LabelMap;
    companySelectStatus: LabelMap;
    completeStatus: LabelMap;
}

// ── 実行予算一覧 ──────────────────────────────

/** 実行予算の見積明細（ユニット）— BudgetUnitResource */
export interface BudgetUnit {
    id: number;
    label: string;
    subCateLabel: string;
    useFlg: number;
    amount: number | null;
    unitPrice: number | null;
    price: number | null;
    masterPrice: number | null;
    estimatePrice: number | null;
    company: string | null;
    companySelectLabel: string;
    completeLabel: string;
    constructAt: string;
    completionAt: string;
}

/** 実行予算 詳細の財務サマリ */
export interface BudgetSummary {
    address: string | null;
    deliveryDate: string;
    sellTotal: number | null;
    costTotal: number | null;
    profitTotal: number | null;
    profitRate: number | null;
}

/** 原価セクション（明細はステータス画面と同じ EstimateUnit） */
export interface BudgetSection {
    key: string;
    title: string;
    count: number;
    units: EstimateUnit[];
}

/** 実行予算 詳細 — EstimateDetailResource */
export interface EstimateDetail {
    id: number;
    name: string;
    hasTmpBudget: boolean;
    summary: BudgetSummary;
    sections: BudgetSection[];
}

/** 集計セル: d=日付, v=数値, s=テキスト */
export interface AggCell {
    d: string | null;
    v: number | null;
    s: string | null;
}

/** 実行予算（案件）— ExecutionBudgetResource（estimate_aggregates 踏襲） */
export interface ExecutionBudget {
    id: number;
    name: string;
    hasTmpBudget: boolean;
    agg: Record<string, AggCell>;
}

/** 実行予算一覧の検索フォーム */
export interface BudgetFilters {
    id: string;
    keyword: string;
    monthFrom: string;
    monthTo: string;
    sort: string;
}

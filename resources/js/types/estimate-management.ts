// 見積管理画面（見積り依頼 / 発注業者選定 ほか）の型。
// サーバの EstimateManagementResource と一致させること。

/** 案件 → 項目 → 見積先 を展開した1行。 */
export interface EstimateManagementRow {
    unitId: number;
    companyId: number | null;
    /** 同一項目の先頭行のみ値が入る（2行目以降は空文字）。 */
    itemLabel: string;
    vendorName: string;
    masterPrice: number | null;
    budgetPrice: number | null;
    quotePrice: number | null;
    /** 見積依頼済み（相見積送信履歴あり）。 */
    requested: boolean;
    /** 発注業者として選定済み（adoption_flg=1）。 */
    selected: boolean;
    /** 設計部選定済み（design_status=1）。 */
    designSelected: boolean;
    /** 部長承認済み（fix_status=1）。 */
    approved: boolean;
    /** 取消申請中（cancel_flg>=1）。 */
    cancelRequested: boolean;
    /** 取消承認済み（cancel_flg>=2）。 */
    cancelApproved: boolean;
}

/** 案件（実行予算）1件。 */
export interface EstimateManagementProject {
    id: number;
    no: number;
    name: string;
    rows: EstimateManagementRow[];
}

export interface EstimateManagementPagination {
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
    from: number | null;
    to: number | null;
}

export interface EstimateManagementFilters {
    keyword: string;
    itemLabel: string;
}

/**
 * 画面モード（列の出し分け / アクション）。
 * quote-request 以外は「発注業者選定」と同じボタン形式（押下→ヘッダー確定→API→リロード）。
 */
export type EstimateManagementMode =
    | 'quote-request'
    | 'vendor-selection'
    | 'design-selection'
    | 'manager-approval'
    | 'cancel-request'
    | 'cancel-approval';

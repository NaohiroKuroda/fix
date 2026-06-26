// 見積管理画面（見積り依頼 / 業者選定 ほか）の型。
// サーバの BuildingQuotationResource と一致させること。

/** 案件 → 項目 → 見積先 を展開した1行。 */
export interface EstimateManagementRow {
    unitId: number;
    companyId: number | null;
    /** 同一項目の先頭行のみ値が入る（2行目以降は空文字）。 */
    itemLabel: string;
    vendorName: string;
    /** 見積先の詳細（iframe で開く felix_total の見積先編集フォーム）。会社名リンクに使う。 */
    vendorDetailUrl: string | null;
    /** 業者マイページ（別タブで開く auto_login の estimate 編集ページ）。右端ボタンに使う。 */
    vendorUrl: string | null;
    /** 「見積先を追加」リンク先（iframe で開く felix_total の見積先追加画面）。項目（unit）単位。 */
    addVendorUrl: string | null;
    masterPrice: number | null;
    budgetPrice: number | null;
    quotePrice: number | null;
    /** 見積依頼済み（相見積送信履歴あり）。 */
    requested: boolean;
    /** 発注業者として選定済み（adoption_flg=1）。 */
    selected: boolean;
    /** 部長承認済み（fix_status=1）。 */
    approved: boolean;
    /** 取消申請中（cancel_flg>=1）。 */
    cancelRequested: boolean;
    /** 取消承認済み（cancel_flg>=2）。 */
    cancelApproved: boolean;
    /** 仮選定（新スキーマ t_cost_quotations.is_drafted）。旧スキーマは常に false。 */
    provisional: boolean;
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
    /** 見積先（業者名）での絞り込み。 */
    vendor: string;
    /** 業者選定画面の回答状態フィルタ（全て / 回答あり / 回答なし）。サーバから常に渡る。 */
    answer?: 'all' | 'answered' | 'unanswered';
}

/**
 * 画面モード（列の出し分け / アクション）。
 * quote-request 以外は「業者選定」と同じボタン形式（押下→ヘッダー確定→API→リロード）。
 */
export type EstimateManagementMode =
    | 'quote-request'
    | 'vendor-selection'
    | 'manager-approval'
    | 'cancel-request'
    | 'cancel-approval';

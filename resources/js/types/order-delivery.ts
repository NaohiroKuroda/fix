// 発注〜納品〜請求フロー画面の型。サーバの OrderDeliveryResource と一致させること。
// 見積管理とほぼ同じ「案件 → 項目 → 見積先」構造。

/** 案件 → 項目 → 見積先 を展開した1行。 */
export interface OrderDeliveryRow {
    unitId: number;
    /** チャット・アクションの単位＝見積先ID（t_cost_quotations.id）。 */
    companyId: number;
    itemName: string;
    vendorName: string;
    /**
     * 区分（もらい＝請求 / 払い＝支払）。見積依頼画面と同じ t_cost_quotations.is_billing_target。
     * 業者承諾確認画面（showBillingKind）の「区分」列に出す。
     */
    billingTarget: boolean;
    masterPrice: number | null;
    budgetPrice: number | null;
    /** 相見積／見積（税抜）＝業者の見積額（最新の相見積履歴）。 */
    quotePrice: number | null;
    /** 発注（税抜）＝発注金額。発注前は null。 */
    orderPrice: number | null;
    /** 承諾の残り期限（日数）。正=あとN日、0=本日、負=期限超過。発注前は null。 */
    deadlineDays: number | null;
    orderDate: string | null;
    vendorAcceptedAt: string | null;
    submittedAt: string | null;
    /** 完了確認画面（請求）：請求書は確認と同時に自動作成される。未作成は null。 */
    invoiceAmount: number | null;
    invoiceStatus: string | null;
    invoiceSubmittedAt: string | null;
    invoiceApprovedAt: string | null;
    /** やり取り（コメント）の件数（費用項目単位）。 */
    messageCount: number;
    hasComments: boolean;
    unreadCount: number;
}

/** 案件（実行予算）1件。 */
export interface OrderDeliveryProject {
    id: number;
    no: number;
    name: string;
    /** 発注書（felix_total の発注書確認画面）を iframe で開く URL。移行元が無ければ null。 */
    orderDocumentUrl: string | null;
    /** 納品関係書類（felix_total）を iframe で開く URL。移行元が無ければ null。 */
    deliveryDocumentUrl: string | null;
    /** 完了報告書（felix_total）を iframe で開く URL。移行元が無ければ null。 */
    completionReportUrl: string | null;
    rows: OrderDeliveryRow[];
}

export interface OrderDeliveryPagination {
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
    from: number | null;
    to: number | null;
}

export interface OrderDeliveryFilters {
    keyword: string;
    itemLabel: string;
    vendor: string;
    /** 承諾確認フィルタ（業者承諾確認画面のみ）：all=全て / confirmed=確認済 / pending=未完了。 */
    acceptance?: string;
    /** 請求月フィルタ（完了確認画面のみ）：last=先月 / current=当月 / next=来月。 */
    billingMonth?: string;
}

/** 画面モード（8画面）。 */
export type OrderDeliveryMode =
    | 'order-execution'
    | 'order-approval'
    | 'order-cancel-request'
    | 'order-cancel-approval'
    | 'order-acceptance'
    | 'delivery-report'
    | 'delivery-approval'
    | 'invoice-approval';

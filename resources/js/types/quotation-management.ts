// 見積管理画面（見積り依頼 / 業者選定 ほか）の型。
// サーバの BuildingQuotationResource と一致させること。

/** 案件 → 項目 → 見積先 を展開した1行。 */
export interface QuotationManagementRow {
    unitId: number;
    companyId: number | null;
    /** 項目名（全行に入る）。表示は「表示中の同一項目の先頭行」にのみ出す（QuotationProjectCard 側で制御）。 */
    itemName: string;
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
    /** 見積依頼済み（送信回数が1回以上）。 */
    requested: boolean;
    /** 見積依頼の送信回数（t_cost_quotation_requests の件数）。0=未依頼。見積依頼画面のみ意味を持つ。 */
    sendCount: number;
    /**
     * 最終依頼日時（t_cost_quotation_requests.requested_at の最大値。`Y/m/d H:i` 整形済み）。
     * 未依頼および見積依頼以外の画面は null。見積依頼画面のみ意味を持つ。
     */
    lastRequestedAt: string | null;
    /** やり取り（コメント）の件数（費用項目単位）。業者選定・部長承認の「やり取り」列に表示。 */
    messageCount: number;
    /** やり取り（コメント）が1件以上あるか。コメントボタンを選定ボタンと同色にする判定に使う。 */
    hasComments: boolean;
    /** やり取りの未読数（自分の最終既読より新しい他者コメント）。0=未読なし。 */
    unreadCount: number;
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
    /**
     * 請求先（新スキーマ t_cost_quotations.is_billing_target）。
     * 業者追加時に「請求先とする」をONにした業者。見積依頼画面では金額3列・仮選定を「ー」表示にし、
     * 操作列はチェック不要で即時送信する「見積送信」ボタンにする。
     */
    billingTarget: boolean;
    /** 部長承認で否認され業者選定へ差し戻された（deny_comment あり）。ボタンの赤色表示に使う。 */
    denied: boolean;
}

/** やり取り（コメント履歴）の添付ファイル1件。 */
export interface QuotationChatFile {
    id: number;
    name: string;
    mime: string | null;
    size: number;
    /** 画像（サムネ生成対象）か。 */
    isImage: boolean;
    /** 画像サムネイルのインライン配信URL。画像かつサムネ生成済みのみ。無ければ null。 */
    thumbUrl: string | null;
    /** クリック時の端末ダウンロード用URL（Content-Disposition: attachment）。全ファイル共通のクリック先。 */
    downloadUrl: string;
}

/** やり取り（チャット）の1発言。サーバの QuotationMessageController と一致させること。 */
export interface QuotationChatMessage {
    id: number;
    /** 発言者の役割：manager=建設部部長 / staff=部下。 */
    senderRole: 'manager' | 'staff';
    /** 自分（ログイン中ユーザー）の投稿か。true=右寄せ、false=左寄せで表示する。 */
    isMine: boolean;
    senderName: string;
    body: string;
    createdAt: string | null;
    /** 添付ファイル（無ければ空配列）。 */
    files: QuotationChatFile[];
}

/** 案件（実行予算）1件。 */
export interface QuotationManagementProject {
    id: number;
    no: number | null;
    name: string;
    rows: QuotationManagementRow[];
}

export interface QuotationManagementPagination {
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
    from: number | null;
    to: number | null;
}

export interface QuotationManagementFilters {
    keyword: string;
    itemLabel: string;
    /** 見積先（業者名）での絞り込み。 */
    vendor: string;
    /** 業者選定画面の回答状態フィルタ（全て / 回答あり / 回答なし）。サーバから常に渡る。 */
    answer?: 'all' | 'answered' | 'unanswered';
    /** コメント有無フィルタ（全て / コメントあり / コメントなし）。全画面共通。サーバから常に渡る。 */
    comment?: 'all' | 'has' | 'none';
}

/**
 * 画面モード（列の出し分け / アクション）。
 * quote-request 以外は「業者選定」と同じボタン形式（押下→ヘッダー確定→API→リロード）。
 */
export type QuotationManagementMode =
    | 'quote-request'
    | 'vendor-selection'
    | 'manager-approval'
    | 'cancel-request'
    | 'cancel-approval';

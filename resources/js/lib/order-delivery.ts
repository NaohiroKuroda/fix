import type { OrderDeliveryMode, OrderDeliveryRow } from '@/types/order-delivery';

/** 行の一意キー。発注フローは常に見積先あり（c{companyId}）。 */
export const orderRowKey = (row: OrderDeliveryRow): string => `c${row.companyId}`;

/** 操作列の種類。checkbox=発注実行（複数選択で一括）／pick-button=承認・確認。 */
export type OrderActionKind = 'checkbox' | 'pick-button';

/**
 * 金額列の構成。
 * pre-order=標準単価/予算単価/相見積、post-order=予算単価/見積/発注、order-only=発注のみ。
 */
export type PriceMode = 'pre-order' | 'post-order' | 'order-only';

export interface OrderDeliveryModeConfig {
    /** 画面タイトル。 */
    title: string;
    /** タイトル横の状態ラベル。 */
    statusLabel: string;
    /** 操作列の種類。 */
    kind: OrderActionKind;
    /** 操作列の見出し。 */
    columnLabel: string;
    /** 未選択時の行ボタン文言。 */
    idleLabel: string;
    /** 選択中の行ボタン文言。 */
    activeLabel: string;
    /** ヘッダー一括ボタンの文言。 */
    actionLabel: string;
    /** ヘッダー一括ボタンの送信中文言。 */
    processingLabel: string;
    /** ヘッダー一括ボタンが非活性のときのツールチップ。 */
    hint: string;
    /** 金額列の構成。発注承認以降は post-order（予算単価/見積/発注）。 */
    priceMode: PriceMode;
    /** 否認（差し戻し・理由必須）列を出すか。 */
    showReject: boolean;
    /** 発注書ボタン列を出すか（発注実行・発注承認・業者承諾確認画面）。 */
    showOrderDocument: boolean;
    /** 発注日列を出すか（発注書の右隣・業者承諾確認画面のみ）。 */
    showOrderDate: boolean;
    /**
     * 区分（請求／支払）列をパートナーの左に出すか（業者承諾確認・発注取消承認画面）。
     * 見積依頼画面と同じ見た目（BillingKindBadge）・同じ元データ（is_billing_target）を使う。
     */
    showBillingKind: boolean;
    /**
     * 操作列（承諾確認→承諾日）で、既に承諾済み（vendorAcceptedAt あり）の行はボタンの代わりに
     * その日付を表示するか（業者承諾確認画面のみ）。
     */
    showAcceptedDate: boolean;
    /** 取消申請（理由必須モーダル）列を出すか（業者承諾確認画面のみ）。 */
    showCancelRequest: boolean;
    /**
     * 行ごとに理由入力モーダル→単体実行にするか（発注取消承認画面のみ）。
     * true のときヘッダー一括ボタン・全て選択は出さず、主操作ボタン押下で直接モーダルを開く。
     * 見積管理の cancel-request / cancel-approval と同じ考え方。
     */
    isPerRowAction: boolean;
    /**
     * 操作列を隠し、代わりに「提出日／確認日／請求日」の3列だけにするか（完了確認画面のみ）。
     * true のときヘッダー一括ボタン・全て選択も出さない。確認日は未確認ボタン→単体実行、
     * 請求日は請求書作成後にクリックで詳細モーダルを開く。
     */
    isCompletionCheck: boolean;
    /**
     * 「提出日／確認日／請求日」の3列を出すか（完了確認・部長完了承認）。
     * isCompletionCheck と異なり、承認・否認など通常の操作列と併用できる
     * （部長完了承認は進捗3列＋承認＋否認）。
     */
    showCompletionColumns: boolean;
    bulkSelectLabel: string;
    bulkClearLabel: string;
}

/**
 * 画面モードごとの操作 UI 定義。発注〜納品フローの7画面で共用する。
 * 見積管理（QUOTATION_MODE_CONFIG）と同じ考え方。全画面「物件カード＋右端ボタン＋チャット」。
 */
export const ORDER_DELIVERY_MODE_CONFIG: Record<OrderDeliveryMode, OrderDeliveryModeConfig> = {
    'order-execution': {
        title: '発注実行',
        statusLabel: '【承認済み見積→未発注】',
        kind: 'checkbox',
        columnLabel: '発注',
        idleLabel: '発注する',
        activeLabel: '発注',
        actionLabel: '発注を実行',
        processingLabel: '実行中…',
        hint: '発注する見積先を選択してください',
        priceMode: 'pre-order',
        showReject: false,
        showOrderDocument: true,
        showOrderDate: false,
        showBillingKind: false,
        showAcceptedDate: false,
        showCancelRequest: false,
        isPerRowAction: false,
        isCompletionCheck: false,
        showCompletionColumns: false,
        bulkSelectLabel: '全て発注',
        bulkClearLabel: '全ての発注を解除',
    },
    'order-approval': {
        title: '発注承認',
        statusLabel: '【担当者実行済→部長承認待ち】',
        kind: 'pick-button',
        columnLabel: '承認',
        idleLabel: '承認する',
        activeLabel: '承認',
        actionLabel: '発注を承認',
        processingLabel: '承認中…',
        hint: '承認する発注を選択してください',
        priceMode: 'post-order',
        showReject: true,
        showOrderDocument: true,
        showOrderDate: false,
        showBillingKind: false,
        showAcceptedDate: false,
        showCancelRequest: false,
        isPerRowAction: false,
        isCompletionCheck: false,
        showCompletionColumns: false,
        bulkSelectLabel: '全て承認',
        bulkClearLabel: '全ての承認を解除',
    },
    'order-cancel-request': {
        title: '発注取消申請',
        statusLabel: '【発注承認済み→部長取消承認待ち】',
        kind: 'pick-button',
        columnLabel: '取消申請',
        idleLabel: '取消申請',
        activeLabel: '申請',
        actionLabel: '発注取消を申請',
        processingLabel: '申請中…',
        hint: '取消申請する発注を選択してください',
        priceMode: 'post-order',
        showReject: false,
        showOrderDocument: false,
        showOrderDate: false,
        showBillingKind: false,
        showAcceptedDate: false,
        showCancelRequest: false,
        isPerRowAction: false,
        isCompletionCheck: false,
        showCompletionColumns: false,
        bulkSelectLabel: '全て取消申請',
        bulkClearLabel: '全ての取消申請を解除',
    },
    'order-cancel-approval': {
        title: '発注取消承認',
        statusLabel: '【取消申請中→部長取消承認待ち】',
        kind: 'pick-button',
        columnLabel: '取消承認',
        idleLabel: '取消承認',
        activeLabel: '承認',
        actionLabel: '発注取消を承認',
        processingLabel: '承認中…',
        hint: '取消承認する発注を選択してください',
        priceMode: 'order-only',
        showReject: false,
        showOrderDocument: false,
        showOrderDate: false,
        showBillingKind: true,
        showAcceptedDate: false,
        showCancelRequest: false,
        isPerRowAction: true,
        isCompletionCheck: false,
        showCompletionColumns: false,
        bulkSelectLabel: '全て取消承認',
        bulkClearLabel: '全ての取消承認を解除',
    },
    'order-acceptance': {
        title: '業者承諾確認',
        statusLabel: '【発注承認済み→業者承諾待ち】',
        kind: 'pick-button',
        columnLabel: '承諾日',
        // 承諾日＝業者が承諾した日。未承諾は空白（ボタン自体は選択トグルとして機能する）。
        idleLabel: '',
        activeLabel: '',
        actionLabel: '承諾を確認',
        processingLabel: '確認中…',
        hint: '承諾を確認する発注を選択してください',
        priceMode: 'order-only',
        showReject: false,
        showOrderDocument: true,
        showOrderDate: true,
        showBillingKind: true,
        showAcceptedDate: true,
        showCancelRequest: true,
        isPerRowAction: false,
        isCompletionCheck: false,
        showCompletionColumns: false,
        bulkSelectLabel: '全て確認',
        bulkClearLabel: '全ての確認を解除',
    },
    'delivery-report': {
        title: '完了確認',
        statusLabel: '【業者承諾済み→提出・確認・請求の進捗確認】',
        kind: 'pick-button',
        columnLabel: '確認',
        idleLabel: '未確認',
        activeLabel: '確認',
        actionLabel: '報告書を確認',
        processingLabel: '確認中…',
        hint: '確認する報告書を選択してください',
        priceMode: 'order-only',
        showReject: false,
        showOrderDocument: false,
        showOrderDate: false,
        showBillingKind: false,
        showAcceptedDate: false,
        showCancelRequest: false,
        isPerRowAction: false,
        isCompletionCheck: true,
        showCompletionColumns: true,
        bulkSelectLabel: '全て確認',
        bulkClearLabel: '全ての確認を解除',
    },
    'delivery-approval': {
        title: '部長完了承認',
        statusLabel: '【報告書受領済み→部長承認待ち】',
        kind: 'pick-button',
        columnLabel: '承認',
        idleLabel: '承認する',
        activeLabel: '承認',
        actionLabel: '納品を承認',
        processingLabel: '承認中…',
        hint: '承認する納品報告を選択してください',
        priceMode: 'order-only',
        showReject: true,
        showOrderDocument: false,
        showOrderDate: false,
        showBillingKind: false,
        showAcceptedDate: false,
        showCancelRequest: false,
        isPerRowAction: false,
        isCompletionCheck: false,
        showCompletionColumns: true,
        bulkSelectLabel: '全て承認',
        bulkClearLabel: '全ての承認を解除',
    },
    'invoice-approval': {
        title: '請求取消承認',
        statusLabel: '【請求書作成済み→取消確認】',
        kind: 'pick-button',
        columnLabel: '取消確認',
        idleLabel: '取消確認する',
        activeLabel: '取消確認',
        actionLabel: '請求取消を確定',
        processingLabel: '処理中…',
        hint: '取消確定する請求を選択してください',
        priceMode: 'order-only',
        showReject: false,
        showOrderDocument: false,
        showOrderDate: false,
        showBillingKind: false,
        showAcceptedDate: false,
        showCancelRequest: false,
        isPerRowAction: false,
        isCompletionCheck: false,
        showCompletionColumns: true,
        bulkSelectLabel: '全て取消確認',
        bulkClearLabel: '全ての取消確認を解除',
    },
};

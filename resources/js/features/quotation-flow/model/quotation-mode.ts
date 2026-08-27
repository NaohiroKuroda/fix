import type { QuotationManagementMode, QuotationManagementRow } from './quotation';

/**
 * 明細行の一意キー。取引先ありは区分＋id（支払=c{id} / 請求=b{id}）、取引先なしは unitId（u{id}）。
 * v-for の key と選択・選定・仮選定などのローカル状態キーに共用する。
 *
 * ※ 区分（支払 / 請求）を先頭に付けるのは必須。支払は t_payable_partners、請求は t_billing_partners と
 *   別テーブルで id が独立採番のため、区分フィルタ「全て」で両方を並べると id が衝突しうる。
 *   衝突すると v-for の key が重複して行の表示が壊れ（絞り込みで消えたまま戻らない）、
 *   さらに選択状態が支払行と請求行で混線する。
 */
export const quotationRowKey = (row: QuotationManagementRow): string =>
    row.companyId != null ? `${row.billingTarget ? 'b' : 'c'}${row.companyId}` : `u${row.unitId}`;

/**
 * 操作（最終列）の種類。
 * - checkbox      : 見積依頼。未依頼行をチェックで選び、ヘッダー送信で確定（チェックボックス UI）。
 * - pick-button   : 部長承認 / 取消申請 / 取消承認。未処理行をボタンで選び、ヘッダー確定で送信。
 *                   処理済み行は静的バッジ（依頼済みと同様、再操作不可）。
 * - toggle-button : 業者選定。サーバ状態を初期値に押下でトグル（業者の選び替え）。
 */
export type QuotationActionKind = 'checkbox' | 'pick-button' | 'toggle-button';

/** 行の「処理済み」を表すサーバ側フラグ（boolean プロパティ名）。 */
export type QuotationAppliedKey = 'requested' | 'selected' | 'approved' | 'cancelRequested' | 'cancelApproved';

export interface QuotationModeConfig {
    kind: QuotationActionKind;
    /** 最終列のヘッダー名。 */
    columnLabel: string;
    /** 相見積（税抜）列を表示するか（業者選定以降の画面で表示）。 */
    showQuote: boolean;
    /** 見積金額列（showQuote=true）の見出し。未指定は「相見積」。例: 部長取消申請=「確定見積」。 */
    quoteColumnLabel?: string;
    /** 行が「処理済み」かを表すサーバ側フラグ名。 */
    appliedKey: QuotationAppliedKey;
    /**
     * 処理済みでも再操作を許す（ロックしない）か。
     * 見積依頼は何度でも依頼できるよう true（依頼済みでもチェック可・静的バッジを出さない）。
     */
    reselectable?: boolean;
    /** 操作対象（未処理）行の操作前ラベル。 */
    idleLabel: string;
    /** 選択中（押下済み・送信待ち）／トグル ON のラベル。 */
    activeLabel: string;
    /** 処理済み行の静的バッジ文言（pick-button のみ）。 */
    appliedLabel: string;
    /** ヘッダー確定ボタンの送信中ラベル。 */
    processingLabel: string;
    /** ヘッダー確定ボタンが非活性のときのツールチップ。 */
    hint: string;
    /**
     * 一括選択ボタンの表示名（未選択時）。例: 「全て依頼」「全て承認」。
     * 未指定の場合は一括選択ボタン自体を出さない（業者選定は 1 業者を選ぶ画面のため未指定）。
     */
    bulkSelectLabel?: string;
    /** 一括選択ボタンの表示名（全選択済み＝解除時）。例: 「全ての依頼を解除」。 */
    bulkClearLabel?: string;
}

/**
 * 画面モードごとの操作 UI 定義（最終列のラベル・操作種別・処理済み判定）。
 * 画面コンテナ（QuotationManagementScreen）と明細カード（QuotationProjectCard）で共用する。
 */
export const QUOTATION_MODE_CONFIG: Record<QuotationManagementMode, QuotationModeConfig> = {
    'quote-request': {
        kind: 'checkbox',
        columnLabel: '見積依頼／送信',
        // 予算単価の右隣に「相見積」列（業者から返ってきた見積提示額）を表示する。
        showQuote: true,
        appliedKey: 'requested',
        // 何度でも依頼できるよう、依頼済みでもチェック可（静的バッジは出さない）。
        reselectable: true,
        idleLabel: '未依頼',
        activeLabel: '依頼',
        appliedLabel: '依頼済み',
        processingLabel: '送信中…',
        hint: '送信する行をチェックしてください',
        bulkSelectLabel: '全て依頼',
        bulkClearLabel: '全ての依頼を解除',
    },
    'vendor-selection': {
        kind: 'toggle-button',
        columnLabel: '業者選定',
        showQuote: true,
        appliedKey: 'selected',
        idleLabel: '選定する',
        activeLabel: '選定',
        appliedLabel: '選定済',
        processingLabel: '確定中…',
        hint: '発注業者を選定してください',
        // 業者選定は案件ごとに 1 業者を選ぶ画面のため、一括選択（全て選定）は用意しない。
    },
    'manager-approval': {
        kind: 'pick-button',
        columnLabel: '承認',
        showQuote: true,
        quoteColumnLabel: '確定見積',
        appliedKey: 'approved',
        idleLabel: '承認する',
        activeLabel: '承認',
        appliedLabel: '承認済',
        processingLabel: '承認中…',
        hint: '承認する見積先を選択してください',
        bulkSelectLabel: '全て承認',
        bulkClearLabel: '全ての承認を解除',
    },
    'cancel-request': {
        kind: 'pick-button',
        columnLabel: '取消申請',
        showQuote: true,
        quoteColumnLabel: '確定見積',
        appliedKey: 'cancelRequested',
        idleLabel: '取消申請',
        activeLabel: '申請',
        appliedLabel: '申請済',
        processingLabel: '申請中…',
        hint: '取消申請する見積先を選択してください',
        bulkSelectLabel: '全て取消申請',
        bulkClearLabel: '全ての取消申請を解除',
    },
    'cancel-approval': {
        kind: 'pick-button',
        columnLabel: '取消承認',
        showQuote: true,
        quoteColumnLabel: '確定見積',
        appliedKey: 'cancelApproved',
        idleLabel: '取消承認',
        activeLabel: '承認',
        appliedLabel: '承認済',
        processingLabel: '承認中…',
        hint: '取消承認する見積先を選択してください',
        bulkSelectLabel: '全て取消承認',
        bulkClearLabel: '全ての取消承認を解除',
    },
};

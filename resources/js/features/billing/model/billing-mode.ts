import type { BillingMode } from './billing';

/**
 * 操作（最終列）の種類。
 * - modal    : 見積作成。行ボタンで見積入力モーダルを開き、1件ずつ保存する。
 * - pick     : 見積承認。未処理行をボタンで選び、ヘッダーの確定で一括送信する。
 * - per-row  : 取消申請 / 取消承認。行ボタンで理由入力モーダルを開き、1件ずつ実行する。
 * - view     : 発注書確認。参照のみ（承諾日の表示と発注書プレビュー）。
 */
export type BillingActionKind = 'modal' | 'pick' | 'per-row' | 'view';

export interface BillingModeConfig {
    kind: BillingActionKind;
    /** 画面タイトル（【請求】は共通なのでヘッダー側で付ける）。 */
    title: string;
    /** 状態ラベル（サイドメニューの表記と揃える）。 */
    statusLabel: string;
    /** ヘッダー一括アクションのボタン名。null＝出さない（modal / per-row / view）。 */
    actionLabel: string | null;
    /** 送信先 URL（POST）。view は null。 */
    actionUrl: string | null;
    /** 金額列の見出し。 */
    amountColumnLabel: string;
    /** 最終列（操作列）の見出し。 */
    columnLabel: string;
    /** 操作ボタンの通常ラベル。 */
    idleLabel: string;
    /** 選択中（pick のみ）のラベル。 */
    activeLabel: string;
    /** 送信中ラベル。 */
    processingLabel: string;
    /** ヘッダー確定ボタンが非活性のときのツールチップ。 */
    hint: string;
    bulkSelectLabel: string;
    bulkClearLabel: string;
    /** 承諾日列を出すか（発注書確認のみ）。 */
    showAcceptedAt: boolean;
    /** 理由入力を必須にするか（取消系）。 */
    reasonRequired: boolean;
}

/**
 * 画面モードごとの操作 UI 定義。
 * もらいは相見積・業者選定が無いため、標準単価 / 予算単価 / 仮選定は全モードで「—」固定
 * （docs/operations/もらい_運用フロー.drawio ④⑦）。
 */
export const BILLING_MODE_CONFIG: Record<BillingMode, BillingModeConfig> = {
    'billing-quote-create': {
        kind: 'modal',
        title: '見積作成',
        statusLabel: '【FELIX(担当者)→業者】',
        actionLabel: null,
        actionUrl: '/quotation-management/billing-quote-create',
        amountColumnLabel: '見積額',
        columnLabel: '見積作成',
        idleLabel: '見積作成',
        activeLabel: '見積修正',
        processingLabel: '保存中…',
        hint: '見積を作成する請求先を選択してください',
        bulkSelectLabel: '全て選択',
        bulkClearLabel: '全ての選択を解除',
        showAcceptedAt: false,
        reasonRequired: false,
    },
    'billing-quote-approval': {
        kind: 'pick',
        title: '見積承認',
        statusLabel: '【FELIX(建設部部長)→業者】',
        actionLabel: '見積承認',
        actionUrl: '/quotation-management/billing-quote-approval',
        amountColumnLabel: '見積額',
        columnLabel: '承認',
        idleLabel: '承認する',
        activeLabel: '承認',
        processingLabel: '承認中…',
        hint: '承認する請求先を選択してください',
        bulkSelectLabel: '全て承認',
        bulkClearLabel: '全ての承認を解除',
        showAcceptedAt: false,
        reasonRequired: false,
    },
    'billing-cancel-request': {
        kind: 'per-row',
        title: '見積取消申請',
        statusLabel: '【FELIX(担当者)→FELIX(建設部部長)】',
        actionLabel: null,
        actionUrl: '/quotation-management/billing-cancel-request',
        amountColumnLabel: '確定見積',
        columnLabel: '取消申請',
        idleLabel: '取消申請',
        activeLabel: '申請',
        processingLabel: '申請中…',
        hint: '取消申請する請求先を選択してください',
        bulkSelectLabel: '全て取消申請',
        bulkClearLabel: '全ての取消申請を解除',
        showAcceptedAt: false,
        reasonRequired: true,
    },
    'billing-cancel-approval': {
        kind: 'per-row',
        title: '見積取消承認',
        statusLabel: '【FELIX(建設部部長)→FELIX(担当者)】',
        actionLabel: null,
        actionUrl: '/quotation-management/billing-cancel-approval',
        amountColumnLabel: '確定見積',
        columnLabel: '取消承認',
        idleLabel: '取消承認',
        activeLabel: '承認',
        processingLabel: '承認中…',
        hint: '取消承認する請求先を選択してください',
        bulkSelectLabel: '全て取消承認',
        bulkClearLabel: '全ての取消承認を解除',
        showAcceptedAt: false,
        reasonRequired: true,
    },
    'billing-order-confirmation': {
        kind: 'view',
        title: '発注書確認',
        statusLabel: '【FELIX(建設部部長)】',
        actionLabel: null,
        actionUrl: null,
        amountColumnLabel: '発注額',
        columnLabel: '発注書',
        idleLabel: '発注書',
        activeLabel: '発注書',
        processingLabel: '',
        hint: '',
        bulkSelectLabel: '',
        bulkClearLabel: '',
        showAcceptedAt: true,
        reasonRequired: false,
    },
};

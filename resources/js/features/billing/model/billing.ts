// 請求（もらい）系画面の型。サーバの BillingMockService（将来は BillingPartnerResource）と一致させること。
//
// もらいは「相見積・業者選定が発生しない」ため、支払（quotation-flow）と列構成・操作が異なる。
// 詳細設計: docs/detailed-design/quotations/06〜09_請求_*_詳細設計.md

import type { ProjectFilters } from '@/shared/api';

/** 一覧のページネーション。形は他フローと共通なので shared/api の型をそのまま使う。 */
export type { Pagination as BillingPagination } from '@/shared/api';

/**
 * 請求取引先の承認ステータス（t_billing_partners.approval_status）。
 * 値はテーブル定義書に準拠する（支払側の実装が使う MANAGER_APPROVED / CANCEL_REQUESTED とは異なる）。
 */
export type BillingApprovalStatus =
    | 'UNSELECTED'
    | 'STAFF_APPROVED'
    | 'APPROVED'
    | 'CANCEL_APPLIED'
    | 'CANCEL_APPROVED';

/** 案件 → 項目 → 請求取引先 を展開した1行。 */
export interface BillingRow {
    /** 請求取引先ID（t_billing_partners.id）。 */
    partnerId: number;
    /** 項目名（t_building_budget_items.name）。表示は同一項目の先頭行にのみ出す。 */
    itemName: string;
    /** 請求先の会社名。 */
    vendorName: string;
    /** 見積先の詳細（iframe で開く felix_total の編集フォーム）。null ならプレーン表示。 */
    vendorDetailUrl: string | null;
    /** 「業者を追加」リンク先（iframe）。項目単位。見積作成画面のみ使う。 */
    addVendorUrl: string | null;
    approvalStatus: BillingApprovalStatus;
    /**
     * 請求見積の税抜合計（t_billing_quotations.amount_excluding_tax の最新版）。
     * 金額は BCMath 前提のため **文字列**で受け取る（frontend.md §4.9）。未作成は null。
     */
    quotationAmount: string | null;
    /** 見積日（`Y/m/d` 整形済み）。未作成は null。 */
    quotationDate: string | null;
    /** 業者の発注承諾日（`Y/m/d` 整形済み）。発注書確認画面で使う。未承諾は null。 */
    acceptedAt: string | null;
    /** やり取り（コメント）の件数（項目単位）。 */
    messageCount: number;
    hasComments: boolean;
    unreadCount: number;
}

/** 案件（実行予算）1件。 */
export interface BillingProject {
    id: number;
    no: number | null;
    name: string;
    rows: BillingRow[];
}

export interface BillingFilters extends ProjectFilters {
    /** コメント有無フィルタ（全て / コメントあり / コメントなし）。サーバから常に渡る。 */
    comment?: 'all' | 'has' | 'none';
}

/** 画面モード（列の出し分け / アクション）。 */
export type BillingMode =
    | 'billing-quote-create'
    | 'billing-quote-approval'
    | 'billing-cancel-request'
    | 'billing-cancel-approval'
    | 'billing-order-confirmation';

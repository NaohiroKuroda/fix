// 請求（もらい）系画面の型。サーバの BillingMockService（将来は BillingPartnerResource）と一致させること。
//
// もらいは「相見積・業者選定が発生しない」ため、支払（features/payable）と列構成・操作が異なる。
// 詳細設計: docs/detailed-design/quotations/06〜09_請求_*_詳細設計.md

import type { ProjectFilters } from '@/shared/api';

/** 一覧のページネーション。形は他フローと共通なので shared/api の型をそのまま使う。 */
export type { Pagination as BillingPagination } from '@/shared/api';

/**
 * 請求取引先の承認ステータス（t_billing_partners.approval_status）。
 * 値はテーブル定義書に準拠する（支払側の実装が使う MANAGER_APPROVED / CANCEL_REQUESTED とは異なる）。
 */
export type BillingApprovalStatus =
    | 'DRAFT'
    | 'APPLIED'
    | 'APPROVED'
    | 'CANCEL_APPLIED'
    | 'CANCELLED';

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
    /** 区分（true=請求／もらい、false=支払／はらい）。区分トグルで選んだ側が並ぶ。 */
    billingTarget: boolean;
    /**
     * 請求見積の税別合計（t_billing_quotations.subtotal_amount の最新版）。
     * 金額は BCMath 前提のため **文字列**で受け取る（frontend.md §4.9）。未作成は null。
     */
    quotationAmount: string | null;
    /** 見積日（`Y/m/d` 整形済み）。未作成は null。 */
    quotationDate: string | null;
    /** 業者の発注承諾日（`Y/m/d` 整形済み。t_billing_quotations.accepted_at）。未承諾は null。 */
    acceptedAt: string | null;
    /**
     * 発注金額＝発注書の税別合計（t_billing_orders.subtotal_amount）。
     * 金額は BCMath 前提のため文字列で受け取る（frontend.md §4.9）。未発行は null。
     */
    orderAmount: string | null;
    /** 発注承諾日（`Y/m/d` 整形済み。t_billing_orders.contract_approved_at）。未承諾は null。 */
    orderAcceptedAt: string | null;
    /** やり取り（コメント）の件数（項目単位）。 */
    messageCount: number;
    hasComments: boolean;
    unreadCount: number;
    /**
     * 作成済みの請求見積（最新版）。未作成は null。
     * 見積作成モーダルを「見積修正」で開いたときの初期値になる。
     */
    quotation: BillingQuotation | null;
    /**
     * この画面で操作できる行か（処理フロー J列「表示承認ステータス」）。
     * false の行も一覧には出すが、操作は不可にする（K列「ステータス外表示形式」）。
     */
    operable: boolean;
}

/** 状態バッジの表示名（支払側と共通の語彙）。 */
export const BILLING_STATUS_LABEL: Record<BillingApprovalStatus, string> = {
    DRAFT: '未申請',
    APPLIED: '申請中',
    APPROVED: '承認済',
    CANCEL_APPLIED: '取消申請中',
    CANCELLED: '取消承認済',
};

/** 課税区分（t_billing_quotation_details.tax_type）。 */
export type BillingTaxType = 'TAXABLE' | 'NON_TAXABLE';

/**
 * 請求見積の明細1行（t_billing_quotation_details）。
 *
 * 列は felix_total の業者マイページ「見積」タブを踏襲する
 * （拠点 / 部門 / 依頼内容 / 数量 / 単位 / 税区分 / 税種類 / 単価 / 金額）。
 * 金額系は BCMath 前提のため文字列で受け渡す（frontend.md §4.9）。
 */
export interface BillingQuotationDetail {
    id: number | null;
    /** メモ行（依頼内容だけを表示する行）。 */
    isMemo: boolean;
    /** 拠点コード（constant.branch_list）。 */
    branchCode: number | null;
    /** 部署ID（departments.id）。 */
    departmentId: number | null;
    /** 依頼内容（明細名）。 */
    name: string;
    /** 数量。 */
    quantity: number | null;
    /** 単位マスターID（master_units.id）。 */
    unitId: number | null;
    /** 単価。 */
    unitPrice: string | null;
    /** 課税区分。 */
    taxType: BillingTaxType;
    /** 消費税率（DECIMAL(3,2) をそのまま文字列で保持。例 "0.10"）。 */
    taxRate: string;
    /** 税込フラグ（felix_total の「税種類」＝税別/税込）。 */
    isTaxInclusive: boolean;
    /** 金額（数量 × 単価。手入力も可）。 */
    price: string | null;
    /**
     * 使用中の行か（t_billing_quotation_details.is_changed）。
     * felix_total の fix_flg と同じで、空の予備行は 0 で保存される。
     * **画面には is_changed = true の行だけを表示する。**
     */
    isChanged: boolean;
}

/** 請求見積（t_billing_quotations）の最新版 ＋ 明細。 */
export interface BillingQuotation {
    id: number | null;
    /** 見積日（`YYYY-MM-DD`。input[type=date] にそのまま入れる）。 */
    quotationDate: string;
    /** 税抜合計。 */
    amountExcludingTax: string | null;
    /** 消費税調整（端数調整・手入力調整の差分）。 */
    taxAdjust: string | null;
    /** 源泉所得及び復興特別所得税。 */
    withholdingIncomeTax: string | null;
    comment: string;
    fileUrl: string;
    details: BillingQuotationDetail[];
}

/** モーダルの選択肢（サーバから渡すマスタ）。 */
export interface BillingMasters {
    /** 拠点（constant.branch_list）。 */
    branches: { code: number; name: string }[];
    /** 部署（departments）。 */
    departments: { id: number; name: string }[];
    /** 単位（master_units）。 */
    units: { id: number; name: string }[];
}

/** 案件（実行予算）1件。 */
export interface BillingProject {
    id: number;
    no: number | null;
    name: string;
    /** 発注書（発注書確認画面のボタン）で開く felix_total の発注書確認画面 URL。未設定は null。 */
    orderDocumentUrl?: string | null;
    rows: BillingRow[];
}

export interface BillingFilters extends ProjectFilters {
    /** コメント有無フィルタ（全て / コメントあり / コメントなし）。サーバから常に渡る。 */
    comment?: 'all' | 'has' | 'none';
    /**
     * 区分。請求系画面の初期値は `billing`。
     * `all` にすると支払取引先も同じ一覧に並ぶ（支払行は**表示のみ**で操作不可）。
     */
    kind?: 'all' | 'payable' | 'billing';
}

/** 画面モード（列の出し分け / アクション）。 */
export type BillingMode =
    | 'billing-quote-create'
    | 'billing-quote-approval'
    | 'billing-cancel-request'
    | 'billing-cancel-approval'
    | 'billing-order-confirmation';

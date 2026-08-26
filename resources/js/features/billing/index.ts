// 請求（もらい）フロー（見積作成 / 見積承認 / 見積取消申請 / 見積取消承認 / 発注書確認）の public API。
// この5画面は同じ操作フローを mode で出し分けるため、1スライスにまとめている。
// スライス外からは必ずこのファイル経由で参照すること（内部ファイルへの直接 import は禁止）。
export { default as BillingScreen } from './ui/BillingScreen.vue';

export { BILLING_STATUS_LABEL } from './model/billing';

export type {
    BillingApprovalStatus,
    BillingMasters,
    BillingQuotation,
    BillingQuotationDetail,
    BillingTaxType,
    BillingFilters,
    BillingMode,
    BillingPagination,
    BillingProject,
    BillingRow,
} from './model/billing';

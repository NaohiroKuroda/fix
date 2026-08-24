// 見積管理フロー（見積依頼 / 業者選定 / 部長承認 / 取消申請 / 取消承認）の public API。
// この5画面は同じ操作フローを mode で出し分けるため、1スライスにまとめている。
// スライス外からは必ずこのファイル経由で参照すること（内部ファイルへの直接 import は禁止）。
export { default as QuotationManagementScreen } from './ui/QuotationManagementScreen.vue';

export type {
    QuotationManagementFilters,
    QuotationManagementMode,
    QuotationManagementPagination,
    QuotationManagementProject,
    QuotationManagementRow,
} from './model/quotation';

// 見積管理【支払】5画面（見積依頼 / 業者選定 / 部長承認 / 取消申請 / 取消承認）の public API。
// 起点テーブルは t_payable_partners（支払取引先）。請求（もらい）側は features/billing。
// この5画面は同じ操作フローを mode で出し分けるため、1スライスにまとめている。
// スライス外からは必ずこのファイル経由で参照すること（内部ファイルへの直接 import は禁止）。
export { default as PayableScreen } from './ui/PayableScreen.vue';

export { PAYABLE_STATUS_LABEL } from './model/payable';

export type {
    PayableApprovalStatus,
    PayableFilters,
    PayableMode,
    PayablePagination,
    PayableProject,
    PayableRow,
} from './model/payable';

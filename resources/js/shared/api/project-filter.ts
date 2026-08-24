// 一覧画面の共通絞り込み条件（物件名 / 項目名 / 見積先）。
// shared/ui/filter-bar が扱うのはこの3項目のみ。画面固有の条件は各フローの
// model 側でこの型を extends して足す（例: QuotationManagementFilters）。

export interface ProjectFilters {
    keyword: string;
    itemLabel: string;
    /** 見積先（業者名）での絞り込み。 */
    vendor: string;
}

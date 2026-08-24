// ページネーション（Laravel の LengthAwarePaginator と一致させる）。
// 見積管理・発注納品の両フローで同じ形なので shared/api に置く。

export interface Pagination {
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
    from: number | null;
    to: number | null;
}

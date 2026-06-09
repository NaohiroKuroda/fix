// 見積/原価明細のステータス → バッジ variant の対応を一元化する composable。
//
// 旧実装では CostTable.vue と EstimateSection.vue が別々に variantOf() を持ち、
// 同じ state に対して色が食い違っていた（例: approved が info / success）。
// ここで意味ベースの単一マッピングに統一する。
//
// 配色の意味づけ:
//   success(緑)   … 承認済 / あり（完了・肯定）
//   info(青)      … 送信済 / 選定 / 回答済（進行中の情報）
//   warning(黄)   … 未承認 / 保留
//   danger(赤)    … 否認
//   destructive   … キャンセル
//   secondary     … 通知済（控えめ）
//   muted(灰)     … なし / 未設定

export type EstimateStateVariant =
    | 'default'
    | 'secondary'
    | 'destructive'
    | 'outline'
    | 'success'
    | 'warning'
    | 'muted'
    | 'info'
    | 'danger';

const STATE_VARIANT: Record<string, EstimateStateVariant> = {
    none: 'muted',
    pending: 'warning',
    notified: 'secondary',
    sent: 'info',
    selected: 'info',
    replied: 'info',
    approved: 'success',
    has: 'success',
    denied: 'danger',
    canceled: 'destructive',
};

export function useEstimateStatus() {
    /** state 文字列 → バッジ variant（未知の値は muted）。 */
    const variantOf = (state: string): EstimateStateVariant => STATE_VARIANT[state] ?? 'muted';

    return { variantOf };
}

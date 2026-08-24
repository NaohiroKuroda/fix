// 表示用フォーマッタ（各コンポーネントに散在していた整形関数を集約）。

/** 金額を「¥1,234」表記に。null/undefined は「—」。 */
export const yen = (n: number | null | undefined): string =>
    n === null || n === undefined ? '—' : '¥' + n.toLocaleString('ja-JP');

/** パーセント表記。null/undefined は「—」。 */
export const percent = (n: number | null | undefined): string =>
    n === null || n === undefined ? '—' : `${n}%`;

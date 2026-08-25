// 表示用フォーマッタ（各コンポーネントに散在していた整形関数を集約）。

/** 金額を「¥1,234」表記に。null/undefined は「—」。 */
export const yen = (n: number | null | undefined): string =>
    n === null || n === undefined ? '—' : '¥' + n.toLocaleString('ja-JP');

/** パーセント表記。null/undefined は「—」。 */
export const percent = (n: number | null | undefined): string =>
    n === null || n === undefined ? '—' : `${n}%`;

/**
 * 文字列で受け取った金額（BCMath 由来）を「¥1,234」表記に。null/undefined/空は「—」。
 *
 * 小数を含む値は number へ変換した時点で IEEE 754 の誤差が確定するため、
 * サーバからは文字列で受け取り、フロントでは桁区切りだけ行う
 * （docs/architecture/frontend.md §4.9 / backend.md §5.5）。
 */
export const yenString = (v: string | null | undefined): string => {
    if (v === null || v === undefined || v === '') {
        return '—';
    }
    const sign = v.startsWith('-') ? '-' : '';
    const [intPart = '0', decimalPart] = v.replace(/^[-+]/, '').split('.');
    const grouped = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    // 小数が全て 0 なら整数として表示する（例: "1200.00" → ¥1,200）。
    const fraction = decimalPart && /[1-9]/.test(decimalPart) ? `.${decimalPart}` : '';

    return `¥${sign}${grouped}${fraction}`;
};

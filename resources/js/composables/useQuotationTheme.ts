import { computed, toValue, type MaybeRefOrGetter } from 'vue';

/**
 * 見積管理画面の配色トークン。
 *
 * glass=true は FELIX ブランド（ロゴの紺＝primary＋金 #c4a35b）に接地し、
 * 「明細カードは白／それ以外はリキッドグラス」。glass=false は標準（bg-card など）。
 *
 * create-adaptable-composable 方針に従い、入力は MaybeRefOrGetter で受け toValue で正規化する。
 */
export function useQuotationTheme(isThemedInput: MaybeRefOrGetter<boolean>) {
    const themed = computed(() => toValue(isThemedInput));

    const rootClass = computed(() =>
        themed.value ? 'relative min-h-dvh bg-gradient-to-b from-slate-50 via-slate-100 to-slate-200' : '',
    );
    const stickyBgClass = computed(() =>
        themed.value ? 'bg-white/50 backdrop-blur-2xl backdrop-saturate-150' : 'bg-white/90 backdrop-blur-md',
    );
    const glassPanelClass = computed(() =>
        themed.value
            ? 'rounded-2xl border border-white/60 bg-white/25 shadow-[0_8px_30px_rgba(22,35,78,0.14)] ring-1 ring-inset ring-white/40 backdrop-blur-2xl backdrop-saturate-150'
            : 'rounded-lg border bg-card',
    );
    const detailCardClass = computed(() =>
        themed.value ? 'rounded-xl border border-slate-200 bg-white shadow-sm' : 'rounded-lg border bg-card',
    );
    const inputClass = computed(() =>
        themed.value
            ? 'h-9 w-56 rounded-xl border border-slate-300 bg-white/80 px-3 text-sm text-slate-800 shadow-inner backdrop-blur-md placeholder:text-slate-400 focus:border-primary focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20'
            : 'h-9 w-56 rounded-md border px-2 text-sm',
    );
    const primaryBtnClass = computed(() =>
        themed.value
            ? 'h-9 rounded-xl bg-primary px-4 text-sm font-semibold text-primary-foreground shadow-sm transition hover:opacity-90'
            : 'h-9 rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground transition-opacity hover:opacity-90',
    );
    const pagerBtnClass = computed(() =>
        themed.value
            ? 'rounded-lg border border-primary/20 bg-white/70 px-5 py-2 text-sm font-bold text-primary backdrop-blur-md transition hover:bg-white disabled:cursor-not-allowed disabled:opacity-40'
            : 'rounded-md border bg-card px-5 py-2 text-sm font-bold transition-colors hover:bg-accent disabled:cursor-not-allowed disabled:opacity-40',
    );
    const chipClass = computed(() =>
        themed.value
            ? 'rounded-full border border-primary/25 bg-primary/10 px-3 py-0.5 text-sm font-semibold text-primary backdrop-blur-md'
            : 'rounded-md bg-primary/10 px-2 py-0.5 text-sm font-semibold text-primary',
    );
    const headingClass = computed(() => (themed.value ? 'text-primary' : ''));
    const onGlassTextClass = computed(() => (themed.value ? 'text-slate-600' : 'text-muted-foreground'));
    const mutedTextClass = computed(() => (themed.value ? 'text-slate-500' : 'text-muted-foreground'));
    // 明細タイトル帯：紺ベタ＋白文字＋金キーライン（＝図面の表題欄）。
    const cardHeadClass = computed(() =>
        themed.value ? 'border-l-4 border-l-[#c4a35b] bg-primary text-primary-foreground' : 'border-b bg-muted/40',
    );
    const tableHeadClass = computed(() => (themed.value ? 'bg-zinc-100 text-zinc-700' : 'bg-muted/30 text-muted-foreground'));
    const rowBorderClass = computed(() => (themed.value ? 'border-slate-100' : ''));
    const cellTextClass = computed(() => (themed.value ? 'text-slate-800' : ''));

    return {
        themed,
        rootClass,
        stickyBgClass,
        glassPanelClass,
        detailCardClass,
        inputClass,
        primaryBtnClass,
        pagerBtnClass,
        chipClass,
        headingClass,
        onGlassTextClass,
        mutedTextClass,
        cardHeadClass,
        tableHeadClass,
        rowBorderClass,
        cellTextClass,
    };
}

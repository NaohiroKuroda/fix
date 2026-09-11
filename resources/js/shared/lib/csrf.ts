import { usePage } from '@inertiajs/vue3';

/**
 * CSRF トークン（fetch の `X-XSRF-TOKEN` ヘッダに載せる値）を返す関数を作る。
 *
 * Inertia を通さない fetch で使う。クッキー名は共有プロパティ `xsrfCookieName` から取る
 * （現行 felix_total と同じホストで動かす環境では標準の `XSRF-TOKEN` から名前を変えるため。
 *  → `config/session.php` の `xsrf_cookie`）。
 */
export const useXsrfToken = (): (() => string) => {
    const page = usePage();

    return () => {
        const prefix = `${page.props.xsrfCookieName ?? 'XSRF-TOKEN'}=`;
        const cookie = document.cookie.split('; ').find((c) => c.startsWith(prefix));

        return decodeURIComponent(cookie?.slice(prefix.length) ?? '');
    };
};

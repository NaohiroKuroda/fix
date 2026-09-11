<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * CSRF トークンを載せるクッキーの名前を config（`session.xsrf_cookie`）で変えられるようにする。
 *
 * クッキーはポートを区別しないため、**現行 felix_total と同じホスト名**で新Fix を動かす環境
 * （検証サーバーの `192.168.10.181:8081` と `:8090`）では、両アプリの `XSRF-TOKEN` が
 * 互いに上書きされる。新Fix は現行を iframe で開くので、開いた直後の POST が 419 になる。
 *
 * ホスト名が分かれている本番・ローカルは `SESSION_XSRF_COOKIE` 未設定でよく、
 * その場合は Laravel 標準（`XSRF-TOKEN`）のまま動く。
 */
class PreventRequestForgery extends Middleware
{
    /**
     * 名前以外は親の実装と同じ。
     *
     * @param  array<string, mixed>  $config  config('session')
     */
    protected function newCookie($request, $config): Cookie
    {
        /** @var Request $request */
        return new Cookie(
            (string) config('session.xsrf_cookie', 'XSRF-TOKEN'),
            $request->session()->token(),
            $this->availableAt(60 * $config['lifetime']),
            $config['path'],
            $config['domain'],
            $config['secure'],
            false,
            false,
            $config['same_site'] ?? null,
            $config['partitioned'] ?? false
        );
    }
}

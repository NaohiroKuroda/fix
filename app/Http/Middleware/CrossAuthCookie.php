<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * felix_total との認証連携（同期クッキー方式）。
 *
 * - (1) 復元: admin 未ログイン & 有効な cross_auth クッキーがあれば、自分の native セッションを作る。
 * - (2) 発行/更新: admin ログイン中なら cross_auth をスライディングで張り直す。
 *
 * cross_auth は平文＋HMAC 署名（EncryptCookies の except 対象）。
 * 詳細は docs/plans/cross-auth-cookie-plan.md を参照。
 */
class CrossAuthCookie
{
    public function handle(Request $request, Closure $next)
    {
        $guard = Auth::guard('admin');

        // (1) 復元
        if (! $guard->check() && $request->hasCookie('cross_auth')) {
            $userId = $this->verify($request->cookie('cross_auth'));
            if ($userId !== null && AdminUser::whereKey($userId)->exists()) {
                $guard->loginUsingId($userId);
            }
        }

        $response = $next($request);

        // (2) 発行/更新（スライディング）
        if ($guard->check()) {
            $response->headers->setCookie($this->issue((int) $guard->id()));
        }

        return $response;
    }

    /** 署名付き値を検証し、OK なら userId を返す（NG は null）。 */
    private function verify(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $parts = explode('.', $value);
        if (count($parts) !== 4) {
            return null;
        }

        [$ver, $userId, $exp, $sig] = $parts;
        if ($ver !== 'v1' || ! ctype_digit($userId) || ! ctype_digit($exp)) {
            return null;
        }
        if ((int) $exp < time()) {
            return null;                                  // 失効
        }

        $expected = hash_hmac('sha256', "v1.{$userId}.{$exp}", (string) config('cross_auth.secret'));
        if (! hash_equals($expected, $sig)) {
            return null;                                  // 改ざん
        }

        return (int) $userId;
    }

    /** cross_auth クッキーを生成（平文＋HMAC、属性付き）。 */
    private function issue(int $userId): Cookie
    {
        $ttl = (int) config('cross_auth.ttl', 1800);
        $exp = time() + $ttl;
        $value = "v1.{$userId}.{$exp}.".hash_hmac('sha256', "v1.{$userId}.{$exp}", (string) config('cross_auth.secret'));

        return cookie(
            name: 'cross_auth',
            value: $value,
            minutes: (int) ($ttl / 60),
            path: '/',
            domain: config('cross_auth.domain'),
            secure: (bool) config('cross_auth.secure', true),
            httpOnly: true,
            sameSite: 'lax',
        );
    }
}

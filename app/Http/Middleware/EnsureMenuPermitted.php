<?php

namespace App\Http\Middleware;

use App\Models\Legacy\AdminUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * サイドメニューに出ない画面を URL 直打ちで開かせない。
 *
 * 表示可否は新テーブルのメニュー定義（`m_menu_items` ＋ 権限の紐付け）が唯一の正で、
 * ここでも同じ判定（{@see AdminUser::menuPermissions()}）を使う。
 * メニューキーはパスの2番目のセグメント（`/quotation-management/{key}/...`）。
 * 実行系（POST の confirm / reject など）も同じキー配下なのでまとめて守れる。
 */
class EnsureMenuPermitted
{
    /**
     * 画面ではない（メニューに紐づかない）2番目のセグメント。素通しする。
     *
     * コメント（やり取り）は**項目単位のスレッド**で、どの画面からでも同じものを読み書きする。
     * 取引先 ID を切るため URL が `/quotation-management/payable-partners/...` になり、
     * メニューキーとして判定すると**画面は見られるのにコメントの取得・送信だけ弾かれる**。
     *
     * ponytail: 見積管理配下にサブリソースを足すときはここにも足す（メニュー定義からは判別できない）。
     *
     * @var list<string>
     */
    private const NON_SCREEN_KEYS = ['payable-partners', 'billing-partners', 'comment-attachments'];

    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();
        $key = $request->segment(2);

        if (! $admin instanceof AdminUser || $key === null || in_array($key, self::NON_SCREEN_KEYS, true)) {
            return $next($request);
        }

        if (($admin->menuPermissions()[$key] ?? false) === true) {
            return $next($request);
        }

        // 自分が見られる最初の画面へ戻す（1つも無ければログイン画面へ）。
        return redirect($admin->firstMenuUri() ?? route('login'))
            ->with('error', 'この画面を開く権限がありません。');
    }
}

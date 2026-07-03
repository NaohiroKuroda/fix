<?php

namespace App\Http\Middleware;

use App\Repositories\Contracts\QuotationRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $admin = Auth::guard('admin')->user();

        return [
            ...parent::share($request),
            // ヘッダー右に表示する認証ユーザー（admin ガード）。未ログイン時は null。
            'auth' => [
                'user' => $admin ? [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    // 付与ロールの slug 一覧（汎用。将来のメニュー出し分け用）。
                    'roles' => $admin->roleSlugs(),
                    // 建設部部長か（部長承認・部長取消承認メニュー、やり取りの発言ロール判定）。
                    'isEstimateManager' => $admin->isEstimateManager(),
                ] : null,
            ],
            // 現行 felix_total の URL（明細リンクの iframe 先）。
            'felixTotalUrl' => config('services.felix_total.url'),
            // フラッシュメッセージ（成功 / エラー）。Controller の back()->with(...) で積む。
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            // サイドメニューの未処理件数バッヂ。Closure にして部分リロード（only指定）時は評価しない。
            'menuBadges' => fn () => $admin
                ? app(QuotationRepositoryInterface::class)->pendingCounts()
                : null,
        ];
    }
}

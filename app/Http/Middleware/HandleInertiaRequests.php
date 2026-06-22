<?php

namespace App\Http\Middleware;

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
                ] : null,
            ],
            // 現行 felix_total の URL（明細リンクの iframe 先）。
            'felixTotalUrl' => config('services.felix_total.url'),
            // フラッシュメッセージ（成功 / エラー）。Controller の back()->with(...) で積む。
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }
}

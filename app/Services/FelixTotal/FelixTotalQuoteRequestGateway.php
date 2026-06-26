<?php

namespace App\Services\FelixTotal;

use App\Http\Middleware\CrossAuthCookie;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * 現行 felix_total（laravel-admin）の見積依頼処理（order_estimate）を、
 * サーバ間 HTTP でそのまま実行する外部連携ゲートウェイ。
 *
 * 新スキーマ画面の「見積依頼送信」は felix_total の既存処理
 * （トークン発行＋ estimate_order_histories 作成＋業者へメール送信）を
 * 唯一の正として再利用する。認証は cross_auth クッキー（HMAC 署名）を
 * サーバ側で発行して付与し、felix_total 側の admin セッションを復元させる。
 *
 * 詳細は docs/architecture/backend.md「3.5 外部システム連携」を参照。
 */
class FelixTotalQuoteRequestGateway
{
    /**
     * felix_total の見積依頼処理を実行する。
     *
     * @param  list<string>  $estimateUnitPairs  "{estimate_units.id}:{estimate_unit_companies.id}" の配列
     * @return int 依頼を送信した見積先の件数（＝有効なペア数）
     *
     * @throws RuntimeException 連携先 URL 未設定 / admin 未ログイン / HTTP 失敗時
     */
    public function orderEstimate(array $estimateUnitPairs): int
    {
        $pairs = array_values(array_filter($estimateUnitPairs, 'strlen'));
        if ($pairs === []) {
            return 0;
        }

        // サーバ間 HTTP はコンテナが到達できるホスト（internal_url）を使う。
        // ブラウザ向け URL（fix.felix-japan.local 等）はコンテナから到達できないため。
        $base = (string) (config('services.felix_total.internal_url') ?: config('services.felix_total.url'));
        if ($base === '') {
            throw new RuntimeException('felix_total の連携 URL（FELIX_TOTAL_INTERNAL_URL / FELIX_TOTAL_URL）が未設定です。');
        }

        $adminId = Auth::guard('admin')->id();
        if ($adminId === null) {
            throw new RuntimeException('admin としてログインしていないため、見積依頼を送信できません。');
        }

        $url = rtrim($base, '/').(string) config('services.felix_total.quote_request_path');
        $cookie = 'cross_auth='.CrossAuthCookie::mintValue((int) $adminId);

        try {
            $response = Http::withHeaders(['Cookie' => $cookie])
                ->acceptJson()
                ->timeout(30)
                ->get($url, [
                    // estimate_id="" を渡すと felix_total 側がユニットの案件で自動グルーピングする。
                    'estimate_id' => '',
                    'estimate_unit_ids' => implode(',', $pairs),
                ]);
        } catch (ConnectionException $e) {
            // 接続失敗（不達/タイムアウト）も連携失敗として統一的に扱う。
            throw new RuntimeException('felix_total へ接続できませんでした。', previous: $e);
        }

        // felix_total は成功時に「ユニット → 依頼済みバッジ HTML」の JSON を返す。
        // 2xx かつ JSON として解釈できれば成功とみなす。
        if (! $response->successful() || $response->json() === null) {
            throw new RuntimeException("felix_total の見積依頼処理に失敗しました（HTTP {$response->status()}）。");
        }

        return count($pairs);
    }
}

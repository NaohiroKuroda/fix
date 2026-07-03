<?php

namespace App\Services\FelixTotal;

use App\Http\Middleware\CrossAuthCookie;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * 現行 felix_total（laravel-admin）の見積関連処理を、サーバ間 HTTP でそのまま実行する外部連携ゲートウェイ。
 *
 * 新スキーマ画面の操作を felix_total の既存処理（唯一の正）に橋渡しする：
 * - 見積依頼送信  : order_estimate（トークン発行＋ estimate_order_histories 作成＋業者へメール）
 * - 業者選定（採用）: update_adoption_flg
 * - 部長承認（建設部選定）: update_tmp_company_select_flg
 *
 * 認証は cross_auth クッキー（HMAC 署名）をサーバ側で発行して付与し、felix_total の admin セッションを復元させる。
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
        if (! $response->successful() || $response->json() === null) {
            throw new RuntimeException("felix_total の見積依頼処理に失敗しました（HTTP {$response->status()}）。");
        }

        return count($pairs);
    }

    /**
     * 採用（業者選定）：felix_total の update_adoption_flg を呼ぶ。
     *
     * @param  int  $estimateUnitId  旧 estimate_units.id
     * @param  int  $companyId  旧 estimate_unit_companies.id
     * @param  int  $noCompetitiveFlg  相見積なしフラグ（estimate_units.no_competitive_flg）
     *
     * @throws RuntimeException
     */
    public function adoptCompany(int $estimateUnitId, int $companyId, int $noCompetitiveFlg = 0): void
    {
        $this->callEdit('update_adoption_flg', [
            'estimate_unit_id' => $estimateUnitId,
            'id' => $companyId,
            'no_competitive_flg' => $noCompetitiveFlg,
        ]);
    }

    /**
     * 建設部選定（部長承認）：felix_total の update_tmp_company_select_flg を呼ぶ。
     *
     * @param  int  $estimateUnitId  旧 estimate_units.id
     * @param  int  $companyId  旧 estimate_unit_companies.id
     *
     * @throws RuntimeException
     */
    public function tmpSelectCompany(int $estimateUnitId, int $companyId): void
    {
        $this->callEdit('update_tmp_company_select_flg', [
            'estimate_unit_id' => $estimateUnitId,
            'id' => $companyId,
        ]);
    }

    /**
     * felix_total の new-estimates-custom-edit 配下の更新系を cross_auth 付きサーバ間 HTTP（GET）で叩く。
     *
     * @param  array<string, int|string>  $params
     *
     * @throws RuntimeException 連携先 URL 未設定 / admin 未ログイン / 接続失敗 / 非 2xx 時
     */
    private function callEdit(string $action, array $params): void
    {
        // サーバ間 HTTP はコンテナが到達できるホスト（internal_url）を使う。
        $base = (string) (config('services.felix_total.internal_url') ?: config('services.felix_total.url'));
        if ($base === '') {
            throw new RuntimeException('felix_total の連携 URL（FELIX_TOTAL_INTERNAL_URL / FELIX_TOTAL_URL）が未設定です。');
        }

        $adminId = Auth::guard('admin')->id();
        if ($adminId === null) {
            throw new RuntimeException('admin としてログインしていないため、felix_total を呼べません。');
        }

        $url = rtrim($base, '/').'/admin/new-estimates-custom-edit/'.$action;
        $cookie = 'cross_auth='.CrossAuthCookie::mintValue((int) $adminId);

        try {
            $response = Http::withHeaders(['Cookie' => $cookie])
                ->acceptJson()
                ->timeout(30)
                ->get($url, $params);
        } catch (ConnectionException $e) {
            throw new RuntimeException('felix_total へ接続できませんでした。', previous: $e);
        }

        if (! $response->successful()) {
            throw new RuntimeException("felix_total の処理（{$action}）に失敗しました（HTTP {$response->status()}）。");
        }
    }
}

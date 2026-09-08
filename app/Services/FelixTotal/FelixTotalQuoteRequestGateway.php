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
     * felix_total 側は mode=='false' のときだけ採用取消として扱うため、採用は mode='true' を明示する
     * （未指定でも採用側へ分岐するが、意図しない取消を防ぐため送信値を明示する）。
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
            'mode' => 'true',
        ]);
    }

    /**
     * 採用取消（部長承認の否認＝業者選定へ差し戻し）：felix_total の update_adoption_flg を mode='false' で呼ぶ。
     *
     * felix_total 側は adoption_flg=0 / company_select_flg=NULL とし、estimate_units の
     * vendor_id を NULL・price を再計算する（＝業者選定前の状態へ戻す）。
     *
     * @param  int  $estimateUnitId  旧 estimate_units.id
     * @param  int  $companyId  旧 estimate_unit_companies.id
     *
     * @throws RuntimeException
     */
    public function cancelAdoption(int $estimateUnitId, int $companyId): void
    {
        $this->callEdit('update_adoption_flg', [
            'estimate_unit_id' => $estimateUnitId,
            'id' => $companyId,
            'no_competitive_flg' => 0,
            'mode' => 'false',
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
     * 建設部選定の取消（部長取消承認）：felix_total の update_tmp_company_select_flg を mode='false' で呼ぶ。
     *
     * felix_total 側は estimate_units の tmp_company_id を NULL・company_select_status を 1（選定中）に戻し、
     * estimate_unit_companies.tmp_status を NULL にする。
     *
     * @param  int  $estimateUnitId  旧 estimate_units.id
     * @param  int  $companyId  旧 estimate_unit_companies.id
     *
     * @throws RuntimeException
     */
    public function cancelTmpSelection(int $estimateUnitId, int $companyId): void
    {
        $this->callEdit('update_tmp_company_select_flg', [
            'estimate_unit_id' => $estimateUnitId,
            'id' => $companyId,
            'mode' => 'false',
        ]);
    }

    /**
     * 発注書の作成・送付（部長承認）：現行の `create_send_order_company` を呼ぶ。
     *
     * 業者マイページの発注書表示と請負承認は**現行 `orders` が起点**のため、
     * 新テーブルの発注書（`t_payable_orders`）だけでは業者は承認できない。
     * 現行側で `orders` ＋ `order_units` を作り、`status = 10`（発行済）にして発注メールまで送る。
     *
     * @param  int  $estimateUnitCompanyId  旧 estimate_unit_companies.id（= t_payable_partners.source_id）
     *
     * @throws RuntimeException
     */
    public function createAndSendOrder(int $estimateUnitCompanyId): void
    {
        $this->call('estimates-custom-detail/create_send_order_company', [
            'estimate_unit_company_ids' => (string) $estimateUnitCompanyId,
        ]);
    }

    /**
     * 発注書のキャンセル（部長取消承認）：現行の `cancel_send_order` を `status = 99` で呼ぶ。
     *
     * 現行 `orders` / `estimate_customs` をキャンセル状態にして業者マイページから発注書を消す。
     *
     * ponytail: 現行の `create_send_order_company` は既存 `orders` を **status を見ずに** 1件拾うため、
     * キャンセル済みでも再利用される。そのため「取消 → **同じ見積先**を選び直して再承認」では
     * 新しい発注書が作られない（別の見積先を選び直した場合は新規に作られる）。今回のリリースでは
     * 発注書作成後に見積依頼からやり直す運用が無いため対応しない。必要になったら現行側の判定に
     * `whereNotIn('status', [98, 99])` を足すか、対象の `orders` を消す（→ 05_支払_部長取消承認）。
     *
     * @param  int  $estimateUnitId  旧 estimate_units.id（= t_building_budget_items.source_id）
     *
     * @throws RuntimeException
     */
    public function cancelOrder(int $estimateUnitId): void
    {
        $this->call('estimates-custom-detail/cancel_send_order', [
            'estimate_unit_ids' => (string) $estimateUnitId,
            'status' => 99, // 99=キャンセル（現行 config/constant.php の report_status_list）
        ]);
    }

    /**
     * felix_total の new-estimates-custom-edit 配下の更新系を叩く。
     *
     * @param  array<string, int|string>  $params
     *
     * @throws RuntimeException 連携先 URL 未設定 / admin 未ログイン / 接続失敗 / 非 2xx 時
     */
    private function callEdit(string $action, array $params): void
    {
        $this->call('new-estimates-custom-edit/'.$action, $params);
    }

    /**
     * felix_total の `/admin` 配下を cross_auth 付きサーバ間 HTTP（GET）で叩く。
     *
     * @param  string  $path  `/admin/` に続くパス（例: `estimates-custom-detail/cancel_send_order`）
     * @param  array<string, int|string>  $params
     *
     * @throws RuntimeException 連携先 URL 未設定 / admin 未ログイン / 接続失敗 / 非 2xx 時
     */
    private function call(string $path, array $params): void
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

        $url = rtrim($base, '/').'/admin/'.$path;
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
            throw new RuntimeException("felix_total の処理（{$path}）に失敗しました（HTTP {$response->status()}）。");
        }
    }
}

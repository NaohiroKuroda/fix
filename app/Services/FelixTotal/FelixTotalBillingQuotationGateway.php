<?php

namespace App\Services\FelixTotal;

use App\Http\Middleware\CrossAuthCookie;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * 請求（もらい）の見積を現行 felix_total へ反映させる外部連携ゲートウェイ。
 *
 * もらいの業者マイページ「見積」タブは見積書PDFの閲覧だけで、その PDF は現行の
 * `estimate_unit_company_files`（type = 1）＋ `cost_unit_estimate_units` から組み立てられる。
 * 新テーブル（`t_billing_quotations`）へ登録しただけでは業者に見えないため、
 * 見積承認のタイミングで現行側へ写す処理を呼ぶ。
 *
 * 書き込みは現行側の責務とし、こちらは依頼するだけにする（支払系の
 * {@see FelixTotalQuoteRequestGateway} と同じ cross_auth クッキー方式）。
 */
class FelixTotalBillingQuotationGateway
{
    /**
     * 承認した請求見積を現行の見積ファイルへ写すよう依頼する。
     *
     * @param  list<int>  $partnerIds  請求取引先（t_billing_partners.id）
     * @return int 現行側で写した件数（見積が無い・移行元が無い取引先は数に入らない）
     *
     * @throws RuntimeException 連携先 URL 未設定 / admin 未ログイン / 接続失敗 / 非 2xx 時
     */
    public function syncQuotations(array $partnerIds): int
    {
        $ids = array_values(array_filter(array_map('intval', $partnerIds)));
        if ($ids === []) {
            return 0;
        }

        $response = $this->call('sync_billing_quotation', [
            'billing_partner_ids' => implode(',', $ids),
        ]);

        return (int) ($response['synced'] ?? 0);
    }

    /**
     * felix_total の new-estimates-custom-edit 配下を cross_auth 付きサーバ間 HTTP（GET）で叩く。
     *
     * @param  array<string, int|string>  $params
     * @return array<string, mixed> 応答 JSON
     *
     * @throws RuntimeException
     */
    private function call(string $action, array $params): array
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

        return (array) $response->json();
    }
}

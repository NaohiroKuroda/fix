<?php

namespace App\Services\Quotation\Billing;

use App\Exceptions\ServiceException;
use App\Models\TBuilding;
use App\Repositories\Contracts\Quotation\Billing\BillingRepositoryInterface;
use App\Services\Comment\CommentService;
use App\Services\FelixTotal\FelixTotalBillingQuotationGateway;
use App\Services\Mail\BillingNotificationMailService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

/**
 * 請求（もらい）系5画面のユースケース。
 *
 * 承認ステータスの遷移はすべて本サービスを通す。理由付きの操作（取消申請・取消承認・否認）は、
 * 併せて対象項目のやり取り（コメント）へ記録を残す（支払系と同じ流儀）。
 *
 * @see docs/detailed-design/quotations/06_請求_見積作成_詳細設計.md
 */
class BillingQuotationService
{
    public function __construct(
        private readonly BillingRepositoryInterface $billing,
        private readonly CommentService $comments,
        private readonly BillingNotificationMailService $mail,
        private readonly FelixTotalBillingQuotationGateway $legacyQuotation,
    ) {}

    /**
     * 画面の案件一覧を取得する。
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, TBuilding>
     */
    public function paginate(string $mode, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->guard(
            fn () => $this->billing->forBillingManagement($filters, $perPage, $mode),
            '請求系画面の一覧取得に失敗しました',
            ['mode' => $mode, 'filters' => $filters],
        );
    }

    /**
     * 見積作成モーダルの選択肢（拠点 / 部署 / 単位）。
     *
     * @return array<string, mixed>
     */
    public function masters(): array
    {
        return $this->guard(
            fn () => $this->billing->masters(),
            '請求見積のマスタ取得に失敗しました',
            [],
        );
    }

    /**
     * 見積を保存する（新規作成・修正とも新しい版を作る）。承認申請はしない（`DRAFT` のまま）。
     *
     * @param  array<string, mixed>  $quotation
     * @param  list<array<string, mixed>>  $details
     */
    public function saveQuotation(int $partnerId, array $quotation, array $details): int
    {
        return $this->guard(
            fn () => $this->billing->saveQuotation($partnerId, $quotation, $details),
            '請求見積の保存に失敗しました',
            ['partnerId' => $partnerId],
        );
    }

    /**
     * 見積の承認申請（見積作成 → 見積承認へ回す）。`DRAFT` / `CANCELLED` → `APPLIED`。
     *
     * @param  list<int>  $partnerIds
     * @return int 実際に申請した件数
     */
    public function apply(array $partnerIds): int
    {
        return $this->guard(
            fn () => $this->billing->advanceStatus($partnerIds, 'DRAFT', 'APPLIED')
                + $this->billing->advanceStatus($partnerIds, 'CANCELLED', 'APPLIED'),
            '請求見積の承認申請に失敗しました',
            ['partnerIds' => $partnerIds],
        );
    }

    /**
     * 見積承認。`APPLIED` → `APPROVED`。
     *
     * **発注書（t_billing_orders）はここでは作らない。** もらいの発注は業者が承諾して初めて
     * 成立するため、業者マイページの「発注承諾する」を押した時点で発行する
     * （→ 02_請求_発注書確認_詳細設計.md §4）。
     *
     * @param  list<int>  $partnerIds
     */
    public function approve(array $partnerIds): int
    {
        return $this->guard(
            fn (): int => $this->billing->advanceStatus($partnerIds, 'APPLIED', 'APPROVED'),
            '請求見積の承認に失敗しました',
            ['partnerIds' => $partnerIds],
        );
    }

    /**
     * ④ 見積承認後に、承認した見積を現行 felix_total の見積ファイルへ写すよう依頼する。
     *
     * もらいの業者マイページは現行の見積ファイルから見積書PDFを組み立てるため、
     * 新テーブルへ登録しただけでは業者に見えない。**承認そのものとは切り離す**（同期に失敗しても
     * 承認は巻き戻さず、ログに残して false を返す）。
     *
     * @param  list<int>  $partnerIds  承認した請求取引先（t_billing_partners.id）
     * @return bool 同期を依頼できたか（false＝失敗。承認自体は成立している）
     */
    public function syncQuotationToLegacy(array $partnerIds): bool
    {
        try {
            $this->legacyQuotation->syncQuotations($partnerIds);

            return true;
        } catch (\Exception $e) {
            Log::error('【請求】見積の現行への同期に失敗しました', [
                'partnerIds' => $partnerIds,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return false;
        }
    }

    /**
     * ④ 見積承認後に、業者へ「見積確認・発注承諾のご依頼」メールをメールキューへ登録する。
     *
     * **承認そのものとは切り離す**。キューへの登録に失敗しても承認（ステータス更新・発注書発行）は
     * 巻き戻さず、ログに残して false を返すだけにする。
     *
     * @param  list<int>  $partnerIds  承認した請求取引先（t_billing_partners.id）
     * @return bool 登録できたか（false＝失敗。承認自体は成立している）
     */
    public function notifyQuoteConfirmed(array $partnerIds): bool
    {
        try {
            $this->mail->sendQuoteConfirmMail($partnerIds);

            return true;
        } catch (\Exception $e) {
            Log::error('【請求】見積確認・発注承諾のご依頼メールの登録に失敗しました', [
                'partnerIds' => $partnerIds,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return false;
        }
    }

    /**
     * 見積の否認。`APPLIED` → `CANCELLED`（③ 見積作成へ差し戻し）。
     * 否認理由を項目のやり取りへ `【否認】{理由}` として残す。
     */
    public function reject(int $partnerId, string $reason): int
    {
        return $this->transition($partnerId, 'APPLIED', 'CANCELLED', '【否認】'.$reason, '請求見積の否認に失敗しました');
    }

    /**
     * 見積取消申請。`APPROVED` → `CANCEL_APPLIED`。理由を `【取消申請】{理由}` で残す。
     */
    public function requestCancel(int $partnerId, string $reason): int
    {
        return $this->transition($partnerId, 'APPROVED', 'CANCEL_APPLIED', '【取消申請】'.$reason, '請求見積の取消申請に失敗しました');
    }

    /**
     * 見積取消申請のあとに、業者へ「発注取消のご連絡」メールをメールキューへ登録する。
     *
     * **申請そのものとは切り離す**。キューへの登録に失敗しても申請は巻き戻さず、
     * ログに残して false を返すだけにする。
     *
     * @param  list<int>  $partnerIds  取消申請した請求取引先（t_billing_partners.id）
     * @return bool 登録できたか（false＝失敗。申請自体は成立している）
     */
    public function notifyCancelRequested(array $partnerIds): bool
    {
        try {
            $this->mail->sendCancelRequestMail($partnerIds);

            return true;
        } catch (\Exception $e) {
            Log::error('【請求】発注取消のご連絡メールの登録に失敗しました', [
                'partnerIds' => $partnerIds,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return false;
        }
    }

    /**
     * 見積取消承認。`CANCEL_APPLIED` → `CANCELLED`（③ 見積作成へ差し戻し）。
     * 理由を `【取消承認】{理由}` で残す。
     */
    public function approveCancel(int $partnerId, string $reason): int
    {
        return $this->transition($partnerId, 'CANCEL_APPLIED', 'CANCELLED', '【取消承認】'.$reason, '請求見積の取消承認に失敗しました');
    }

    /**
     * 見積取消の否認。`CANCEL_APPLIED` → `APPROVED`（**承認済みのまま据え置く**）。
     *
     * 取消を認めないので見積作成へは差し戻さず、承認済みの状態に戻す（支払側の部長取消承認と同じ）。
     * 発行済みの発注書もそのまま残す。理由は `【否認】{理由}` としてやり取りに記録する。
     */
    public function rejectCancel(int $partnerId, string $reason): int
    {
        return $this->transition($partnerId, 'CANCEL_APPLIED', 'APPROVED', '【否認】'.$reason, '請求見積の取消否認に失敗しました');
    }

    /**
     * ステータスを1件遷移させ、成功したら理由コメントを項目スレッドへ残す。
     *
     * @return int 実際に遷移した件数（0=対象外）
     */
    private function transition(int $partnerId, string $from, string $to, string $body, string $errorMessage): int
    {
        return $this->guard(function () use ($partnerId, $from, $to, $body): int {
            $count = $this->billing->advanceStatus([$partnerId], $from, $to);

            if ($count > 0) {
                // 見積作成へ差し戻す遷移（否認・取消承認）では、発行済みの発注書も取り消す。
                if ($to === 'CANCELLED') {
                    $this->billing->revokeOrders([$partnerId]);
                }

                $itemId = $this->billing->itemIdForPartner($partnerId);
                if ($itemId !== null) {
                    $this->comments->post($itemId, $body, []);
                }
            }

            return $count;
        }, $errorMessage, ['partnerId' => $partnerId, 'from' => $from, 'to' => $to]);
    }

    /**
     * 例外をログに残して ServiceException へ包み直す（支払系サービスと同じ方針）。
     *
     * @template T
     *
     * @param  callable(): T  $operation
     * @param  array<string, mixed>  $context
     * @return T
     */
    private function guard(callable $operation, string $errorMessage, array $context): mixed
    {
        try {
            return $operation();
        } catch (\Exception $e) {
            Log::error($errorMessage, $context + [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new ServiceException(previous: $e);
        }
    }
}

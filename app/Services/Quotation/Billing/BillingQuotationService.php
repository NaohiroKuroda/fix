<?php

namespace App\Services\Quotation\Billing;

use App\Exceptions\ServiceException;
use App\Models\TBuilding;
use App\Repositories\Contracts\BillingRepositoryInterface;
use App\Services\Comment\CommentService;
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
     * @param  list<int>  $partnerIds
     */
    public function approve(array $partnerIds): int
    {
        return $this->guard(
            fn () => $this->billing->advanceStatus($partnerIds, 'APPLIED', 'APPROVED'),
            '請求見積の承認に失敗しました',
            ['partnerIds' => $partnerIds],
        );
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
     * 見積取消承認。`CANCEL_APPLIED` → `CANCELLED`（③ 見積作成へ差し戻し）。
     * 理由を `【取消承認】{理由}` で残す。
     */
    public function approveCancel(int $partnerId, string $reason): int
    {
        return $this->transition($partnerId, 'CANCEL_APPLIED', 'CANCELLED', '【取消承認】'.$reason, '請求見積の取消承認に失敗しました');
    }

    /**
     * 見積取消の否認。`CANCEL_APPLIED` → `CANCELLED`（承認と同じ差し戻し先）。
     * 違いは理由が `【否認】{理由}` として残ること。
     */
    public function rejectCancel(int $partnerId, string $reason): int
    {
        return $this->transition($partnerId, 'CANCEL_APPLIED', 'CANCELLED', '【否認】'.$reason, '請求見積の取消否認に失敗しました');
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

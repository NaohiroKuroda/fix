<?php

namespace App\Services\Quotation\Payable;

use App\Exceptions\ServiceException;
use App\Models\TBuilding;
use App\Repositories\Contracts\Quotation\Payable\PayableRepositoryInterface;
use App\Services\Comment\CommentService;
use App\Services\Mail\VendorNotificationMailService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

/**
 * 部長取消申請（cancel-request）画面のユースケース。
 */
class CancelRequestService
{
    public function __construct(
        private readonly PayableRepositoryInterface $estimates,
        private readonly CommentService $comments,
        private readonly VendorNotificationMailService $mail,
    ) {}

    /**
     * 部長取消申請画面の案件一覧（承認済みで未取消申請の見積先）を取得する。
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, TBuilding>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        try {
            return $this->estimates->forPayableManagement($filters, $perPage, 'cancel-request');
        } catch (\Exception $e) {
            Log::error('部長取消申請の一覧取得に失敗しました', [
                'message' => $e->getMessage(),
                'filters' => $filters,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new ServiceException(previous: $e);
        }
    }

    /**
     * 業者へ「発注取消のご連絡」メールを送る（→ 支払系_必要処理一覧 A4-3）。
     *
     * 取消申請そのものは成立しているので、送信に失敗しても巻き戻さない。
     * 呼び出し側（コントローラ）が戻り値を見て画面に注意書きを出す。
     *
     * @param  list<int>  $partnerIds
     * @return bool 送信予約まで成功したか
     */
    public function notifyCancelRequested(array $partnerIds): bool
    {
        try {
            $this->mail->sendPayableCancelRequestMail($partnerIds);

            return true;
        } catch (\Exception $e) {
            Log::error('【支払】発注取消のご連絡メールの登録に失敗しました', [
                'partnerIds' => $partnerIds,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return false;
        }
    }

    /**
     * 部長取消申請の実行（選択した見積先の取消を申請）。
     * 併せて、取消申請の理由を項目のやり取り（コメント）へ残す。
     *
     * @param  list<int>  $partnerIds
     * @return int 実際に申請した件数
     */
    public function confirm(array $partnerIds, string $reason): int
    {
        try {
            $count = $this->estimates->recordCancelRequests($partnerIds);

            if ($count > 0) {
                // 対象見積先が属する項目（重複排除）へ理由コメントを残す（投稿者＝操作者）。
                $itemIds = [];
                foreach ($partnerIds as $partnerId) {
                    $itemId = $this->estimates->itemIdForPartner((int) $partnerId);
                    if ($itemId !== null) {
                        $itemIds[$itemId] = true;
                    }
                }
                foreach (array_keys($itemIds) as $itemId) {
                    $this->comments->post($itemId, '【取消申請】'.$reason, []);
                }
            }

            return $count;
        } catch (\Exception $e) {
            Log::error('部長取消申請の実行に失敗しました', [
                'message' => $e->getMessage(),
                'partnerIds' => $partnerIds,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new ServiceException(previous: $e);
        }
    }
}

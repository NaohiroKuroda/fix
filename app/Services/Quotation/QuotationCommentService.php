<?php

namespace App\Services\Quotation;

use App\Models\AdminUser;
use App\Models\TBuildingCostItem;
use App\Models\TComment;
use App\Repositories\Contracts\CommentRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * やり取り（コメント）のユースケース入口。
 *
 * コメントは費用項目（t_building_cost_items）単位で1スレッドに集約する
 * （commentable_type = App\Models\TBuildingCostItem / commentable_id = 項目ID）。
 * 投稿者・既読の user_id はログイン中の admin_users.id を用いる。
 */
class QuotationCommentService
{
    /** コメント対象＝費用項目（t_building_cost_items）のモデル。 */
    private const COMMENTABLE_TYPE = TBuildingCostItem::class;

    public function __construct(
        private readonly CommentRepositoryInterface $comments,
    ) {}

    /**
     * 項目のコメント一覧を古い順で取得し、ログインユーザーの既読日時を「今」で更新する。
     *
     * @return Collection<int, TComment>
     */
    public function thread(int $itemId): Collection
    {
        $comments = $this->comments->forCommentable(self::COMMENTABLE_TYPE, $itemId);

        $userId = $this->currentUserId();
        if ($userId !== null) {
            $this->comments->markRead(self::COMMENTABLE_TYPE, $itemId, $userId, Carbon::now());
        }

        return $comments;
    }

    /**
     * 項目にコメントを1件投稿する（添付ファイル込み）。投稿者は現在のログインユーザー。
     *
     * @param  list<UploadedFile>  $files
     */
    public function post(int $itemId, string $body, array $files): TComment
    {
        $userId = $this->currentUserId() ?? 0;

        $comment = $this->comments->create(self::COMMENTABLE_TYPE, $itemId, $userId, $body);

        // 添付ファイルは public ディスクへ保存し、コメントにポリモーフィックで紐づける。
        foreach ($files as $file) {
            $path = $file->store("comments/{$itemId}", 'public');
            $this->comments->addAttachment(
                $comment,
                $path,
                $file->getClientOriginalName(),
                $file->getClientMimeType(),
                (int) $file->getSize(),
                $userId,
            );
        }

        return $comment->load(['attachments', 'user.roles']);
    }

    /** ログイン中の admin_users.id（未認証なら null）。 */
    private function currentUserId(): ?int
    {
        $admin = Auth::guard('admin')->user();

        return $admin instanceof AdminUser ? (int) $admin->id : null;
    }
}

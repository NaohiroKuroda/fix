<?php

namespace App\Services\Comment;

use App\Exceptions\ServiceException;
use App\Models\AdminUser;
use App\Models\TBuildingBudgetItem;
use App\Models\TComment;
use App\Repositories\Contracts\Comment\CommentRepositoryInterface;
use App\Services\Image\ImageCompressor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * やり取り（コメント）のユースケース入口。
 *
 * コメントは建物予算項目（t_building_budget_items）単位で1スレッドに集約する
 * （commentable_type = "App\Models\TBuildingBudgetItem" / commentable_id = 項目ID）。
 * 投稿者・既読の user_id はログイン中の admin_users.id を用いる。
 */
class CommentService
{
    /**
     * コメント対象＝建物予算項目（t_building_budget_items）のモーフ型。
     *
     * FQCN 直書きではなくモデルの getMorphClass() を通す（morphMap を入れた場合にも追随するため）。
     * 2026-08 のスキーマ改訂前に保存された旧クラス名（TBuildingCostItem）は
     * マイグレーション 2026_08_27_000000 で新 FQCN へ書き換え済み。
     */
    private function commentableType(): string
    {
        return (new TBuildingBudgetItem)->getMorphClass();
    }

    public function __construct(
        private readonly CommentRepositoryInterface $comments,
        private readonly ImageCompressor $imageCompressor,
    ) {}

    /**
     * 項目のコメント一覧を古い順で取得し、ログインユーザーの既読日時を「今」で更新する。
     *
     * @return Collection<int, TComment>
     */
    public function thread(int $itemId): Collection
    {
        try {
            $comments = $this->comments->forCommentable($this->commentableType(), $itemId);

            $userId = $this->currentUserId();
            if ($userId !== null) {
                $this->comments->markRead($this->commentableType(), $itemId, $userId, Carbon::now());
            }

            return $comments;
        } catch (\Exception $e) {
            Log::error('コメントの取得に失敗しました', [
                'message' => $e->getMessage(),
                'itemId' => $itemId,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new ServiceException(previous: $e);
        }
    }

    /**
     * 項目にコメントを1件投稿する（添付ファイル込み）。投稿者は現在のログインユーザー。
     *
     * @param  list<UploadedFile>  $files
     */
    public function post(int $itemId, string $body, array $files): TComment
    {
        try {
            $userId = $this->currentUserId() ?? 0;

            $comment = $this->comments->create($this->commentableType(), $itemId, $userId, $body);

            // 添付ファイルは ImageCompressor 経由で public ディスクへ保存する
            // （画像は圧縮＋サムネ生成・UUID化。それ以外はそのまま保存）。
            foreach ($files as $file) {
                $stored = $this->imageCompressor->store($file, "comments/{$itemId}");
                $this->comments->addAttachment(
                    $comment,
                    $stored->path,
                    $file->getClientOriginalName(),
                    $stored->mime,
                    $stored->size,
                    $userId,
                );
            }

            return $comment->load(['attachments', 'user.roles']);
        } catch (\Exception $e) {
            Log::error('コメントの投稿に失敗しました', [
                'message' => $e->getMessage(),
                'itemId' => $itemId,
                'fileCount' => count($files),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new ServiceException(previous: $e);
        }
    }

    /** ログイン中の admin_users.id（未認証なら null）。 */
    private function currentUserId(): ?int
    {
        $admin = Auth::guard('admin')->user();

        return $admin instanceof AdminUser ? (int) $admin->id : null;
    }
}

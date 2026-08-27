<?php

namespace App\Repositories\Comment;

use App\Models\TComment;
use App\Models\TCommentReadTimestamp;
use App\Repositories\Contracts\Comment\CommentRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * コメント（t_comments）まわりのデータアクセス実装。
 * 参照先は default 接続（＝felix_total と共有の MySQL: DB_DATABASE=fix）。
 */
class CommentRepository implements CommentRepositoryInterface
{
    /**
     * @return Collection<int, TComment>
     */
    public function forCommentable(string $type, int $id): Collection
    {
        return TComment::query()
            ->where('commentable_type', $type)
            ->where('commentable_id', $id)
            ->with(['attachments', 'user.roles'])
            ->orderBy('id')
            ->get();
    }

    public function create(string $type, int $id, int $userId, string $body): TComment
    {
        return TComment::create([
            'commentable_type' => $type,
            'commentable_id' => $id,
            'user_id' => $userId,
            'body' => $body,
        ]);
    }

    public function addAttachment(
        TComment $comment,
        string $filePath,
        string $originalName,
        string $mimeType,
        int $size,
        int $userId,
    ): void {
        // morphMany 経由のため attachable_type / attachable_id は自動設定される。
        $comment->attachments()->create([
            'file_path' => $filePath,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'size' => $size,
            'user_id' => $userId,
        ]);
    }

    public function markRead(string $type, int $id, int $userId, Carbon $at): void
    {
        TCommentReadTimestamp::updateOrCreate(
            ['user_id' => $userId, 'readable_type' => $type, 'readable_id' => $id],
            ['last_read_at' => $at],
        );
    }
}

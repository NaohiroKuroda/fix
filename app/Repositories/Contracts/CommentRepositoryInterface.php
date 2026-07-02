<?php

namespace App\Repositories\Contracts;

use App\Models\TComment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * コメント（t_comments）・添付（t_attachments）・既読（t_comment_read_timestamps）への
 * データアクセス窓口。対象はポリモーフィック（morph type + id）で指定する。
 */
interface CommentRepositoryInterface
{
    /**
     * 対象（morph）に紐づくコメントを古い順で取得する（投稿者・添付込み）。
     *
     * @return Collection<int, TComment>
     */
    public function forCommentable(string $type, int $id): Collection;

    /** コメントを1件作成して返す。 */
    public function create(string $type, int $id, int $userId, string $body): TComment;

    /** コメントに添付ファイルを1件追加する。 */
    public function addAttachment(
        TComment $comment,
        string $filePath,
        string $originalName,
        string $mimeType,
        int $size,
        int $userId,
    ): void;

    /** 対象（morph）に対するユーザーの既読日時を更新（無ければ作成）する。 */
    public function markRead(string $type, int $id, int $userId, Carbon $at): void;
}

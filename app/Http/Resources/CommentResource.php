<?php

namespace App\Http\Resources;

use App\Models\AdminUser;
use App\Models\TAttachment;
use App\Models\TComment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

/**
 * やり取り（コメント）1件をチャットUI向けの形に整形する。
 * フロントの EstimateChatMessage 型（senderRole / senderName / body / createdAt / files）と一致させる。
 *
 * senderRole は投稿者（admin_users）の建設部部長ロール有無から manager/staff を導出する。
 * isMine はログイン中のユーザー自身の投稿か（チャットの右寄せ/左寄せ判定に使う）。
 *
 * @mixin TComment
 */
class CommentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $author = $this->user;
        $isManager = $author instanceof AdminUser && $author->isEstimateManager();
        $currentId = Auth::guard('admin')->id();

        return [
            'id' => (int) $this->id,
            'senderRole' => $isManager ? 'manager' : 'staff',
            // 自分の投稿か（投稿者 user_id ＝ ログイン中の admin_users.id）。
            'isMine' => $currentId !== null && (int) $this->user_id === (int) $currentId,
            'senderName' => (string) ($author?->name ?? ''),
            'body' => (string) $this->body,
            'createdAt' => optional($this->created_at)->format('Y-m-d H:i'),
            'files' => $this->attachments->map(fn (TAttachment $attachment): array => [
                'id' => (int) $attachment->id,
                'name' => (string) $attachment->original_name,
                // インライン表示（画像サムネイル）用。配信は認証付きの Laravel ルート経由。
                'url' => route('quotation-management.comment-attachments.show', $attachment->id),
                // クリック時の端末ダウンロード用（Content-Disposition: attachment）。
                'downloadUrl' => route('quotation-management.comment-attachments.download', $attachment->id),
                'mime' => $attachment->mime_type,
                'size' => (int) $attachment->size,
                'isImage' => str_starts_with((string) $attachment->mime_type, 'image/'),
            ])->all(),
        ];
    }
}

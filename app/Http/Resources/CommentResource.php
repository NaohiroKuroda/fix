<?php

namespace App\Http\Resources;

use App\Models\AdminUser;
use App\Models\TAttachment;
use App\Models\TComment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
            'files' => $this->attachments->map(function (TAttachment $attachment): array {
                $isImage = str_starts_with((string) $attachment->mime_type, 'image/');
                // サムネは画像かつ実際に生成できた場合のみ。無ければ null（UIはアイコン表示）。
                $hasThumb = $isImage && Storage::disk('public')->exists($attachment->thumbnailPath());

                return [
                    'id' => (int) $attachment->id,
                    'name' => (string) $attachment->original_name,
                    'mime' => $attachment->mime_type,
                    'size' => (int) $attachment->size,
                    'isImage' => $isImage,
                    // 画像サムネイルのインライン配信URL（画像かつサムネ生成済みのみ）。
                    'thumbUrl' => $hasThumb
                        ? route('quotation-management.comment-attachments.thumb', $attachment->id)
                        : null,
                    // クリック時の端末ダウンロード用URL（全ファイル共通のクリック先）。
                    'downloadUrl' => route('quotation-management.comment-attachments.download', $attachment->id),
                ];
            })->all(),
        ];
    }
}

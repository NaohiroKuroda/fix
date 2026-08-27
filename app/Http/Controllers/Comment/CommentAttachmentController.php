<?php

namespace App\Http\Controllers\Comment;

use App\Http\Controllers\Controller;
use App\Models\TAttachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * コメント添付ファイル（t_attachments）の配信。
 *
 * 公開ストレージ（public/storage）の静的配信は Web サーバ設定に依存し 403 になり得るため、
 * 認証（auth:admin）付きで Laravel から直接ストリームする。
 * - thumb    : 画像サムネイルのインライン配信（画像のみ・XSS 対策でサムネに限定）
 * - download : 添付として端末へダウンロード（元ファイル名で保存）。全ファイル共通のクリック先
 */
class CommentAttachmentController extends Controller
{
    /**
     * 画像サムネイルをインライン配信する（チャット吹き出しの <img> 表示用）。
     * インライン配信はサムネイル画像（JPEG）に限定する（SVG/HTML はインライン配信しない）。
     */
    public function thumb(TAttachment $attachment): StreamedResponse
    {
        $disk = Storage::disk('public');
        $path = $attachment->thumbnailPath();
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => 'inline',
        ]);
    }

    /** 添付ファイルを端末へダウンロードさせる（Content-Disposition: attachment）。 */
    public function download(TAttachment $attachment): StreamedResponse
    {
        $disk = Storage::disk('public');
        abort_unless($disk->exists($attachment->file_path), 404);

        return $disk->download($attachment->file_path, $attachment->original_name);
    }
}

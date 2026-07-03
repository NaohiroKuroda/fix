<?php

namespace App\Http\Controllers\Quotation;

use App\Http\Controllers\Controller;
use App\Models\TAttachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * コメント添付ファイル（t_attachments）の配信。
 *
 * 公開ストレージ（public/storage）の静的配信は Web サーバ設定に依存し 403 になり得るため、
 * 認証（auth:admin）付きで Laravel から直接ストリームする。
 * - show     : インライン配信（チャットの画像サムネイル表示用）
 * - download : 添付として端末へダウンロード（元ファイル名で保存）
 */
class CommentAttachmentController extends Controller
{
    /** 添付ファイルをインライン配信する（画像サムネイル等の表示用）。 */
    public function show(TAttachment $attachment): StreamedResponse
    {
        $disk = Storage::disk('public');
        abort_unless($disk->exists($attachment->file_path), 404);

        return $disk->response($attachment->file_path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
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

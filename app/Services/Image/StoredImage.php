<?php

namespace App\Services\Image;

/**
 * ImageCompressor::store() の結果。public ディスク上の保存結果を表す。
 */
final readonly class StoredImage
{
    public function __construct(
        /** 本体ファイルの相対パス（例: comments/12/{uuid}.jpg）。画像は圧縮済み。 */
        public string $path,
        /** 保存後の実MIMEタイプ（画像は実体から判定した値）。 */
        public string $mime,
        /** 保存後のファイルサイズ（バイト）。 */
        public int $size,
        /** サムネイルの相対パス（画像で生成できた場合のみ。無ければ null）。 */
        public ?string $thumbPath = null,
    ) {}
}

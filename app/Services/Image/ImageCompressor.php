<?php

namespace App\Services\Image;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 添付画像の圧縮・サムネイル生成（GD 使用）。06_添付ファイル_詳細設計 §5・§6。
 *
 * - 対象は jpeg/png/gif/webp。heic/heif はGD非対応のため圧縮せずそのまま保存する。
 * - 共有サーバのメモリOOMを避けるため、総ピクセルが約40MP超なら圧縮をスキップして元を保存する。
 * - 保存名はUUID化し、本体は comments/{itemId}/{uuid}.{ext}、
 *   サムネは comments/{itemId}/thumbs/{uuid}.jpg（規約パス）に保存する。
 */
class ImageCompressor
{
    /** GD圧縮の対象拡張子。 */
    private const COMPRESSIBLE = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /** 圧縮スキップ閾値（総ピクセル数）。共有サーバのメモリに合わせて調整する。 */
    private const MAX_PIXELS = 40_000_000;

    /** 本体の長辺上限（px）。これを超える場合のみアスペクト比維持で縮小する。 */
    private const MAX_LONG_EDGE = 1920;

    /** サムネイルの長辺（px）。 */
    private const THUMB_LONG_EDGE = 400;

    /** 本体JPEGの品質。 */
    private const MAIN_JPEG_QUALITY = 82;

    /** サムネイルJPEGの品質。 */
    private const THUMB_JPEG_QUALITY = 80;

    /**
     * ファイルを public ディスクへ保存する。画像は圧縮＋サムネ生成、それ以外はそのまま保存する。
     *
     * @param  string  $directory  保存先ディレクトリ（例: comments/12）
     */
    public function store(UploadedFile $file, string $directory): StoredImage
    {
        $disk = Storage::disk('public');
        $uuid = (string) Str::uuid();
        $ext = strtolower($file->getClientOriginalExtension());
        $filename = "{$uuid}.{$ext}";
        $path = "{$directory}/{$filename}";

        // 非圧縮対象（heic/heif/pdf/office/cad/zip）・GD不在時はそのまま保存する。
        if (! in_array($ext, self::COMPRESSIBLE, true) || ! function_exists('imagecreatefromstring')) {
            $disk->putFileAs($directory, $file, $filename);

            return new StoredImage($path, (string) $file->getClientMimeType(), (int) $file->getSize());
        }

        // 実体検証：画像として読めなければ拡張子偽装（画像を騙る非画像）として拒否する。
        $info = @getimagesize($file->getRealPath());
        if ($info === false) {
            throw new \RuntimeException('画像ファイルとして不正です（拡張子偽装の可能性があります）。');
        }
        [$width, $height] = $info;
        $realMime = (string) ($info['mime'] ?? $file->getClientMimeType());

        // 40MP超はメモリOOM回避で圧縮・サムネをスキップし、元をそのまま保存する。
        if ($width * $height > self::MAX_PIXELS) {
            $disk->putFileAs($directory, $file, $filename);

            return new StoredImage($path, $realMime, (int) $file->getSize());
        }

        $src = @imagecreatefromstring((string) $file->get());
        if (! $src instanceof \GdImage) {
            // GDが当該フォーマットを読めない環境。圧縮せずそのまま保存する（サムネ無し）。
            $disk->putFileAs($directory, $file, $filename);

            return new StoredImage($path, $realMime, (int) $file->getSize());
        }

        $thumbPath = "{$directory}/thumbs/{$uuid}.jpg";

        try {
            // JPEGは EXIF Orientation を補正してから保存する（再エンコードでEXIFは破棄される）。
            if (in_array($ext, ['jpg', 'jpeg'], true)) {
                $src = $this->applyExifOrientation($src, $file->getRealPath());
            }

            // 本体：長辺 1920 に収める（拡大はしない）。
            $main = $this->resample($src, self::MAX_LONG_EDGE);
            $disk->put($path, $this->encode($main, $ext, self::MAIN_JPEG_QUALITY));
            if ($main !== $src) {
                imagedestroy($main);
            }

            // サムネ：長辺 400・JPEG 品質 80。
            $thumb = $this->resample($src, self::THUMB_LONG_EDGE);
            $disk->put($thumbPath, $this->encode($thumb, 'jpg', self::THUMB_JPEG_QUALITY));
            if ($thumb !== $src) {
                imagedestroy($thumb);
            }
        } finally {
            imagedestroy($src);
        }

        return new StoredImage($path, $realMime, (int) $disk->size($path), $thumbPath);
    }

    /**
     * 長辺が $maxLongEdge を超える場合のみアスペクト比維持で縮小する。
     * 縮小不要ならソースをそのまま返す（呼び出し側は識別子比較で破棄要否を判定する）。
     */
    private function resample(\GdImage $src, int $maxLongEdge): \GdImage
    {
        $w = imagesx($src);
        $h = imagesy($src);
        $long = max($w, $h);
        if ($long <= $maxLongEdge) {
            return $src;
        }

        $scale = $maxLongEdge / $long;
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($nw, $nh);
        // 透過を維持する（PNG/WebP）。
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        return $dst;
    }

    /**
     * GD画像を指定形式のバイナリ文字列へエンコードする。
     * JPEG は透過非対応のため、透過部分を白で塗ってフラット化する。
     */
    private function encode(\GdImage $img, string $ext, int $quality): string
    {
        ob_start();
        switch ($ext) {
            case 'png':
                imagealphablending($img, false);
                imagesavealpha($img, true);
                imagepng($img, null, 6);
                break;
            case 'gif':
                imagegif($img);
                break;
            case 'webp':
                imagewebp($img, null, $quality);
                break;
            default: // jpg / jpeg
                $flat = $this->flatten($img);
                imagejpeg($flat, null, $quality);
                if ($flat !== $img) {
                    imagedestroy($flat);
                }
                break;
        }

        return (string) ob_get_clean();
    }

    /** 透過部分を白背景でフラット化した新しい画像を返す（JPEG出力用）。 */
    private function flatten(\GdImage $img): \GdImage
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $dst = imagecreatetruecolor($w, $h);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $w, $h, $white);
        imagealphablending($dst, true);
        imagecopy($dst, $img, 0, 0, 0, 0, $w, $h);

        return $dst;
    }

    /** EXIF Orientation を読み、正しい向きへ回転した画像を返す（対応は 180/±90 のみ）。 */
    private function applyExifOrientation(\GdImage $src, string $path): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $src;
        }

        $exif = @exif_read_data($path);
        if ($exif === false || empty($exif['Orientation'])) {
            return $src;
        }

        $rotated = match ((int) $exif['Orientation']) {
            3 => imagerotate($src, 180, 0),
            6 => imagerotate($src, -90, 0),
            8 => imagerotate($src, 90, 0),
            default => null,
        };

        if ($rotated instanceof \GdImage) {
            imagedestroy($src);

            return $rotated;
        }

        return $src;
    }
}

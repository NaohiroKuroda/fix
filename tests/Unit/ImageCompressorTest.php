<?php

namespace Tests\Unit;

use App\Services\Image\ImageCompressor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * 添付画像の圧縮・サムネイル生成（ImageCompressor）の検証。
 * 06_添付ファイル_詳細設計 §5・§6 の仕様どおりに保存されることを確認する。
 */
class ImageCompressorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_large_image_is_resized_and_thumbnail_generated(): void
    {
        // 長辺 3000px の画像 → 本体は長辺1920に縮小、サムネは長辺400。
        $file = UploadedFile::fake()->image('sample.jpg', 3000, 2000);

        $stored = app(ImageCompressor::class)->store($file, 'comments/5');

        $disk = Storage::disk('public');
        $disk->assertExists($stored->path);
        $this->assertNotNull($stored->thumbPath);
        $disk->assertExists($stored->thumbPath);

        // UUID 化されたパス（comments/5/{uuid}.jpg）。
        $this->assertMatchesRegularExpression('#^comments/5/[0-9a-f-]{36}\.jpg$#', $stored->path);
        $this->assertMatchesRegularExpression('#^comments/5/thumbs/[0-9a-f-]{36}\.jpg$#', $stored->thumbPath);

        // 本体：長辺 1920（アスペクト比維持で 1920x1280）。
        [$mw, $mh] = getimagesizefromstring($disk->get($stored->path));
        $this->assertSame(1920, $mw);
        $this->assertSame(1280, $mh);

        // サムネ：長辺 400（400x267）。
        [$tw, $th] = getimagesizefromstring($disk->get($stored->thumbPath));
        $this->assertSame(400, $tw);
        $this->assertSame(267, $th);

        $this->assertSame('image/jpeg', $stored->mime);
        $this->assertGreaterThan(0, $stored->size);
    }

    public function test_small_image_is_not_upscaled_but_thumbnail_generated(): void
    {
        // 長辺 300px（1920未満）→ 本体は拡大しない、サムネは生成される。
        $file = UploadedFile::fake()->image('small.png', 300, 200);

        $stored = app(ImageCompressor::class)->store($file, 'comments/9');

        $disk = Storage::disk('public');
        [$mw, $mh] = getimagesizefromstring($disk->get($stored->path));
        $this->assertSame(300, $mw, '1920未満の画像は拡大されない');
        $this->assertSame(200, $mh);
        $this->assertNotNull($stored->thumbPath);
        $disk->assertExists($stored->thumbPath);
    }

    public function test_non_image_is_stored_as_is_without_thumbnail(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 120, 'application/pdf');

        $stored = app(ImageCompressor::class)->store($file, 'comments/3');

        Storage::disk('public')->assertExists($stored->path);
        $this->assertNull($stored->thumbPath, '非画像はサムネイルを生成しない');
        $this->assertMatchesRegularExpression('#^comments/3/[0-9a-f-]{36}\.pdf$#', $stored->path);
    }

    public function test_spoofed_image_extension_is_rejected(): void
    {
        // 中身がテキストなのに拡張子が .jpg（拡張子偽装）→ 例外で拒否。
        $file = UploadedFile::fake()->createWithContent('evil.jpg', 'this is not an image');

        $this->expectException(\RuntimeException::class);
        app(ImageCompressor::class)->store($file, 'comments/1');
    }
}

<?php

namespace Tests\Unit;

use App\Http\Requests\StoreCommentRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * 添付バリデーション（拡張子ホワイトリスト・件数・サイズ）の検証。
 */
class StoreCommentRequestTest extends TestCase
{
    /** @return array<string, mixed> */
    private function rules(): array
    {
        return (new StoreCommentRequest)->rules();
    }

    public function test_png_and_jpg_pass(): void
    {
        $files = [
            UploadedFile::fake()->image('image (7).png', 100, 100),
            UploadedFile::fake()->image('image (8).png', 100, 100),
            UploadedFile::fake()->image('photo.jpg', 100, 100),
        ];
        $v = Validator::make(['files' => $files], $this->rules());
        $this->assertFalse($v->fails(), json_encode($v->errors()->all(), JSON_UNESCAPED_UNICODE));
    }

    public function test_japanese_named_png_passes(): void
    {
        $files = [UploadedFile::fake()->image('ジョブモネシアチャート.png', 100, 100)];
        $v = Validator::make(['files' => $files], $this->rules());
        $this->assertFalse($v->fails(), json_encode($v->errors()->all(), JSON_UNESCAPED_UNICODE));
    }

    public function test_pdf_and_generic_binary_pass(): void
    {
        $files = [
            UploadedFile::fake()->create('model-photo-list.pdf', 500, 'application/pdf'),
            UploadedFile::fake()->create('drawing.dwg', 500, 'application/octet-stream'),
        ];
        $v = Validator::make(['files' => $files], $this->rules());
        $this->assertFalse($v->fails(), json_encode($v->errors()->all(), JSON_UNESCAPED_UNICODE));
    }

    public function test_svg_is_rejected(): void
    {
        $files = [UploadedFile::fake()->create('x.svg', 10, 'image/svg+xml')];
        $v = Validator::make(['files' => $files], $this->rules());
        $this->assertTrue($v->fails());
    }
}

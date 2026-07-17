<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * 見積先（t_cost_quotations）のやり取り（チャット）への投稿入力。
 */
class StoreQuotationMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 許可する拡張子のホワイトリスト（06_添付ファイル_詳細設計 §2）。
     * SVG/exe/php 等のホワイトリスト外は一律拒否する（拡張子偽装対策）。
     *
     * @var list<string>
     */
    public const ALLOWED_EXTENSIONS = [
        // 画像
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif',
        // 文書
        'pdf', 'txt',
        // Office
        'xlsx', 'xls', 'docx', 'doc', 'pptx', 'ppt', 'csv',
        // CAD・図面
        'dwg', 'dxf', 'jww', 'jwc', 'sfc', 'p21',
        // 圧縮
        'zip',
    ];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // 本文は任意。ただし添付ファイルが無い場合は本文必須（本文・添付いずれか必要）。
            'body' => ['nullable', 'required_without:files', 'string', 'max:2000'],
            // 1送信あたり最大 5 件。
            'files' => ['nullable', 'array', 'max:5'],
            // 1ファイル最大 10MB、拡張子はホワイトリストに限定（クライアント拡張子を照合）。
            // 実MIMEの厳格照合は CAD/zip が octet-stream に潰れ誤検知するため課さず、
            // 画像は保存時（ImageCompressor）に getimagesize() で実体検証する。
            'files.*' => ['file', 'max:10240', 'extensions:'.implode(',', self::ALLOWED_EXTENSIONS)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required_without' => 'メッセージを入力するか、ファイルを添付してください。',
            'body.max' => 'メッセージは2000文字以内で入力してください。',
            'files.max' => '添付ファイルは5件までです。',
            'files.*.file' => '添付ファイルの形式が不正です。',
            'files.*.max' => '添付ファイルは1件あたり10MBまでです。',
            'files.*.extensions' => '添付できないファイル形式です。許可された形式のファイルを選択してください。',
        ];
    }

    public function body(): string
    {
        return (string) $this->input('body', '');
    }

    /**
     * アップロードされた添付ファイル。
     *
     * @return list<UploadedFile>
     */
    public function attachments(): array
    {
        return array_values(array_filter((array) $this->file('files')));
    }
}

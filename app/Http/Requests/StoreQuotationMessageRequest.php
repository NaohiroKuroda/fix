<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // 本文は任意。ただし添付ファイルが無い場合は本文必須（本文・添付いずれか必要）。
            'body' => ['nullable', 'required_without:files', 'string', 'max:2000'],
            'files' => ['nullable', 'array', 'max:10'],
            // 1ファイル最大 10MB。
            'files.*' => ['file', 'max:10240'],
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
            'files.max' => '添付ファイルは10件までです。',
            'files.*.file' => '添付ファイルの形式が不正です。',
            'files.*.max' => '添付ファイルは1件あたり10MBまでです。',
        ];
    }

    public function body(): string
    {
        return (string) $this->input('body', '');
    }

    /**
     * アップロードされた添付ファイル。
     *
     * @return list<\Illuminate\Http\UploadedFile>
     */
    public function attachments(): array
    {
        return array_values(array_filter((array) $this->file('files')));
    }
}

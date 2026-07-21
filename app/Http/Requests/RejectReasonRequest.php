<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** 発注 / 納品報告 / 請求の否認（理由入力）の入力。3フェーズ共通で使う。 */
class RejectReasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => '否認理由を入力してください。',
        ];
    }

    public function targetId(): int
    {
        return (int) $this->input('id');
    }

    public function reason(): string
    {
        return (string) $this->input('reason');
    }
}

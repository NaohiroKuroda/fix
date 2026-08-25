<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 業者承諾確認画面からの取消申請の入力。
 * 対象は見積先ID（t_payable_partners.id）の配列＋理由（必須）。
 * 部長取消申請（CancelActionRequest）と同様、理由入力を必須にする。
 */
class OrderDeliveryCancelActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:t_payable_partners,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => '対象を選択してください。',
            'ids.min' => '対象を選択してください。',
            'reason.required' => '理由を入力してください。',
            'reason.max' => '理由は1000文字以内で入力してください。',
        ];
    }

    /** @return list<int> */
    public function ids(): array
    {
        return array_values(array_map(intval(...), $this->input('ids', [])));
    }

    public function reason(): string
    {
        return (string) $this->input('reason');
    }
}

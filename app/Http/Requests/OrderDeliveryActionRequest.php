<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 発注〜納品〜請求フローの一括アクション（発注実行・承認・承諾記録・確認・仮締め）の入力。
 * 対象は見積先ID（t_cost_quotations.id）の配列。全画面共通で使う。
 */
class OrderDeliveryActionRequest extends FormRequest
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
            'ids.*' => ['integer', 'distinct', 'exists:t_cost_quotations,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => '対象を選択してください。',
            'ids.min' => '対象を選択してください。',
        ];
    }

    /** @return list<int> */
    public function ids(): array
    {
        return array_values(array_map(intval(...), $this->input('ids', [])));
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 業者選定の確定（業者選定画面）の入力。
 *
 * ボタンで選定された支払取引先（t_payable_partners.id）の配列を受け取り、採用業者として確定する。
 */
class ConfirmVendorSelectionRequest extends FormRequest
{
    /**
     * リクエストの認可可否を返す。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルールを返す。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'partnerIds' => ['required', 'array', 'min:1'],
            'partnerIds.*' => ['integer', 'distinct', 'exists:t_payable_partners,id'],
        ];
    }

    /**
     * バリデーションエラーメッセージを返す。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'partnerIds.required' => '発注業者を選定してください。',
            'partnerIds.min' => '発注業者を選定してください。',
        ];
    }

    /**
     * 検証済みの見積先 ID 配列（int に正規化）。
     *
     * @return list<int>
     */
    public function partnerIds(): array
    {
        /** @var list<int|string> $ids */
        $ids = $this->input('partnerIds', []);

        return array_values(array_map(intval(...), $ids));
    }
}

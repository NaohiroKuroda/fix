<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 業者選定の確定（業者選定画面）の入力。
 *
 * ボタンで選定された見積先（EstimateUnitCompany.id）の配列を受け取り、採用業者として確定する。
 */
class ConfirmVendorSelectionRequest extends FormRequest
{
    /**
     * リクエストの認可可否を返す。
     *
     * @return bool
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
            'companyIds' => ['required', 'array', 'min:1'],
            'companyIds.*' => ['integer', 'distinct', 'exists:estimate_unit_companies,id'],
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
            'companyIds.required' => '発注業者を選定してください。',
            'companyIds.min' => '発注業者を選定してください。',
        ];
    }

    /**
     * 検証済みの見積先 ID 配列（int に正規化）。
     *
     * @return list<int>
     */
    public function companyIds(): array
    {
        /** @var list<int|string> $ids */
        $ids = $this->input('companyIds', []);

        return array_values(array_map(intval(...), $ids));
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 見積依頼送信（見積り依頼画面）の入力。
 *
 * 選択された見積先（EstimateUnitCompany.id）の配列を受け取り、相見積依頼を記録する。
 */
class SendQuoteRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'companyIds' => ['required', 'array', 'min:1'],
            'companyIds.*' => ['integer', 'distinct', 'exists:estimate_unit_companies,id'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'companyIds.required' => '見積依頼を送信する見積先を選択してください。',
            'companyIds.min' => '見積依頼を送信する見積先を選択してください。',
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

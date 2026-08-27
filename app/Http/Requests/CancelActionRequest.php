<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 部長取消申請 / 部長取消承認の入力。
 * 選択された見積先（t_payable_partners.id）の配列と、理由（必須）を受け取る。
 * 否認（RejectQuotationRequest）と同様、理由入力を必須にする。
 */
class CancelActionRequest extends FormRequest
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
            'companyIds' => ['required', 'array', 'min:1'],
            'companyIds.*' => ['integer', 'distinct', 'exists:t_payable_partners,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'companyIds.required' => '対象の見積先を選択してください。',
            'companyIds.min' => '対象の見積先を選択してください。',
            'reason.required' => '理由を入力してください。',
            'reason.max' => '理由は1000文字以内で入力してください。',
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

    public function reason(): string
    {
        return (string) $this->input('reason');
    }
}

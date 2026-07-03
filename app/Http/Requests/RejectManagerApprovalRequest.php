<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 部長承認画面の「否認」（業者選定へ差し戻し）の入力。
 * 対象の見積先（t_cost_quotations.id）1件と否認理由を受け取る。
 */
class RejectManagerApprovalRequest extends FormRequest
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
            'companyId' => ['required', 'integer', 'exists:t_cost_quotations,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'companyId.required' => '対象の見積先が指定されていません。',
            'reason.required' => '否認理由を入力してください。',
            'reason.max' => '否認理由は1000文字以内で入力してください。',
        ];
    }

    public function companyId(): int
    {
        return (int) $this->input('companyId');
    }

    public function reason(): string
    {
        return (string) $this->input('reason');
    }
}

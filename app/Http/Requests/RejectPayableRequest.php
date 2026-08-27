<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 見積管理の「否認」共通の入力。対象の見積先（t_payable_partners.id）1件と否認理由を受け取る。
 *
 * - 部長承認の否認 … 業者選定へ差し戻す（APPLIED → DRAFT）
 * - 部長取消承認の否認 … 取消を却下し、承認済みのまま据え置く（CANCEL_APPLIED → APPROVED）
 */
class RejectPayableRequest extends FormRequest
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
            'partnerId' => ['required', 'integer', 'exists:t_payable_partners,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'partnerId.required' => '対象の見積先が指定されていません。',
            'reason.required' => '否認理由を入力してください。',
            'reason.max' => '否認理由は1000文字以内で入力してください。',
        ];
    }

    public function partnerId(): int
    {
        return (int) $this->input('partnerId');
    }

    public function reason(): string
    {
        return (string) $this->input('reason');
    }
}

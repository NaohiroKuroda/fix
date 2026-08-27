<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 請求（もらい）系画面の承認ステータス操作の入力。
 * 対象の請求取引先（t_billing_partners.id）と、理由（必要な画面のみ）を受け取る。
 *
 * 理由が必須の画面（取消申請 / 取消承認 / 否認）は {@see BillingReasonActionRequest} を使う。
 */
class BillingPartnerActionRequest extends FormRequest
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
            'partnerIds' => ['required', 'array', 'min:1'],
            'partnerIds.*' => ['integer', 'exists:t_billing_partners,id'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'partnerIds.required' => '対象の請求先が指定されていません。',
            'reason.required' => '理由を入力してください。',
            'reason.max' => '理由は1000文字以内で入力してください。',
        ];
    }

    /** @return list<int> */
    public function partnerIds(): array
    {
        return array_values(array_map('intval', (array) $this->input('partnerIds', [])));
    }

    /** 対象が1件だけの操作（取消申請 / 取消承認 / 否認）で使う先頭の ID。 */
    public function partnerId(): int
    {
        return $this->partnerIds()[0] ?? 0;
    }

    public function reason(): string
    {
        return (string) $this->input('reason', '');
    }
}

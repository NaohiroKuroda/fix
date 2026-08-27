<?php

namespace App\Http\Requests;

/**
 * 請求（もらい）系画面のうち、**理由の入力が必須**の操作
 * （見積の否認 / 見積取消申請 / 見積取消承認）の入力。
 *
 * 理由は対象項目のやり取り（コメント）へ記録される（共通仕様 §4）。
 */
class BillingReasonActionRequest extends BillingPartnerActionRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'reason' => ['required', 'string', 'max:1000'],
        ]);
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 見積管理画面の支払取引先（t_payable_partners）一括操作の共通入力。
 *
 * 部長承認 / 取消申請 / 取消承認 で共用する。ボタンで選択された見積先 ID の配列を受け取る。
 */
class PayablePartnerActionRequest extends FormRequest
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
            'partnerIds.required' => '対象の見積先を選択してください。',
            'partnerIds.min' => '対象の見積先を選択してください。',
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

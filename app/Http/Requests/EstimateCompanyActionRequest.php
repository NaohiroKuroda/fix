<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 見積管理画面の見積先（EstimateUnitCompany）一括操作の共通入力。
 *
 * 部長承認 / 取消申請 / 取消承認 で共用する。ボタンで選択された見積先 ID の配列を受け取る。
 */
class EstimateCompanyActionRequest extends FormRequest
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
            'companyIds.required' => '対象の見積先を選択してください。',
            'companyIds.min' => '対象の見積先を選択してください。',
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

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ステータス管理画面の検索条件。
 *
 * フロントからは camelCase のクエリで送られる。'all'/'' は「未指定」として扱う。
 */
class SearchEstimateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:255'],
            'subCate' => ['nullable', 'string', 'max:10'],
            'company' => ['nullable', 'string', 'max:255'],
            'companySelectStatus' => ['nullable', 'string', 'max:10'],
            'completeStatus' => ['nullable', 'string', 'max:10'],
            'constructAt' => ['nullable', 'date'],
            'completionAt' => ['nullable', 'date'],
            'adoptionFlg' => ['nullable', 'boolean'],
            'changedFlg' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Repository に渡す正規化済みの検索条件。
     * 'all'/'' は null に畳み込む。
     *
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return [
            'keyword' => $this->nullIfEmpty($this->input('keyword')),
            'subCate' => $this->nullIfEmpty($this->input('subCate')),
            'company' => $this->nullIfEmpty($this->input('company')),
            'companySelectStatus' => $this->nullIfEmpty($this->input('companySelectStatus')),
            'completeStatus' => $this->nullIfEmpty($this->input('completeStatus')),
            'constructAt' => $this->nullIfEmpty($this->input('constructAt')),
            'completionAt' => $this->nullIfEmpty($this->input('completionAt')),
            'adoptionFlg' => $this->boolean('adoptionFlg'),
            'changedFlg' => $this->boolean('changedFlg'),
        ];
    }

    /**
     * フォームの再表示用（'all' デフォルトを保持）。
     *
     * @return array<string, mixed>
     */
    public function filtersForView(): array
    {
        return [
            'keyword' => (string) $this->input('keyword', ''),
            'subCate' => (string) $this->input('subCate', 'all'),
            'company' => (string) $this->input('company', ''),
            'companySelectStatus' => (string) $this->input('companySelectStatus', 'all'),
            'completeStatus' => (string) $this->input('completeStatus', 'all'),
            'constructAt' => (string) $this->input('constructAt', ''),
            'completionAt' => (string) $this->input('completionAt', ''),
            'adoptionFlg' => $this->boolean('adoptionFlg'),
            'changedFlg' => $this->boolean('changedFlg'),
        ];
    }

    private function nullIfEmpty(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === 'all') {
            return null;
        }

        return (string) $value;
    }
}

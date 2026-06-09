<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 実行予算一覧の検索条件。
 */
class SearchBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'id' => ['nullable', 'string', 'max:255'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'dateColumn' => ['nullable', 'string', Rule::in($this->dateColumnKeys())],
            'monthFrom' => ['nullable', 'date_format:Y-m'],
            'monthTo' => ['nullable', 'date_format:Y-m'],
            'dealerName' => ['nullable', 'string', 'max:255'],
            'ownerBankName' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'in:id_desc,date_desc,date_asc'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        return [
            'id' => $this->nullIfEmpty($this->input('id')),
            'keyword' => $this->nullIfEmpty($this->input('keyword')),
            'dateColumn' => $this->nullIfEmpty($this->input('dateColumn')),
            'monthFrom' => $this->nullIfEmpty($this->input('monthFrom')),
            'monthTo' => $this->nullIfEmpty($this->input('monthTo')),
            'dealerName' => $this->nullIfEmpty($this->input('dealerName')),
            'ownerBankName' => $this->nullIfEmpty($this->input('ownerBankName')),
            'sort' => $this->nullIfEmpty($this->input('sort')),
        ];
    }

    /** @return array<string, mixed> */
    public function filtersForView(): array
    {
        return [
            'id' => (string) $this->input('id', ''),
            'keyword' => (string) $this->input('keyword', ''),
            'dateColumn' => (string) $this->input('dateColumn', 'delivery_date'),
            'monthFrom' => (string) $this->input('monthFrom', ''),
            'monthTo' => (string) $this->input('monthTo', ''),
            'dealerName' => (string) $this->input('dealerName', ''),
            'ownerBankName' => (string) $this->input('ownerBankName', ''),
            'sort' => (string) $this->input('sort', 'id_desc'),
        ];
    }

    /**
     * 日付カラム選択の許可キー一覧。
     *
     * @return list<string>
     */
    private function dateColumnKeys(): array
    {
        return array_keys((array) config('status_management.budget_date_column', []));
    }

    private function nullIfEmpty(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === 'all') {
            return null;
        }

        return (string) $value;
    }
}

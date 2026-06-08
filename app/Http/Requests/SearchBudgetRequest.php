<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'monthFrom' => ['nullable', 'date_format:Y-m'],
            'monthTo' => ['nullable', 'date_format:Y-m'],
            'sort' => ['nullable', 'string', 'in:id_desc,handover_asc'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        return [
            'id' => $this->nullIfEmpty($this->input('id')),
            'keyword' => $this->nullIfEmpty($this->input('keyword')),
            'monthFrom' => $this->nullIfEmpty($this->input('monthFrom')),
            'monthTo' => $this->nullIfEmpty($this->input('monthTo')),
            'sort' => $this->nullIfEmpty($this->input('sort')),
        ];
    }

    /** @return array<string, mixed> */
    public function filtersForView(): array
    {
        return [
            'id' => (string) $this->input('id', ''),
            'keyword' => (string) $this->input('keyword', ''),
            'monthFrom' => (string) $this->input('monthFrom', ''),
            'monthTo' => (string) $this->input('monthTo', ''),
            'sort' => (string) $this->input('sort', 'id_desc'),
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

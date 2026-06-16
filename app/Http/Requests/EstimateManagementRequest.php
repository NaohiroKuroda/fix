<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 見積管理画面（見積り依頼 / 発注業者選定 ほか）の絞り込み条件。
 */
class EstimateManagementRequest extends FormRequest
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
            'itemLabel' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        return [
            'keyword' => $this->nullIfEmpty($this->input('keyword')),
            'itemLabel' => $this->nullIfEmpty($this->input('itemLabel')),
        ];
    }

    /** @return array<string, string> */
    public function filtersForView(): array
    {
        return [
            'keyword' => (string) $this->input('keyword', ''),
            'itemLabel' => (string) $this->input('itemLabel', ''),
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

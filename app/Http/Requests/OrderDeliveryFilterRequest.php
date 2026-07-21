<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 発注〜納品〜請求フロー画面の絞り込み条件（物件名 / 項目名 / 見積先）。
 * 見積管理と同じフィルタ構成にする。
 */
class OrderDeliveryFilterRequest extends FormRequest
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
            'vendor' => ['nullable', 'string', 'max:255'],
            // 業者承諾確認画面の承諾確認フィルタ（全て / 確認済 / 未完了）。業者側の承諾有無で絞る。
            'acceptance' => ['nullable', 'in:all,confirmed,pending'],
            // 完了確認画面の請求月フィルタ（先月 / 当月 / 来月）。報告書提出日（月末17:00締め）で絞る。
            'billingMonth' => ['nullable', 'in:last,current,next'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        return [
            'keyword' => $this->nullIfEmpty($this->input('keyword')),
            'itemLabel' => $this->nullIfEmpty($this->input('itemLabel')),
            'vendor' => $this->nullIfEmpty($this->input('vendor')),
            'acceptance' => (string) $this->input('acceptance', 'pending'),
            'billingMonth' => (string) $this->input('billingMonth', 'current'),
        ];
    }

    /** @return array<string, mixed> */
    public function filtersForView(): array
    {
        return [
            'keyword' => (string) $this->input('keyword', ''),
            'itemLabel' => (string) $this->input('itemLabel', ''),
            'vendor' => (string) $this->input('vendor', ''),
            'acceptance' => (string) $this->input('acceptance', 'pending'),
            'billingMonth' => (string) $this->input('billingMonth', 'current'),
        ];
    }

    private function nullIfEmpty(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}

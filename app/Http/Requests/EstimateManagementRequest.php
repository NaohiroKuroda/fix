<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 見積管理画面（見積り依頼 / 業者選定 ほか）の絞り込み条件。
 */
class EstimateManagementRequest extends FormRequest
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
            'keyword' => ['nullable', 'string', 'max:255'],
            'itemLabel' => ['nullable', 'string', 'max:255'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            // 業者選定画面の回答状態フィルタ（全て / 回答あり / 回答なし）。
            'answer' => ['nullable', 'in:all,answered,unanswered'],
        ];
    }

    /**
     * リポジトリ用の絞り込み条件（空値は null）を返す。
     *
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return [
            'keyword' => $this->nullIfEmpty($this->input('keyword')),
            'itemLabel' => $this->nullIfEmpty($this->input('itemLabel')),
            'vendor' => $this->nullIfEmpty($this->input('vendor')),
            'answer' => (string) $this->input('answer', 'answered'),
        ];
    }

    /**
     * 画面再表示用の絞り込み条件（文字列に正規化）を返す。
     *
     * @return array<string, mixed>
     */
    public function filtersForView(): array
    {
        return [
            'keyword' => (string) $this->input('keyword', ''),
            'itemLabel' => (string) $this->input('itemLabel', ''),
            'vendor' => (string) $this->input('vendor', ''),
            'answer' => (string) $this->input('answer', 'answered'),
        ];
    }

    /**
     * 値が「未指定（null/空文字/'all'）」なら null、それ以外は文字列で返す。
     *
     * @param  mixed  $value
     * @return string|null
     */
    private function nullIfEmpty(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === 'all') {
            return null;
        }

        return (string) $value;
    }
}

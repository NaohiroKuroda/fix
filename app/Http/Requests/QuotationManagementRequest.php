<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 見積管理画面（見積り依頼 / 業者選定 ほか）の絞り込み条件。
 */
class QuotationManagementRequest extends FormRequest
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
            'keyword' => ['nullable', 'string', 'max:255'],
            'itemLabel' => ['nullable', 'string', 'max:255'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            // 業者選定画面の回答状態フィルタ（全て / 回答あり / 回答なし）。
            'answer' => ['nullable', 'in:all,answered,unanswered'],
            // コメント有無フィルタ（全て / コメントあり / コメントなし）。全画面共通。
            'comment' => ['nullable', 'in:all,has,none'],
            // 区分。all＝両区分を並べる（自区分以外は表示のみ）／payable＝支払のみ／billing＝請求のみ。
            'kind' => ['nullable', 'in:all,payable,billing'],
        ];
    }

    /**
     * リポジトリ用の絞り込み条件（空値は null）を返す。
     *
     * @return array<string, mixed>
     */
    public function filters(string $defaultKind = 'payable'): array
    {
        return [
            'keyword' => $this->nullIfEmpty($this->input('keyword')),
            'itemLabel' => $this->nullIfEmpty($this->input('itemLabel')),
            'vendor' => $this->nullIfEmpty($this->input('vendor')),
            'answer' => (string) $this->input('answer', 'all'),
            'comment' => (string) $this->input('comment', 'all'),
            'kind' => $this->kind($defaultKind),
        ];
    }

    /**
     * 画面再表示用の絞り込み条件（文字列に正規化）を返す。
     *
     * @return array<string, mixed>
     */
    public function filtersForView(string $defaultKind = 'payable'): array
    {
        return [
            'keyword' => (string) $this->input('keyword', ''),
            'itemLabel' => (string) $this->input('itemLabel', ''),
            'vendor' => (string) $this->input('vendor', ''),
            'answer' => (string) $this->input('answer', 'all'),
            'comment' => (string) $this->input('comment', 'all'),
            'kind' => $this->kind($defaultKind),
        ];
    }

    /**
     * 区分。未指定なら画面の既定値を使う。
     *
     * - `payable` / `billing` … その区分の取引先だけを表示する。画面の既定値。
     * - `all` … 両区分を同じ一覧に並べる。自区分以外は表示のみ（操作不可）。
     *
     * 支払系画面の既定は `payable`、請求系画面の既定は `billing`（処理フロー H列「区分が支払 / 請求」）。
     */
    private function kind(string $default): string
    {
        $value = (string) $this->input('kind', '');

        return in_array($value, ['all', 'payable', 'billing'], true) ? $value : $default;
    }

    /**
     * 値が「未指定（null/空文字/'all'）」なら null、それ以外は文字列で返す。
     */
    private function nullIfEmpty(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === 'all') {
            return null;
        }

        return (string) $value;
    }
}

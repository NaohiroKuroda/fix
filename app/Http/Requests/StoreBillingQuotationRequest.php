<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 【請求】見積作成モーダルの保存入力（t_billing_quotations ＋ t_billing_quotation_details）。
 *
 * 明細は空の予備行も送られてくるため、`isChanged = false`（未使用行）はサーバ側で捨てる。
 * メモ行（`isMemo`）は依頼内容以外を持たない。
 *
 * @see docs/detailed-design/quotations/06_請求_見積作成_詳細設計.md §6
 */
class StoreBillingQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'partnerId' => ['required', 'integer', 'exists:t_billing_partners,id'],
            'quotationDate' => ['required', 'date'],
            'taxAdjust' => ['nullable', 'numeric'],
            'withholdingIncomeTax' => ['nullable', 'numeric'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'fileUrl' => ['nullable', 'string', 'max:255'],
            // 見積書ファイル（任意）。上限は添付ファイル詳細設計に合わせて 10MB。
            'file' => ['nullable', 'file', 'max:10240'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.isMemo' => ['boolean'],
            'details.*.isChanged' => ['boolean'],
            'details.*.branchCode' => ['nullable', 'integer'],
            'details.*.departmentId' => ['nullable', 'integer'],
            'details.*.name' => ['nullable', 'string', 'max:255'],
            'details.*.quantity' => ['nullable', 'integer'],
            'details.*.unitId' => ['nullable', 'integer'],
            'details.*.unitPrice' => ['nullable', 'numeric'],
            'details.*.taxType' => ['nullable', 'in:TAXABLE,NON_TAXABLE'],
            'details.*.taxRate' => ['nullable', 'numeric'],
            'details.*.isTaxInclusive' => ['boolean'],
            'details.*.price' => ['nullable', 'numeric'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quotationDate.required' => '見積日を入力してください。',
            'details.required' => '明細を1行以上入力してください。',
            'file.max' => '見積書ファイルは10MB以内にしてください。',
        ];
    }

    public function partnerId(): int
    {
        return (int) $this->input('partnerId');
    }

    /**
     * 見積ヘッダー（t_billing_quotations）の入力値。
     *
     * @return array<string, mixed>
     */
    public function quotation(): array
    {
        return [
            'quotationDate' => (string) $this->input('quotationDate'),
            'taxAdjust' => (int) $this->input('taxAdjust', 0),
            'withholdingIncomeTax' => $this->input('withholdingIncomeTax') === null
                ? null
                : (int) $this->input('withholdingIncomeTax'),
            'comment' => (string) $this->input('comment', ''),
            'fileUrl' => (string) $this->input('fileUrl', ''),
        ];
    }

    /**
     * 保存する明細（**使用中の行だけ**）。空の予備行（`isChanged = false`）は捨てる。
     *
     * @return list<array<string, mixed>>
     */
    public function details(): array
    {
        $rows = [];
        foreach ((array) $this->input('details', []) as $detail) {
            if (($detail['isChanged'] ?? false) !== true) {
                continue;
            }
            $rows[] = $detail;
        }

        return $rows;
    }
}

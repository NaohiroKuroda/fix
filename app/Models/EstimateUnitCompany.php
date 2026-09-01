<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 見積業者（現行 felix_total の `estimate_unit_companies`）。
 *
 * 新スキーマの取引先（{@see TBillingPartner} / {@see TPayablePartner}）の `source_id` が
 * このテーブルの `id` を指す。通知メールでは
 *   - 宛先の解決（`staff_id` → {@see CompanyStaff}、無ければ `companies` のメール）
 *   - 業者マイページのログイン URL（`/estimate/login/{id}/{token}`）
 * に必要なため、新Fix からも参照する。更新は現行 felix_total 側の責務。
 *
 * @property int $id
 * @property int $estimate_unit_id
 * @property int $company_id
 * @property list<int|string>|null $staff_id
 * @property bool|null $invoice_flg
 */
class EstimateUnitCompany extends Model
{
    use SoftDeletes;

    protected $table = 'estimate_unit_companies';

    protected $guarded = [];

    protected $casts = [
        // 現行と同じく担当者IDの配列（JSON）として扱う。
        'staff_id' => 'array',
        'invoice_flg' => 'boolean',
    ];

    // 見積業者の会社
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\HasBlameColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 請求取引先（もらい。旧：estimate_unit_companies の invoice_flg = 1）
 *
 * 2026-08 のスキーマ改訂で見積先が支払／請求で分割された。支払＝{@see TPayablePartner}。
 * 承認ステータスの語彙は支払と共通（DRAFT / APPLIED / APPROVED / CANCEL_APPLIED / CANCELLED）。
 */
class TBillingPartner extends Model
{
    use HasBlameColumns, HasFactory;

    protected $table = 't_billing_partners';

    protected $fillable = [
        'building_budget_item_id',
        'company_id',
        'branch_company_id',
        'counter_company_id',
        'approval_status',
        'created_at',
        'updated_at',
        'source_id',
    ];

    // 建物予算項目
    public function budgetItem()
    {
        return $this->belongsTo(TBuildingBudgetItem::class, 'building_budget_item_id');
    }

    // 請求先
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    // 請求見積（FELIX が作成した見積。版管理）
    public function quotations()
    {
        return $this->hasMany(TBillingQuotation::class, 'billing_partner_id');
    }

    // 最新の請求見積（is_latest = true）
    public function latestQuotation()
    {
        return $this->hasOne(TBillingQuotation::class, 'billing_partner_id')
            ->where('is_latest', true);
    }
}

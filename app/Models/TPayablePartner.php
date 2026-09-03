<?php

namespace App\Models;

use App\Models\Concerns\HasBlameColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 支払取引先（旧：estimate_unit_companies の invoice_flg = 0）
 *
 * 2026-08 のスキーマ改訂で `t_cost_quotations` が支払／請求で分割され、
 * 支払（はらい）＝本テーブル、請求（もらい）＝ `t_billing_partners` になった。
 * これに伴い旧 `is_billing_target` 列は廃止されている。
 *
 * 否認理由（旧 `deny_comment` 列）も新スキーマには存在しない。否認理由は項目単位の
 * コメントスレッド（t_comments に `【否認】` 付きで投稿）を唯一の記録とする。
 */
class TPayablePartner extends Model
{
    use HasBlameColumns, HasFactory;

    protected $table = 't_payable_partners';

    protected $fillable = [
        'building_budget_item_id',
        'company_id',
        'branch_company_id',
        'counter_company_id',
        'is_drafted',
        'approval_status',
        'created_at',
        'updated_at',
        'source_id',
    ];

    protected $casts = [
        'is_drafted' => 'boolean',
    ];

    // 建物予算項目
    public function budgetItem()
    {
        return $this->belongsTo(TBuildingBudgetItem::class, 'building_budget_item_id');
    }

    // 見積先
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    // 見積先支店
    public function branchCompany()
    {
        return $this->belongsTo(Company::class, 'branch_company_id');
    }

    // 見積先窓口
    public function counterCompany()
    {
        return $this->belongsTo(Company::class, 'counter_company_id');
    }

    // 支払見積（業者から提出された見積。版管理）
    public function quotations()
    {
        return $this->hasMany(TPayableQuotation::class, 'payable_partner_id');
    }

    // 最新の支払見積（is_latest = true）
    public function latestQuotation()
    {
        return $this->hasOne(TPayableQuotation::class, 'payable_partner_id')
            ->where('is_latest', true);
    }

    // 支払見積依頼（相見積依頼の送信履歴）。件数＝見積依頼送信回数（0=未依頼）。
    public function requests()
    {
        return $this->hasMany(TPayableQuotationRequest::class, 'payable_partner_id');
    }

    // 発注書（2026-08 のスキーマ改訂で追加。金額・業者の承諾日時を持つ）。
    // 部長の発注承認時に発行し、発注の否認・取消承認で削除する。
    public function payableOrder()
    {
        return $this->hasOne(TPayableOrder::class, 'payable_partner_id');
    }
}

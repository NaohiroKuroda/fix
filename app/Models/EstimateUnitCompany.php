<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 見積ユニットに紐づく発注先（業者）。`estimate_unit_companies` テーブル。
 *
 * @property int $id
 * @property int $estimate_unit_id
 * @property int|null $company_id
 * @property int|null $adoption_flg 採用フラグ（1 = 採用）
 * @property int|null $changed_flg 金額変更フラグ（1 = 変更あり）
 * @property int|null $order_flg 発注フラグ
 * @property string|null $delivery_company_name
 */
class EstimateUnitCompany extends Model
{
    protected $table = 'estimate_unit_companies';

    protected $guarded = [];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(EstimateUnit::class, 'estimate_unit_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /** 支店（branch_id が company_id と異なる場合に併記する）。 */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'branch_id');
    }

    /** 支払先（緑ラベル表示用）。 */
    public function payCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'pay_company_id');
    }

    /** アップロードファイル（type=1: 見積書 / type=2: 必須ファイル）。 */
    public function files(): HasMany
    {
        return $this->hasMany(EstimateUnitCompanyFile::class, 'estimate_unit_company_id');
    }

    /** 選定ステータスの変更履歴（選定日の取得元）。 */
    public function changeHistories(): HasMany
    {
        return $this->hasMany(EstimateUnitChangeHistory::class, 'estimate_unit_company_id');
    }

    /** 相見積の送信履歴（依頼）。 */
    public function orderHistories(): HasMany
    {
        return $this->hasMany(EstimateOrderHistory::class, 'estimate_unit_company_id');
    }

    /** 完了報告の承認履歴。 */
    public function completeHistories(): HasMany
    {
        return $this->hasMany(CompleteApprovalHistory::class, 'estimate_unit_company_id');
    }

    /** 完了報告書（否認判定）。 */
    public function endReports(): HasMany
    {
        return $this->hasMany(EndReport::class, 'estimate_unit_company_id');
    }

    /** 発注書。 */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'estimate_unit_company_id');
    }

    /** 希望/回答日（承認納期日）。 */
    public function dates(): HasMany
    {
        return $this->hasMany(EstimateUnitCompanyDate::class, 'estimate_unit_company_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 業者の担当者（現行 felix_total の `company_staff`）。
 *
 * 通知メールの宛先解決（`estimate_unit_companies.staff_id` → 担当者メール）に使う。
 * 担当者には有効期間（`date_start` / `date_end`）があり、期間外は宛先にしない。
 *
 * @property int $id
 * @property int $company_id
 * @property string|null $name
 * @property string|null $email
 * @property Carbon|null $date_start
 * @property Carbon|null $date_end
 */
class CompanyStaff extends Model
{
    use SoftDeletes;

    protected $table = 'company_staff';

    protected $guarded = [];

    protected $casts = [
        'date_start' => 'date',
        'date_end' => 'date',
    ];

    // 所属する業者
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}

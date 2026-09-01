<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 業者マイページのアクセストークン（現行 felix_total の `company_tokens`）。
 *
 * 会社単位で1件を使い回す（無ければ発行）。現行の
 * `EstimateCustomDetailController::create_token()` と同じ扱い。
 *
 * @property int $id
 * @property int $company_id
 * @property string $access_token
 */
class CompanyToken extends Model
{
    protected $table = 'company_tokens';

    protected $guarded = [];

    // トークンを持つ業者
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}

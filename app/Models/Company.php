<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 業者（発注先）。`companies` テーブルにマッピングする。
 *
 * @property int $id
 * @property string|null $company_name
 * @property int|null $sub_cate
 * @property int|null $parent_id
 * @property string|null $keisho
 * @property string|null $estimate_email
 * @property string|null $estimate_tantou_email
 */
class Company extends Model
{
    protected $table = 'companies';

    protected $guarded = [];

    // 親会社（支店レコードの場合に本社を指す）
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * 通知メールの宛名に使う会社名。支店の場合は「本社名 支店名」で連結する。
     * 現行 felix_total の `Company::$all_company_name` を踏襲する
     * （親が「様」＝法人でない場合は支店名のみ）。
     */
    public function getAllCompanyNameAttribute(): string
    {
        if (! $this->parent_id) {
            return (string) $this->company_name;
        }

        $parent = $this->parent;

        if ($parent === null || $parent->keisho === '様') {
            return (string) $this->company_name;
        }

        return trim($parent->company_name.' '.$this->company_name);
    }
}

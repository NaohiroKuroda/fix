<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 業者（発注先）。`companies` テーブルにマッピングする。
 *
 * @property int $id
 * @property string|null $company_name
 * @property int|null $sub_cate
 */
class Company extends Model
{
    protected $table = 'companies';

    protected $guarded = [];
}

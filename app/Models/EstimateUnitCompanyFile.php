<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 業者ごとのアップロードファイル。`estimate_unit_company_files`。
 * type=1: 見積書（相見積価格・見積UP）, type=2: 必須ファイル。
 *
 * @property int $estimate_unit_company_id
 * @property float|null $price
 * @property string|null $date
 * @property int|null $type
 * @property int|null $file_cate
 * @property int|null $complete_flg
 */
class EstimateUnitCompanyFile extends Model
{
    protected $table = 'estimate_unit_company_files';

    protected $guarded = [];
}

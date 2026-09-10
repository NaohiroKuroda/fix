<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 発注書（現行 felix_total の `orders`）
 *
 * 業者マイページの発注書タブ・請負承認は現行の `orders` が起点で、
 * 発注書のリンクは `/order/{orders.id}/?estimate_unit_company_id={見積業者ID}` の形になる。
 * 新Fix は**参照のみ**（作成・更新は現行側）。
 *
 * `status` の意味（現行 config/constant.php の report_status_list）:
 *   10=発行済 / 11=支払データ / 20=仕訳データ作成 / 98=変更済 / 99=キャンセル
 *
 * @property int $id
 * @property int|null $estimate_unit_company_id
 * @property int|null $status
 */
class Order extends Model
{
    protected $table = 'orders';

    /** キャンセル・変更済み（業者マイページに出ない発注書）。 */
    public const HIDDEN_STATUSES = [98, 99];
}

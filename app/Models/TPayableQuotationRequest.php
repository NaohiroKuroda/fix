<?php

namespace App\Models;

use App\Models\Concerns\HasBlameColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 支払見積依頼（相見積依頼の送信履歴）。1 行＝支払取引先（t_payable_partners）への 1 回の依頼送信。
 * 送信回数 ＝ payable_partner_id ごとの行数、未依頼 ＝ 0 件。
 *
 * 2026-08 のスキーマ改訂で `t_cost_quotation_requests` → `t_payable_quotation_requests` へ改称。
 *
 * @property int $id
 * @property int $payable_partner_id
 * @property Carbon $requested_at
 *
 * @mixin \Eloquent
 */
class TPayableQuotationRequest extends Model
{
    use HasBlameColumns;

    protected $table = 't_payable_quotation_requests';

    protected $fillable = [
        'payable_partner_id',
        'requested_at',
        'source_id',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
    ];

    /** 支払取引先（t_payable_partners）。 */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(TPayablePartner::class, 'payable_partner_id');
    }
}

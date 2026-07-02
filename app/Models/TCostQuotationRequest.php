<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 費用見積依頼（相見積依頼の送信履歴）。1 行＝見積先（t_cost_quotations）への 1 回の依頼送信。
 * 送信回数 ＝ cost_quotation_id ごとの行数、未依頼 ＝ 0 件。
 *
 * @property int $id
 * @property int $cost_quotation_id
 * @property \Illuminate\Support\Carbon $requested_at
 *
 * @mixin \Eloquent
 */
class TCostQuotationRequest extends Model
{
    protected $table = 't_cost_quotation_requests';

    protected $fillable = [
        'cost_quotation_id',
        'requested_at',
        'source_id',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
    ];

    /** 見積先（t_cost_quotations）。 */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(TCostQuotation::class, 'cost_quotation_id');
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\HasBlameColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 費用見積履歴（旧：estimate_unit_company_files (type = 1)）
 *
 * @see database/migrations/2026_06_19_175319_create_t_cost_quotation_histories_table.php
 */
class TCostQuotationHistory extends Model
{
    use HasBlameColumns, HasFactory;

    protected $table = 't_cost_quotation_histories';

    protected $fillable = [
        'cost_quotation_id',
        'is_latest',
        'file_url',
        'quotation_date',
        'amount_excluding_tax',
        'tax_adjust',
        'withholding_income_tax',
        'comment',
        'created_at',
        'updated_at',
        'source_id',
    ];

    protected $casts = [
        'is_latest' => 'boolean',
    ];

    // 費用見積
    public function quotation()
    {
        return $this->belongsTo(TCostQuotation::class, 'cost_quotation_id');
    }

    // 費用見積明細
    public function details()
    {
        return $this->hasMany(TCostQuotationDetail::class, 'cost_quotation_history_id');
    }

    // 費用見積承認履歴
    public function approvalActions()
    {
        return $this->hasMany(TCostQuotationApprovalAction::class, 'cost_quotation_history_id');
    }
}

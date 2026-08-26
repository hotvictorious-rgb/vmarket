<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * [AI] Class PosDebtTransaction
 * Tracks debt issuances, partial cash/card installment repayments, and adjustments.
 */
class PosDebtTransaction extends Model
{
    use HasFactory;

    protected $table = 'pos_debt_transactions';

    protected $fillable = [
        'ledger_id',
        'seller_id',
        'branch_id',
        'order_id',
        'transaction_type',
        'amount',
        'payment_method',
        'collected_by_id',
        'notes',
    ];

    protected $casts = [
        'ledger_id' => 'integer',
        'seller_id' => 'integer',
        'branch_id' => 'integer',
        'amount' => 'float',
        'collected_by_id' => 'integer',
    ];

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(PosCustomerLedger::class, 'ledger_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }
}

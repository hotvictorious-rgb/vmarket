<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * [AI] Class PosCustomerLedger
 * Manages physical customer credit balances, credit limits, and aging status.
 */
class PosCustomerLedger extends Model
{
    use HasFactory;

    protected $table = 'pos_customer_ledgers';

    protected $fillable = [
        'seller_id',
        'branch_id',
        'customer_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'total_credit_due',
        'credit_limit',
        'due_date',
        'aging_bucket',
        'is_credit_blocked',
        'notes',
    ];

    protected $casts = [
        'seller_id' => 'integer',
        'branch_id' => 'integer',
        'customer_id' => 'integer',
        'total_credit_due' => 'float',
        'credit_limit' => 'float',
        'due_date' => 'date',
        'is_credit_blocked' => 'boolean',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PosDebtTransaction::class, 'ledger_id')->orderBy('created_at', 'desc');
    }

    /**
     * [AI] Helper to recalculate dynamic aging bucket based on oldest open debt or due_date.
     */
    public function getCalculatedAgingAttribute(): string
    {
        if ($this->total_credit_due <= 0) {
            return 'settled';
        }

        $referenceDate = $this->due_date ? \Carbon\Carbon::parse($this->due_date) : $this->updated_at;
        $daysOverdue = now()->diffInDays($referenceDate, false);

        if ($daysOverdue >= 0) {
            return 'current'; // 0-7 Days
        }

        $absDays = abs($daysOverdue);
        if ($absDays <= 30) {
            return 'due'; // 8-30 Days
        }

        return 'critical'; // 30+ Days
    }
}

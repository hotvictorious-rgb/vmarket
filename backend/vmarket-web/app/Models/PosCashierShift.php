<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * [AI] Class PosCashierShift
 * Records cashier shift opening floats, counted drawer cash, and blind-close discrepancies.
 */
class PosCashierShift extends Model
{
    use HasFactory;

    protected $table = 'pos_cashier_shifts';

    protected $fillable = [
        'seller_id',
        'branch_id',
        'cashier_id',
        'opened_at',
        'closed_at',
        'starting_float',
        'expected_cash',
        'counted_cash',
        'variance',
        'status',
        'notes',
    ];

    protected $casts = [
        'seller_id' => 'integer',
        'branch_id' => 'integer',
        'cashier_id' => 'integer',
        'starting_float' => 'float',
        'expected_cash' => 'float',
        'counted_cash' => 'float',
        'variance' => 'float',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }
}

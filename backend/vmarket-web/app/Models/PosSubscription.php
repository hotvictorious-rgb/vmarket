<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * [AI] Class PosSubscription
 * Tracks Multi-Branch Pro subscription tiers, payments (Paystack/Offline/Wallet), and active status.
 */
class PosSubscription extends Model
{
    use HasFactory;

    protected $table = 'pos_subscriptions';

    protected $fillable = [
        'seller_id',
        'plan_type',
        'billing_cycle',
        'price_paid',
        'payment_method',
        'status',
        'offline_payment_id',
        'paystack_reference',
        'started_at',
        'expires_at',
    ];

    protected $casts = [
        'seller_id' => 'integer',
        'price_paid' => 'float',
        'offline_payment_id' => 'integer',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function isCurrentlyActive(): bool
    {
        if ($this->plan_type === 'starter_free') {
            return true;
        }

        return $this->status === 'active' && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}

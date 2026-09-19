<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * [AI] Model CheckoutIntent
 * Represents the durable delivery checkout concurrency anchor, frozen snapshot,
 * and pre-payment purchase intent prior to vendor Order creation.
 *
 * @property int $id
 * @property string $order_group_id
 * @property int $customer_id
 * @property string $idempotency_key
 * @property string $cart_fingerprint
 * @property string|null $active_cart_token
 * @property string $status
 * @property string $total_amount
 * @property string $currency
 * @property array $checkout_snapshot
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon|null $converted_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class CheckoutIntent extends Model
{
    protected $table = 'checkout_intents';

    protected $fillable = [
        'order_group_id',
        'customer_id',
        'idempotency_key',
        'cart_fingerprint',
        'active_cart_token',
        'status',
        'total_amount',
        'currency',
        'checkout_snapshot',
        'expires_at',
        'converted_at',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'checkout_snapshot' => 'array',
        'expires_at' => 'datetime',
        'converted_at' => 'datetime',
        'total_amount' => 'string',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class, 'order_group_id', 'order_group_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || now()->greaterThanOrEqualTo($this->expires_at);
    }
}

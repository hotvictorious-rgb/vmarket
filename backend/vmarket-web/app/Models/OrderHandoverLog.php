<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * [AI] Class OrderHandoverLog
 * Immutable Chain-of-Custody audit log recording exact in-shop staff handover to delivery riders.
 */
class OrderHandoverLog extends Model
{
    use HasFactory;

    protected $table = 'order_handover_logs';

    protected $fillable = [
        'order_id',
        'seller_id',
        'branch_id',
        'handed_over_by_id',
        'handed_over_by_name',
        'delivery_man_id',
        'delivery_man_name',
        'pickup_otp_used',
        'handed_over_at',
        'notes',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'seller_id' => 'integer',
        'branch_id' => 'integer',
        'handed_over_by_id' => 'integer',
        'delivery_man_id' => 'integer',
        'handed_over_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function deliveryMan(): BelongsTo
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id');
    }
}

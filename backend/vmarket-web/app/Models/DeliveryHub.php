<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * [AI] Phase A8 — Logistics Hub Decoupling
 *
 * DeliveryHub is the authoritative internal logistics hub model
 * (landmarks / motor parks). Its geography now links to the
 * canonical Lga model instead of the legacy DeliveryCity model.
 *
 * Authoritative per CLAUDE.md:
 *   DeliveryHub (Internal physical logistics hub only)
 *   ≠ Hubs as customer-facing geography
 */
class DeliveryHub extends Model
{
    use HasFactory;

    protected $table = 'delivery_hubs';

    protected $fillable = [
        'lga_id',
        'name',
        'type',
        'base_shipping_cost',
        'rider_delivery_fee',
        'estimated_delivery_time',
        'is_active',
    ];

    protected $casts = [
        'lga_id'             => 'integer',
        'base_shipping_cost' => 'float',
        'rider_delivery_fee' => 'float',
        'is_active'          => 'boolean',
    ];

    /**
     * The canonical LGA this hub is located in.
     */
    public function lga(): BelongsTo
    {
        return $this->belongsTo(Lga::class, 'lga_id');
    }
}

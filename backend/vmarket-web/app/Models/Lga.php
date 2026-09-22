<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * [AI] LGA (Local Government Area) Model - Canonical Geography V1
 *
 * Part of: VMarket Geography & Fulfillment Architecture
 * Phase: 2 - Canonical Geography Schema
 *
 * Nigeria has 774 LGAs. FCT uses Area Councils but we expose them as LGAs
 * for API consistency.
 *
 * IMPORTANT: An LGA is NOT the delivery address itself.
 * The actual address is: Country + State + LGA + street address + coordinates.
 *
 * An LGA is used for:
 * - Shop location (origin for delivery routing)
 * - Customer address (destination for delivery routing)
 * - Delivery lane routing (Origin LGA → Destination LGA)
 *
 * Relationships:
 * - belongsTo(State)
 */
class Lga extends Model
{
    protected $fillable = ['state_id', 'name', 'code', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the state this LGA belongs to.
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * Scope to only active LGAs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

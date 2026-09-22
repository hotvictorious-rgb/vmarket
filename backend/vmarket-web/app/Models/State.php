<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * [AI] State Model - Canonical Geography V1
 *
 * Part of: VMarket Geography & Fulfillment Architecture
 * Phase: 2 - Canonical Geography Schema
 *
 * Nigerian states (36 states + FCT).
 *
 * Relationships:
 * - belongsTo(Country)
 * - hasMany(Lga)
 */
class State extends Model
{
    protected $fillable = ['country_id', 'name', 'code', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the country this state belongs to.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get all LGAs in this state.
     */
    public function lgas(): HasMany
    {
        return $this->hasMany(Lga::class);
    }

    /**
     * Get only active LGAs in this state.
     */
    public function activeLgas(): HasMany
    {
        return $this->lgas()->where('is_active', true);
    }

    /**
     * Scope to only active states.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

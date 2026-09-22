<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * [AI] Country Model - Canonical Geography V1
 *
 * Part of: VMarket Geography & Fulfillment Architecture
 * Phase: 2 - Canonical Geography Schema
 *
 * Top-level geography entity. Nigeria is the primary country for VMarket.
 *
 * Relationships:
 * - hasMany(State)
 */
class Country extends Model
{
    protected $fillable = ['name', 'iso_code', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all states in this country.
     */
    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }

    /**
     * Get only active states in this country.
     */
    public function activeStates(): HasMany
    {
        return $this->states()->where('is_active', true);
    }

    /**
     * Scope to only active countries.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

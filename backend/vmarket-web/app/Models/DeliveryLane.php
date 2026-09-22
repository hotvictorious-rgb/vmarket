<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class DeliveryLane
 *
 * Directional Origin LGA -> Destination LGA delivery routing.
 * Marketplace-owned fulfillment network.
 *
 * @property int $id
 * @property int $origin_country_id
 * @property int $origin_state_id
 * @property int $origin_lga_id
 * @property int $destination_country_id
 * @property int $destination_state_id
 * @property int $destination_lga_id
 * @property bool $is_enabled
 * @property float $delivery_fee
 * @property string|null $estimated_delivery_time
 *
 * @package App\Models
 */
class DeliveryLane extends Model
{
    protected $table = 'delivery_lanes';

    protected $fillable = [
        'origin_country_id',
        'origin_state_id',
        'origin_lga_id',
        'destination_country_id',
        'destination_state_id',
        'destination_lga_id',
        'is_enabled',
        'delivery_fee',
        'estimated_delivery_time',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'delivery_fee' => 'decimal:2',
    ];

    // ==========================================
    // Relationships
    // ==========================================

    public function originCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'origin_country_id');
    }

    public function originState(): BelongsTo
    {
        return $this->belongsTo(State::class, 'origin_state_id');
    }

    public function originLga(): BelongsTo
    {
        return $this->belongsTo(Lga::class, 'origin_lga_id');
    }

    public function destinationCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'destination_country_id');
    }

    public function destinationState(): BelongsTo
    {
        return $this->belongsTo(State::class, 'destination_state_id');
    }

    public function destinationLga(): BelongsTo
    {
        return $this->belongsTo(Lga::class, 'destination_lga_id');
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    public function scopeForOriginAndDestination(Builder $query, int $originLgaId, int $destinationLgaId): Builder
    {
        return $query->where('origin_lga_id', $originLgaId)
                     ->where('destination_lga_id', $destinationLgaId);
    }

    // ==========================================
    // Static Helper Methods
    // ==========================================

    /**
     * Find enabled delivery lane between two LGAs
     */
    public static function findLane(int $originLgaId, int $destinationLgaId): ?self
    {
        return static::enabled()
            ->forOriginAndDestination($originLgaId, $destinationLgaId)
            ->first();
    }

    /**
     * Check if a delivery lane is available and enabled
     */
    public static function isLaneAvailable(int $originLgaId, int $destinationLgaId): bool
    {
        return static::enabled()
            ->forOriginAndDestination($originLgaId, $destinationLgaId)
            ->exists();
    }

    /**
     * Get authoritative delivery fee for an LGA pair
     */
    public static function getDeliveryFee(int $originLgaId, int $destinationLgaId): ?float
    {
        $lane = static::findLane($originLgaId, $destinationLgaId);
        return $lane ? (float) $lane->delivery_fee : null;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class DeliveryLane
 *
 * Hierarchical Hybrid Delivery Routing Engine:
 * - Intra-State: Directional Origin LGA -> Destination LGA (Local intra-city / inter-LGA courier)
 * - Inter-State: Directional Origin State -> Destination State (National freight / transit corridor)
 *
 * @property int $id
 * @property int $origin_country_id
 * @property int $origin_state_id
 * @property int|null $origin_lga_id
 * @property int $destination_country_id
 * @property int $destination_state_id
 * @property int|null $destination_lga_id
 * @property string $lane_type  // 'intra_state' or 'inter_state'
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
        'lane_type',
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

    public function scopeIntraState(Builder $query): Builder
    {
        return $query->where('lane_type', 'intra_state');
    }

    public function scopeInterState(Builder $query): Builder
    {
        return $query->where('lane_type', 'inter_state');
    }

    public function scopeForOriginAndDestination(Builder $query, int $originLgaId, int $destinationLgaId): Builder
    {
        return $query->where('origin_lga_id', $originLgaId)
                     ->where('destination_lga_id', $destinationLgaId);
    }

    // ==========================================
    // Authoritative Hierarchical Resolver
    // ==========================================

    /**
     * Resolve delivery route, fee, and ETA using the 2-Tier Hierarchical Matrix:
     * 1. Intra-State: If Origin State == Destination State, evaluate LGA-to-LGA lane.
     *    Fallback: State Default Intra-State Fee.
     * 2. Inter-State: If Origin State != Destination State, evaluate State-to-State lane.
     *    Fallback: National Default Inter-State Fee.
     */
    public static function resolveLane(
        int $originStateId,
        ?int $originLgaId,
        int $destStateId,
        ?int $destLgaId
    ): array {
        // TIER 1: Intra-State (Same State)
        if ($originStateId === $destStateId) {
            if ($originLgaId && $destLgaId) {
                $lgaLane = static::enabled()
                    ->where('origin_state_id', $originStateId)
                    ->where('destination_state_id', $destStateId)
                    ->where('origin_lga_id', $originLgaId)
                    ->where('destination_lga_id', $destLgaId)
                    ->first();

                if ($lgaLane) {
                    return [
                        'lane' => $lgaLane,
                        'fee' => (float) $lgaLane->delivery_fee,
                        'eta' => $lgaLane->estimated_delivery_time ?? '24-48 hours',
                        'lane_type' => 'intra_state',
                        'is_interstate' => false,
                        'is_fallback' => false,
                    ];
                }
            }

            // Fallback: Default Intra-State Fee
            $defaultIntraFee = (float) (getWebConfig(name: 'default_intrastate_delivery_fee') ?? 2000.00);
            return [
                'lane' => null,
                'fee' => $defaultIntraFee,
                'eta' => '24-48 hours',
                'lane_type' => 'intra_state_fallback',
                'is_interstate' => false,
                'is_fallback' => true,
            ];
        }

        // TIER 2: Inter-State (Different States)
        $stateLane = static::enabled()
            ->where('origin_state_id', $originStateId)
            ->where('destination_state_id', $destStateId)
            ->where(function ($q) {
                $q->whereNull('origin_lga_id')->orWhere('origin_lga_id', 0);
            })
            ->where(function ($q) {
                $q->whereNull('destination_lga_id')->orWhere('destination_lga_id', 0);
            })
            ->first();

        if ($stateLane) {
            return [
                'lane' => $stateLane,
                'fee' => (float) $stateLane->delivery_fee,
                'eta' => $stateLane->estimated_delivery_time ?? '2-4 business days',
                'lane_type' => 'inter_state',
                'is_interstate' => true,
                'is_fallback' => false,
            ];
        }

        // Fallback: Default National Inter-State Fee
        $defaultInterFee = (float) (getWebConfig(name: 'default_interstate_delivery_fee') ?? 5000.00);
        return [
            'lane' => null,
            'fee' => $defaultInterFee,
            'eta' => '3-5 business days',
            'lane_type' => 'inter_state_fallback',
            'is_interstate' => true,
            'is_fallback' => true,
        ];
    }

    // ==========================================
    // Backward-Compatible Static Helpers
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

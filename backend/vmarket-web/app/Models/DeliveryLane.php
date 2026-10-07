<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class DeliveryLane
 *
 * Hierarchical Zonal & Bidirectional Delivery Routing Engine:
 * - Tier 1: Intra-LGA (Same LGA - Municipal delivery)
 * - Tier 2: Inter-LGA (Same State, Different LGA - Regional transit)
 * - Tier 3: Inter-State (Different States - National freight)
 * - Bidirectional Custom Overrides: Setting Route A ⟷ B automatically applies to B ⟷ A.
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
 * @property bool $is_bidirectional
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
        'is_bidirectional',
        'delivery_fee',
        'estimated_delivery_time',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_bidirectional' => 'boolean',
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

    // ==========================================
    // Authoritative Hierarchical & Bidirectional Resolver
    // ==========================================

    /**
     * Resolve delivery route, fee, and ETA using:
     * 1. Bidirectional Custom Route Overrides (if admin created a custom rate for A ⟷ B)
     * 2. Automatic 3-Tier Zonal Distance Fallbacks (Intra-LGA, Inter-LGA, Inter-State)
     */
    public static function resolveLane(
        int $originStateId,
        ?int $originLgaId,
        int $destStateId,
        ?int $destLgaId
    ): array {
        // CASE A: INTRA-STATE (Same State)
        if ($originStateId === $destStateId) {
            $isSameLga = ($originLgaId && $destLgaId && $originLgaId === $destLgaId);

            // 1. Check for Custom LGA-to-LGA Bidirectional Override
            if ($originLgaId && $destLgaId) {
                $customLane = static::enabled()
                    ->where(function ($q) use ($originLgaId, $destLgaId) {
                        // Direct direction A -> B
                        $q->where(function ($sub) use ($originLgaId, $destLgaId) {
                            $sub->where('origin_lga_id', $originLgaId)
                                ->where('destination_lga_id', $destLgaId);
                        })
                        // Or Reverse direction B -> A (if bidirectional)
                        ->orWhere(function ($sub) use ($originLgaId, $destLgaId) {
                            $sub->where('origin_lga_id', $destLgaId)
                                ->where('destination_lga_id', $originLgaId)
                                ->where('is_bidirectional', true);
                        });
                    })
                    ->first();

                if ($customLane) {
                    return [
                        'lane' => $customLane,
                        'fee' => (float) $customLane->delivery_fee,
                        'eta' => $customLane->estimated_delivery_time ?? ($isSameLga ? '2-4 hours' : 'Same day / 24 hours'),
                        'lane_type' => $isSameLga ? 'intra_lga_custom' : 'inter_lga_custom',
                        'zone_tier' => $isSameLga ? 1 : 2,
                        'is_interstate' => false,
                        'is_custom_override' => true,
                    ];
                }
            }

            // 2. No custom override: Apply Zonal Tier Rate
            if ($isSameLga) {
                // TIER 1: Intra-LGA (Municipal same-city bike delivery)
                $fee = (float) (getWebConfig(name: 'zone_intra_lga_fee') ?? 1000.00);
                $eta = (string) (getWebConfig(name: 'zone_intra_lga_eta') ?? '2-4 hours');
                return [
                    'lane' => null,
                    'fee' => $fee,
                    'eta' => $eta,
                    'lane_type' => 'zone_intra_lga',
                    'zone_tier' => 1,
                    'is_interstate' => false,
                    'is_custom_override' => false,
                ];
            } else {
                // TIER 2: Inter-LGA (Regional courier across LGAs within the same state)
                $fee = (float) (getWebConfig(name: 'zone_inter_lga_fee') ?? 2500.00);
                $eta = (string) (getWebConfig(name: 'zone_inter_lga_eta') ?? 'Same day / 24 hours');
                return [
                    'lane' => null,
                    'fee' => $fee,
                    'eta' => $eta,
                    'lane_type' => 'zone_inter_lga',
                    'zone_tier' => 2,
                    'is_interstate' => false,
                    'is_custom_override' => false,
                ];
            }
        }

        // CASE B: INTER-STATE (Different States)
        // 1. Check for Custom State-to-State Bidirectional Corridor
        $customStateLane = static::enabled()
            ->where(function ($q) use ($originStateId, $destStateId) {
                // Direct StateA -> StateB
                $q->where(function ($sub) use ($originStateId, $destStateId) {
                    $sub->where('origin_state_id', $originStateId)
                        ->where('destination_state_id', $destStateId);
                })
                // Or Reverse StateB -> StateA (if bidirectional)
                ->orWhere(function ($sub) use ($originStateId, $destStateId) {
                    $sub->where('origin_state_id', $destStateId)
                        ->where('destination_state_id', $originStateId)
                        ->where('is_bidirectional', true);
                });
            })
            ->where(function ($q) {
                $q->whereNull('origin_lga_id')->orWhere('origin_lga_id', 0);
            })
            ->where(function ($q) {
                $q->whereNull('destination_lga_id')->orWhere('destination_lga_id', 0);
            })
            ->first();

        if ($customStateLane) {
            return [
                'lane' => $customStateLane,
                'fee' => (float) $customStateLane->delivery_fee,
                'eta' => $customStateLane->estimated_delivery_time ?? '2-4 business days',
                'lane_type' => 'inter_state_custom',
                'zone_tier' => 3,
                'is_interstate' => true,
                'is_custom_override' => true,
            ];
        }

        // 2. TIER 3: Inter-State Zonal Default (National Freight Transit)
        $fee = (float) (getWebConfig(name: 'zone_inter_state_fee') ?? 4500.00);
        $eta = (string) (getWebConfig(name: 'zone_inter_state_eta') ?? '2-4 business days');
        return [
            'lane' => null,
            'fee' => $fee,
            'eta' => $eta,
            'lane_type' => 'zone_inter_state',
            'zone_tier' => 3,
            'is_interstate' => true,
            'is_custom_override' => false,
        ];
    }

    // ==========================================
    // Backward-Compatible Static Helpers
    // ==========================================

    /**
     * Find enabled delivery lane between two LGAs (bidirectional aware)
     */
    public static function findLane(int $originLgaId, int $destinationLgaId): ?self
    {
        return static::enabled()
            ->where(function ($q) use ($originLgaId, $destinationLgaId) {
                $q->where(function ($sub) use ($originLgaId, $destinationLgaId) {
                    $sub->where('origin_lga_id', $originLgaId)
                        ->where('destination_lga_id', $destinationLgaId);
                })->orWhere(function ($sub) use ($originLgaId, $destinationLgaId) {
                    $sub->where('origin_lga_id', $destinationLgaId)
                        ->where('destination_lga_id', $originLgaId)
                        ->where('is_bidirectional', true);
                });
            })
            ->first();
    }

    /**
     * Check if a delivery lane is available and enabled.
     * Under the 3-Tier Zonal Distance Pricing model, 100% of Nigerian LGAs are serviceable nationwide.
     */
    public static function isLaneAvailable(int $originLgaId, int $destinationLgaId): bool
    {
        return true;
    }

    /**
     * Get authoritative delivery fee for an LGA pair.
     * Resolves custom bidirectional overrides first, then falls back to Tier 1, 2, or 3 zonal rates.
     */
    public static function getDeliveryFee(int $originLgaId, int $destinationLgaId): ?float
    {
        $lane = static::findLane($originLgaId, $destinationLgaId);
        if ($lane) {
            return (float) $lane->delivery_fee;
        }

        $originLga = Lga::find($originLgaId);
        $destLga = Lga::find($destinationLgaId);

        if ($originLga && $destLga) {
            $resolved = static::resolveLane($originLga->state_id, $originLgaId, $destLga->state_id, $destinationLgaId);
            return (float) $resolved['fee'];
        }

        return (float) (getWebConfig(name: 'zone_intra_lga_fee') ?? 1000.00);
    }
}

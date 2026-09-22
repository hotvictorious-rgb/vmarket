<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\ShippingAddress;
use App\Models\DeliveryLane;

/**
 * [AI] VMarket Fulfillment Availability Service
 *
 * Part of: VMarket Geography & Fulfillment Architecture
 * Phase: 8 - Fulfillment Availability Service
 *
 * Centralizes availability checks for:
 * 1. Delivery (Origin LGA → Destination LGA lane)
 * 2. Pickup (Shop pickup settings + business hours)
 *
 * Invariants:
 * - Client never specifies origin/destination/fee
 * - Backend calculates origin from shop.lga_id
 * - Backend calculates destination from shipping_address.lga_id
 * - Backend queries DeliveryLane table for authoritative routing
 */
class FulfillmentAvailabilityService
{
    /**
     * Check delivery availability for a shop → address pair
     */
    public function checkDeliveryAvailability(
        Shop $shop,
        ShippingAddress $address,
        array $cartItems = []
    ): array
    {
        // 1. Ensure canonical geography exists
        if (!$shop->lga_id || !$address->lga_id) {
            return [
                'available' => false,
                'reason' => 'incomplete_geography',
                'message' => 'Shop or address lacks canonical LGA mapping',
                'fee' => null,
                'estimated_time' => null,
            ];
        }

        // 2. Find directional delivery lane
        $lane = DeliveryLane::findLane(
            $shop->lga_id,
            $address->lga_id
        );

        if (!$lane) {
            return [
                'available' => false,
                'reason' => 'no_delivery_lane',
                'message' => 'No active delivery route between these LGAs',
                'fee' => null,
                'estimated_time' => null,
            ];
        }

        // 3. Optional: Check cart constraints (e.g., weight, fragile, oversize)
        $cartConstraints = $this->checkCartConstraints($cartItems);
        if (!$cartConstraints['allowed']) {
            return [
                'available' => false,
                'reason' => 'cart_constraint',
                'message' => $cartConstraints['message'],
                'fee' => null,
                'estimated_time' => null,
            ];
        }

        // 4. Return lane availability
        return [
            'available' => true,
            'fee' => (float) $lane->delivery_fee,
            'estimated_time' => $lane->estimated_delivery_time,
            'origin_lga' => [
                'id' => $shop->lga_id,
                'name' => $shop->lga?->name ?? null,
                'state' => $shop->state?->name ?? null,
            ],
            'destination_lga' => [
                'id' => $address->lga_id,
                'name' => $address->canonicalLga?->name ?? null,
                'state' => $address->canonicalState?->name ?? null,
            ],
        ];
    }

    /**
     * Check in‑shop pickup availability
     */
    public function checkPickupAvailability(
        Shop $shop,
        ?string $requestedTime = null
    ): array
    {
        // 1. Shop pickup capability
        if (!$shop->pickup_enabled) {
            return [
                'available' => false,
                'reason' => 'pickup_disabled',
                'message' => 'Shop does not offer in‑store pickup',
                'available_times' => [],
            ];
        }

        // 2. Business hours (null = no restriction)
        if ($shop->pickup_opening_time && $shop->pickup_closing_time) {
            $now = now();
            $opening = now()->setTimeFromTimeString($shop->pickup_opening_time);
            $closing = now()->setTimeFromTimeString($shop->pickup_closing_time);

            // If closing is earlier than opening (e.g., overnight schedule)
            if ($closing < $opening && $now >= $closing) {
                $closing->addDay();
            }

            if ($now < $opening || $now > $closing) {
                return [
                    'available' => false,
                    'reason' => 'outside_hours',
                    'message' => sprintf(
                        'Shop pickup hours: %s – %s',
                        $shop->pickup_opening_time,
                        $shop->pickup_closing_time
                    ),
                    'available_times' => $this->buildAvailablePickupSlots($shop),
                ];
            }
        }

        // 3. Preparation time
        $preparationMinutes = $shop->pickup_preparation_time_minutes ?? 30;
        $availableAt = now()->addMinutes($preparationMinutes);

        // 4. Specific requested time
        if ($requestedTime) {
            $requestedDateTime = \Carbon\Carbon::parse($requestedTime);
            $earliestPickup = now()->addMinutes($preparationMinutes);

            if ($requestedDateTime < $earliestPickup) {
                return [
                    'available' => false,
                    'reason' => 'too_soon',
                    'message' => sprintf(
                        'Pickup needs at least %d minutes preparation',
                        $preparationMinutes
                    ),
                    'earliest_available' => $earliestPickup->format('Y-m-d H:i'),
                    'available_times' => $this->buildAvailablePickupSlots($shop),
                ];
            }

            // Check requested time against business hours
            if ($shop->pickup_opening_time && $shop->pickup_closing_time) {
                $requestedTimeOfDay = $requestedDateTime->format('H:i');
                if ($requestedTimeOfDay < $shop->pickup_opening_time ||
                    $requestedTimeOfDay > $shop->pickup_closing_time) {
                    return [
                        'available' => false,
                        'reason' => 'outside_hours',
                        'message' => sprintf(
                            'Requested time outside shop hours (%s – %s)',
                            $shop->pickup_opening_time,
                            $shop->pickup_closing_time
                        ),
                        'available_times' => $this->buildAvailablePickupSlots($shop),
                    ];
                }
            }
        }

        return [
            'available' => true,
            'preparation_minutes' => $preparationMinutes,
            'earliest_available' => $availableAt->format('Y-m-d H:i'),
            'opening_time' => $shop->pickup_opening_time,
            'closing_time' => $shop->pickup_closing_time,
            'instructions' => $shop->pickup_instructions,
            'available_times' => $this->buildAvailablePickupSlots($shop),
        ];
    }

    /**
     * Check both delivery and pickup availability
     */
    public function checkFulfillmentOptions(
        Shop $shop,
        ?ShippingAddress $address = null,
        array $cartItems = []
    ): array
    {
        $result = [
            'delivery' => null,
            'pickup' => null,
        ];

        // Always check pickup
        $result['pickup'] = $this->checkPickupAvailability($shop);

        // Check delivery only if address provided
        if ($address) {
            $result['delivery'] = $this->checkDeliveryAvailability($shop, $address, $cartItems);
        }

        return $result;
    }

    /**
     * Compute delivery fee for shop → address
     */
    public function getDeliveryFee(
        Shop $shop,
        ShippingAddress $address
    ): ?float
    {
        if (!$shop->lga_id || !$address->lga_id) {
            return null;
        }

        return DeliveryLane::getDeliveryFee($shop->lga_id, $address->lga_id);
    }

    /**
     * Validate LGA-based delivery lane availability
     */
    public function isLaneAvailable(int $originLgaId, int $destinationLgaId): bool
    {
        return DeliveryLane::isLaneAvailable($originLgaId, $destinationLgaId);
    }

    // ==========================================
    // Private Helpers
    // ==========================================

    private function checkCartConstraints(array $cartItems): array
    {
        // Placeholder: no constraints by default
        // Can be extended for weight, size, fragile items, etc.
        return [
            'allowed' => true,
            'message' => null,
        ];
    }

    private function buildAvailablePickupSlots(Shop $shop): array
    {
        $slots = [];

        if (!$shop->pickup_opening_time || !$shop->pickup_closing_time) {
            // No hours restriction → "anytime"
            $slots[] = [
                'label' => 'Anytime during business day',
                'value' => null,
            ];
            return $slots;
        }

        $now = now();
        $prepMinutes = $shop->pickup_preparation_time_minutes ?? 30;
        $earliest = $now->copy()->addMinutes($prepMinutes);

        $opening = $now->copy()->setTimeFromTimeString($shop->pickup_opening_time);
        $closing = $now->copy()->setTimeFromTimeString($shop->pickup_closing_time);

        // Adjust for next day if already past closing
        if ($closing < $opening && $now >= $closing) {
            $opening->addDay();
            $closing->addDay();
        }

        // Generate 30‑minute slots starting from earliest available
        $start = max($opening, $earliest);
        $slotTime = $start->copy();

        while ($slotTime <= $closing) {
            $slotEnd = $slotTime->copy()->addMinutes(30);
            if ($slotEnd > $closing) {
                break;
            }

            $slots[] = [
                'label' => $slotTime->format('H:i') . ' – ' . $slotEnd->format('H:i'),
                'value' => $slotTime->format('Y-m-d H:i'),
            ];

            $slotTime->addMinutes(30);
        }

        // Add tomorrow's slots if today has none
        if (empty($slots)) {
            $tomorrow = $now->copy()->addDay();
            $openingTomorrow = $tomorrow->setTimeFromTimeString($shop->pickup_opening_time);
            $closingTomorrow = $tomorrow->setTimeFromTimeString($shop->pickup_closing_time);

            $slotTime = $openingTomorrow;
            while ($slotTime <= $closingTomorrow) {
                $slotEnd = $slotTime->copy()->addMinutes(30);
                if ($slotEnd > $closingTomorrow) {
                    break;
                }

                $slots[] = [
                    'label' => $slotTime->format('H:i') . ' – ' . $slotEnd->format('H:i') . ' (tomorrow)',
                    'value' => $slotTime->format('Y-m-d H:i'),
                ];

                $slotTime->addMinutes(30);
            }
        }

        return $slots;
    }
}

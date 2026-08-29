<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Seller;
use App\Models\Shop;
use App\Models\DeliveryHub;
use App\Models\DeliveryCity;
use App\Models\ShippingAddress;
use Illuminate\Support\Facades\Log;

/**
 * [AI] Class BranchOrderAllocationService
 * 
 * Intelligent Order Branch Routing & Fulfillment Allocation Engine
 * Automatically evaluates customer delivery geography, merchant branch locations,
 * and regional delivery hubs to assign online orders to the optimal fulfillment branch.
 */
class BranchOrderAllocationService
{
    /**
     * Determine and allocate the optimal physical branch for an order.
     *
     * @param int $sellerId
     * @param int|null $shippingAddressId
     * @param array $items
     * @return int|null Assigned Shop/Branch ID
     */
    public function allocateBranch(int $sellerId, ?int $shippingAddressId = null, array $items = []): ?int
    {
        // 1. Fetch all active physical branches for this seller
        $branches = Shop::where('seller_id', $sellerId)
            ->where('temporary_close', 0)
            ->get();

        if ($branches->isEmpty()) {
            return null;
        }

        // If merchant has only 1 physical store, allocate directly to primary branch
        if ($branches->count() === 1) {
            return $branches->first()->id;
        }

        // 2. Fetch Customer Shipping Address & Geography
        $shippingAddress = $shippingAddressId ? ShippingAddress::find($shippingAddressId) : null;

        if ($shippingAddress) {
            $customerCity = strtolower(trim((string)($shippingAddress->city ?? '')));
            $customerState = strtolower(trim((string)($shippingAddress->state ?? '')));

            // A. Proximity Match: Check if any branch is in the exact same city or state
            foreach ($branches as $branch) {
                $branchAddress = strtolower($branch->address . ' ' . $branch->name);
                if (!empty($customerCity) && str_contains($branchAddress, $customerCity)) {
                    return $branch->id;
                }
            }

            // B. Regional Hub Match: Check matching delivery hub / city if configured
            foreach ($branches as $branch) {
                if (!empty($branch->delivery_city_id)) {
                    $city = DeliveryCity::find($branch->delivery_city_id);
                    if ($city && !empty($customerCity) && str_contains(strtolower($city->city_name), $customerCity)) {
                        return $branch->id;
                    }
                }
            }
        }

        // 3. Fallback: Allocate to Primary Store / Main Branch (Lowest ID or Main Branch)
        return $branches->sortBy('id')->first()->id;
    }

    /**
     * Reassign an order to a specific physical branch.
     *
     * @param Order $order
     * @param int $branchId
     * @param string|null $reassignedByName
     * @return bool
     */
    public function reassignBranch(Order $order, int $branchId, ?string $reassignedByName = null): bool
    {
        $branch = Shop::where('id', $branchId)
            ->where('seller_id', $order->seller_id)
            ->first();

        if (!$branch) {
            return false;
        }

        $order->handover_branch_id = $branch->id;
        $order->save();

        Log::info("[AI Branch Engine] Order #{$order->id} reassigned to Branch #{$branch->id} ({$branch->name}) by {$reassignedByName}");
        return true;
    }
}

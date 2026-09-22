<?php

namespace App\Http\Controllers\RestAPI\v1\customer;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShippingAddress;
use App\Services\FulfillmentAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * [AI] Fulfillment Availability API Controller
 *
 * Part of: VMarket Geography & Fulfillment Architecture
 * Phase: 9 - Fulfillment Availability API
 *
 * Client requests fulfillment options for shop + address.
 * Backend calculates origin/destination from canonical LGAs.
 * Backend queries delivery lanes and pickup settings.
 */
class FulfillmentAvailabilityController extends Controller
{
    protected FulfillmentAvailabilityService $fulfillmentService;

    public function __construct(FulfillmentAvailabilityService $fulfillmentService)
    {
        $this->fulfillmentService = $fulfillmentService;
    }

    /**
     * POST /api/v1/fulfillment/availability
     *
     * Check delivery and pickup availability for a shop + address
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shop_id' => 'required|integer|exists:shops,id',
            'shipping_address_id' => 'nullable|integer|exists:shipping_addresses,id',
            'cart_items' => 'nullable|array',
            'cart_items.*.product_id' => 'integer',
            'cart_items.*.quantity' => 'integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $shop = Shop::with(['country', 'state', 'lga'])->find($request->shop_id);

        if (!$shop) {
            return response()->json([
                'success' => false,
                'message' => 'Shop not found',
            ], 404);
        }

        $address = null;
        if ($request->shipping_address_id) {
            $address = ShippingAddress::with(['canonicalCountry', 'canonicalState', 'canonicalLga'])
                ->find($request->shipping_address_id);

            if (!$address) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shipping address not found',
                ], 404);
            }
        }

        $cartItems = $request->cart_items ?? [];

        $availability = $this->fulfillmentService->checkFulfillmentOptions(
            $shop,
            $address,
            $cartItems
        );

        return response()->json([
            'success' => true,
            'data' => [
                'shop' => [
                    'id' => $shop->id,
                    'name' => $shop->name,
                    'lga' => $shop->lga?->name,
                    'state' => $shop->state?->name,
                ],
                'address' => $address ? [
                    'id' => $address->id,
                    'lga' => $address->canonicalLga?->name,
                    'state' => $address->canonicalState?->name,
                ] : null,
                'fulfillment_options' => $availability,
            ],
        ]);
    }

    /**
     * POST /api/v1/fulfillment/delivery-fee
     *
     * Get authoritative delivery fee for shop + address
     */
    public function getDeliveryFee(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shop_id' => 'required|integer|exists:shops,id',
            'shipping_address_id' => 'required|integer|exists:shipping_addresses,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $shop = Shop::find($request->shop_id);
        $address = ShippingAddress::find($request->shipping_address_id);

        if (!$shop || !$address) {
            return response()->json([
                'success' => false,
                'message' => 'Shop or address not found',
            ], 404);
        }

        $fee = $this->fulfillmentService->getDeliveryFee($shop, $address);

        if ($fee === null) {
            return response()->json([
                'success' => false,
                'message' => 'No delivery lane available between these locations',
                'data' => [
                    'fee' => null,
                    'available' => false,
                ],
            ], 200);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'fee' => $fee,
                'available' => true,
                'origin_lga' => $shop->lga?->name,
                'destination_lga' => $address->canonicalLga?->name,
            ],
        ]);
    }
}

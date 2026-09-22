<?php

namespace App\Http\Controllers\RestAPI\v1\customer;

use App\Exceptions\IdempotencyConflictException;
use App\Exceptions\InvalidCartException;
use App\Exceptions\InvalidPaymentStateException;
use App\Exceptions\PaymentInitializationException;
use App\Exceptions\ProductUnavailableException;
use App\Http\Controllers\Controller;
use App\Services\DeliveryCheckoutIntentService;
use App\Services\DeliveryPaymentInitializationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * [AI] DeliveryCheckoutIntentController
 *
 * Canonical REST controller for the two-phase delivery checkout flow:
 *   Phase 1: POST /api/v1/checkout/intent          → CreateCheckoutIntent
 *   Phase 2: POST /api/v1/checkout/intent/{id}/pay → InitializePayment → Paystack authorization_url
 *
 * Consumers: Customer Mobile App (auth:api) — authenticated customers only.
 * Guests are not supported in the canonical V1 payment engine.
 */
class DeliveryCheckoutIntentController extends Controller
{
    public function __construct(
        protected DeliveryCheckoutIntentService $intentService,
        protected DeliveryPaymentInitializationService $paymentService
    ) {}

    /**
     * Phase 1: Create or replay a frozen CheckoutIntent for a delivery order.
     *
     * Required fields:
     *   - address_id (int): Authenticated customer's shipping address ID
     *   - idempotency_key (string 8-64): Client-generated unique key for replay safety
     *
     * Optional fields:
     *   - billing_address_id (int)
     *   - use_cashback (bool): Redeem customer's Victorious Points as a discount
     *   - cart_item_ids (array of int): Specific cart items to include (defaults to all checked)
     *
     * Decommissioned fields (accepted but ignored for backwards compat):
     *   - coupon_code, coupon_discount: Coupons removed from V1 checkout.
     *
     * [AI] Clients: Customer Mobile App
     */
    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'address_id'         => 'required|integer|min:1',
            'idempotency_key'    => 'required|string|min:8|max:64|regex:/^[A-Za-z0-9_\-\:]{8,64}$/',
            'billing_address_id' => 'nullable|integer|min:1',
            'use_cashback'       => 'nullable|boolean',
            'cart_item_ids'      => 'nullable|array',
            'cart_item_ids.*'    => 'integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        $customer = auth('api')->user();
        if (!$customer) {
            return response()->json([
                'errors' => ['Authentication required. Guest checkout is not supported in V1.'],
            ], 401);
        }

        try {
            $intent = $this->intentService->createCheckoutIntent(
                customer: $customer,
                idempotencyKey: $request->input('idempotency_key'),
                shippingAddress: (int) $request->input('address_id'),
                billingAddress: $request->input('billing_address_id') ? (int) $request->input('billing_address_id') : null,
                useCashback: filter_var($request->input('use_cashback', false), FILTER_VALIDATE_BOOLEAN),
                cartItemIds: $request->input('cart_item_ids'),
            );

            return response()->json([
                'intent_id'      => $intent->id,
                'order_group_id' => $intent->order_group_id,
                'total_amount'   => $intent->total_amount,
                'currency'       => 'NGN',
                'status'         => $intent->status,
                'expires_at'     => $intent->expires_at,
                'fingerprint'    => $intent->cart_fingerprint,
            ], 200);

        } catch (IdempotencyConflictException $e) {
            return response()->json(['errors' => [$e->getMessage()]], 409);
        } catch (InvalidCartException | ProductUnavailableException $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        } catch (\Exception $e) {
            Log::error('[AI] DeliveryCheckoutIntentController::create exception', [
                'customer_id' => $customer->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['errors' => ['Unable to create checkout agreement. Please try again.']], 500);
        }
    }

    /**
     * Phase 2: Initialize a Paystack payment attempt for an existing CheckoutIntent.
     *
     * Required route param:
     *   - order_group_id (string): The CheckoutIntent's order_group_id
     *
     * Returns:
     *   - authorization_url: Redirect the customer to this Paystack-hosted page
     *   - gateway_reference: The VM-{uuid} reference for tracking
     *   - payment_request_id: The PaymentRequest UUID
     *   - is_replayed: true if an existing pending attempt was recovered
     *
     * [AI] Clients: Customer Mobile App
     */
    public function initializePayment(Request $request, string $orderGroupId): JsonResponse
    {
        $customer = auth('api')->user();
        if (!$customer) {
            return response()->json([
                'errors' => ['Authentication required.'],
            ], 401);
        }

        try {
            $result = $this->paymentService->initializePayment(
                customer: $customer,
                intentInput: $orderGroupId,
                ttlMinutes: 30,
            );

            $status = $result['status'] ?? 'unknown';

            // Success: new Paystack initialization
            if ($status === 'success') {
                return response()->json([
                    'status'             => 'success',
                    'authorization_url'  => $result['authorization_url'],
                    'gateway_reference'  => $result['gateway_reference'],
                    'payment_request_id' => $result['payment_request']->id ?? null,
                    'is_replayed'        => false,
                    'is_recovered'       => $result['is_recovered'] ?? false,
                ], 200);
            }

            // Replay: Paystack transaction already exists on the gateway for this attempt
            if ($status === 'pending_on_gateway') {
                $additionalData = json_decode($result['payment_request']->additional_data ?? '{}', true);
                return response()->json([
                    'status'             => 'pending_on_gateway',
                    'authorization_url'  => $additionalData['authorization_url'] ?? null,
                    'gateway_reference'  => $result['gateway_reference'],
                    'payment_request_id' => $result['payment_request']->id ?? null,
                    'is_replayed'        => true,
                ], 200);
            }

            // Ambiguous transport failure: client should retry
            if ($status === 'ambiguous_transport') {
                return response()->json([
                    'status'  => 'ambiguous_transport',
                    'message' => 'Payment gateway communication is temporarily unavailable. Please retry.',
                    'gateway_reference' => $result['gateway_reference'] ?? null,
                ], 503);
            }

            return response()->json(['errors' => ['Payment initialization failed. Please try again.']], 502);

        } catch (InvalidCartException $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        } catch (InvalidPaymentStateException $e) {
            return response()->json(['errors' => [$e->getMessage()]], 409);
        } catch (PaymentInitializationException $e) {
            return response()->json(['errors' => [$e->getMessage()]], 502);
        } catch (\Exception $e) {
            Log::error('[AI] DeliveryCheckoutIntentController::initializePayment exception', [
                'customer_id'    => $customer->id ?? null,
                'order_group_id' => $orderGroupId,
                'error'          => $e->getMessage(),
            ]);
            return response()->json(['errors' => ['Payment initialization failed. Please try again.']], 500);
        }
    }
}

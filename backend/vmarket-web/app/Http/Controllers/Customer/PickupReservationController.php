<?php

namespace App\Http\Controllers\Customer;

use App\Exceptions\IdempotencyConflictException;
use App\Http\Controllers\Controller;
use App\Services\PickupReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * [AI] Controller PickupReservationController (Customer Portal / API)
 *
 * Handles customer pre-payment pickup reservation requests.
 * Invariants:
 * - Scoped strictly to authenticated customer (Zero IDOR).
 * - Split cart by seller + shop.
 * - Idempotent re-execution via parent idempotency key.
 * - Does NOT alter cart, decrement stock, or initiate payment.
 */
use App\Exceptions\InvalidPaymentStateException;
use App\Exceptions\InvalidCartException;
use App\Exceptions\PaymentInitializationException;
use App\Services\PickupPaymentInitializationService;
use App\Models\PickupReservation;
use App\Models\PaymentRequest;
use App\Models\Order;
use App\Models\CashbackRedemption;
use App\Models\CustomerCashbackLedger;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PickupReservationController extends Controller
{
    public function __construct(
        protected PickupReservationService $reservationService,
        protected ?PickupPaymentInitializationService $paymentInitService = null
    ) {
        $this->paymentInitService = $paymentInitService ?: app(PickupPaymentInitializationService::class);
    }

    /**
     * Creates or replays pickup reservations from customer cart.
     */
    public function create(Request $request): JsonResponse
    {
        $customerId = auth('customer')->id() ?? (int) ($request->user('customer')?->id ?? 0);
        if (!$customerId && auth('api')->check()) {
            $customerId = (int) auth('api')->id();
        }

        if (!$customerId) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated customer. Pickup reservations require authentication.',
            ], 401);
        }

        $validated = $request->validate([
            'idempotency_key' => 'required|string|max:64',
            'cart_ids' => 'nullable|array',
            'cart_ids.*' => 'integer',
            'checked_only' => 'nullable|boolean',
        ]);

        try {
            $reservations = $this->reservationService->createReservationsFromCart(
                $customerId,
                $validated['idempotency_key'],
                $validated
            );

            // [AI] Build authoritative per-reservation response payload.
            // The app MUST use these fields — not local cart data — as the authoritative source.
            $cashbackRatePercent = (float) CustomerCashbackLedger::configuredEarnRate();
            $exchangeRate        = (float) (getWebConfig(name: 'loyalty_point_exchange_rate') ?? 1.0);
            $loyaltyStatus       = (int)   (getWebConfig(name: 'loyalty_point_status') ?? 0);
            if ((int)(getWebConfig(name: 'loyalty_point_for_each_order') ?? 1) !== 1) { $loyaltyStatus = 0; }

            $mappedReservations = array_map(function ($reservation) use ($cashbackRatePercent, $exchangeRate, $loyaltyStatus) {
                $snapshot = is_array($reservation->reservation_items)
                    ? $reservation->reservation_items
                    : json_decode($reservation->reservation_items ?? '{}', true);

                // Extract shop snapshot from immutable reservation_items JSON
                $shopSnapshot = [
                    'shop_id'      => $reservation->shop_id,
                    'shop_name'    => $snapshot['shop']['name'] ?? null,
                    'shop_address' => $snapshot['shop']['address'] ?? null,
                ];

                // [AI] Informational merchandise estimate; receipt and actual new funding govern issuance.
                $estimatedCashbackNaira = '0.00';
                if ($loyaltyStatus === 1 && $cashbackRatePercent > 0 && $exchangeRate > 0) {
                    $estimatedCashbackNaira = bcmul(
                        bcadd((string) ($snapshot['subtotal'] ?? '0'), '0', 2),
                        bcdiv((string) $cashbackRatePercent, '100', 6),
                        2
                    );
                }

                return [
                    'id'               => $reservation->id,
                    'reservation_code' => $reservation->reservation_code,
                    'status'           => $reservation->status,
                    'expires_at'       => $reservation->expires_at,
                    'total_amount'     => $reservation->total_amount,
                    'currency'         => $reservation->currency ?? 'NGN',
                    'seller_id'        => $reservation->seller_id,
                    'shop_snapshot'    => $shopSnapshot,
                    'items'            => $snapshot['items'] ?? [],
                    // [AI] Estimate only; confirmed receipt starts the reward lifecycle.
                    'cashback_to_earn' => [
                        'award_trigger'    => 'confirmed_receipt',
                        'award_status'     => 'estimate',
                        'percent'          => $loyaltyStatus === 1 ? $cashbackRatePercent : 0.0,
                        'estimated_naira'  => $estimatedCashbackNaira,
                    ],
                    'order_id'         => $reservation->order_id,
                    'created_at'       => $reservation->created_at,
                ];
            }, $reservations);

            return response()->json([
                'status'             => true,
                'message'            => 'Pickup reservation(s) created successfully.',
                'reservations_count' => count($reservations),
                'reservations'       => $mappedReservations,
            ], 201);

        } catch (IdempotencyConflictException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 409);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\DomainException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Lists active and historical pickup reservations for the authenticated customer.
     */
    public function index(Request $request): JsonResponse|\Illuminate\Contracts\View\View
    {
        $customerId = auth('customer')->id() ?? (int) ($request->user('customer')?->id ?? 0);
        if (!$customerId && auth('api')->check()) {
            $customerId = (int) auth('api')->id();
        }

        if (!$customerId) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $reservations = $this->reservationService->listReservationsForCustomer($customerId);
        if (!$request->expectsJson() && !$request->is('api/*')) {
            return view('theme-views.users-profile.pickup-reservations', ['reservations' => $reservations]);
        }

        return response()->json([
            'status' => true,
            'reservations' => $reservations,
        ]);
    }

    /**
     * Shows single reservation details for the authenticated customer.
     */
    public function show(Request $request, string $reservationCode): JsonResponse
    {
        $customerId = auth('customer')->id() ?? (int) ($request->user('customer')?->id ?? 0);
        if (!$customerId && auth('api')->check()) {
            $customerId = (int) auth('api')->id();
        }

        if (!$customerId) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $reservation = $this->reservationService->getReservationForCustomer($customerId, $reservationCode);

        if (!$reservation) {
            return response()->json([
                'status' => false,
                'message' => 'Reservation not found or unauthorized.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'reservation' => $reservation,
        ]);
    }

    /**
     * Initiates Paystack payment for an inspected and accepted pickup reservation.
     */
    public function pay(Request $request, string $reservationCode): JsonResponse
    {
        $customerId = auth('customer')->id() ?? (int) ($request->user('customer')?->id ?? 0);
        if (!$customerId && auth('api')->check()) {
            $customerId = (int) auth('api')->id();
        }

        if (!$customerId) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $ttlMinutes = (int) ($request->input('ttl_minutes', 30));
        $callbackUrl = $request->input('callback_url');
        $useCashback = (bool) ($request->input('use_cashback', false));
        $validated = $request->validate(['quote_token' => 'required|string|size:64', 'use_cashback' => 'nullable|boolean']);

        try {
            $result = $this->paymentInitService->initializePayment(
                $customerId,
                $reservationCode,
                $useCashback,
                $ttlMinutes,
                $callbackUrl,
                $validated['quote_token']
            );

            return response()->json(array_merge($result, [
                'status' => true,
                'init_status' => $result['status'] ?? 'success',
            ]), 200);
        } catch (InvalidPaymentStateException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 409);
        } catch (InvalidCartException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (PaymentInitializationException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 502);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Payment initialization failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /** [AI] Frozen server funding review for customer storefront and mobile. */
    public function quote(Request $request, string $reservationCode): JsonResponse
    {
        $customerId = (int)(auth('customer')->id() ?? auth('api')->id() ?? 0);
        if (!$customerId) { return response()->json(['status' => false], 401); }
        $request->validate(['use_cashback' => 'nullable|boolean']);
        try {
            return response()->json($this->paymentInitService->quote($customerId, $reservationCode, $request->boolean('use_cashback')));
        } catch (InvalidPaymentStateException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 409);
        }
    }

    /** [AI] Authoritative polling never equates a captured anomaly with fulfilled pickup. */
    public function status(Request $request, string $reservationCode): JsonResponse
    {
        $customerId = (int)(auth('customer')->id() ?? auth('api')->id() ?? 0);
        if (!$customerId) { return response()->json(['status' => false], 401); }
        $state = DB::transaction(function () use ($customerId, $reservationCode) {
            User::where('id', $customerId)->lockForUpdate()->first();
            $reservation = PickupReservation::where('customer_id', $customerId)->where('reservation_code', $reservationCode)->lockForUpdate()->first();
            if (!$reservation) { return null; }
            $payment = PaymentRequest::where('pickup_reservation_id', $reservation->id)->latest('created_at')->lockForUpdate()->first();
            $order = $reservation->order_id ? Order::find($reservation->order_id) : null;
            $canonicalOrder = $order && (int)$order->customer_id === $customerId && $order->order_type === 'pickup' && $order->order_group_id === 'pickup-'.$reservation->reservation_code;
            $paid = $reservation->status === 'order_placed' && $payment && (int)$payment->payer_id === $customerId && $payment->attempt_status === 'successful' && (int)$payment->is_paid === 1 && $canonicalOrder && $order->payment_status === 'paid' && in_array($order->order_status, ['confirmed', 'processing', 'out_for_delivery', 'delivered']);
            if (!$paid && $reservation->isExpired() && !in_array($reservation->status, ['order_placed', 'expired'])) {
                $reservation->update(['status' => 'expired', 'active_reservation_token' => null]);
                if ($payment && $payment->attempt_status === 'pending' && !(int)$payment->is_paid) {
                    $payment->update(['attempt_status' => 'expired', 'active_pickup_reservation_id' => null]);
                }
                CashbackRedemption::where('pickup_reservation_id', $reservation->id)->where('status', 'reserved')->update(['status' => 'released', 'released_at' => now()]);
            }
            $additional = $payment ? json_decode($payment->additional_data ?? '{}', true) : [];
            // [AI] A provider attempt label cannot certify fulfillment or an unsupported cancellation.
            $paymentStatus = $paid ? 'paid' : ($payment?->attempt_status ?? ($reservation->status === 'expired' ? 'expired' : 'unpaid'));
            if (!$paid && ($paymentStatus === 'successful' || $reservation->status === 'order_placed')) {
                $paymentStatus = $canonicalOrder && $order->payment_status === 'refunded' ? 'refunded' : 'reconciliation_required';
            }
            return ['status' => true, 'reservation_status' => $reservation->status,
                'payment_status' => $paymentStatus,
                'order_id' => $paid ? $order->id : null, 'pickup_verification_code' => $paid ? (string)$order->pickup_verification_code : null,
                'expires_at' => $reservation->expires_at, 'payment_request_id' => $payment?->id,
                'gateway_reference' => $payment?->gateway_reference,
                'authorization_url' => !$paid && $payment?->attempt_status === 'pending' ? ($additional['authorization_url'] ?? null) : null];
        });
        return response()->json($state ?? ['status' => false, 'message' => 'Reservation not found.'], $state ? 200 : 404);
    }
}

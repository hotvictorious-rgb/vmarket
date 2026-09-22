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
            $cashbackRatePercent = (float) (getWebConfig(name: 'loyalty_point_earn_rate_percent') ?? 5.0);
            $exchangeRate        = (float) (getWebConfig(name: 'loyalty_point_exchange_rate') ?? 1.0);
            $loyaltyStatus       = (int)   (getWebConfig(name: 'loyalty_point_status') ?? 0);

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

                // Compute estimated cashback (informational only — awarded at settlement)
                $estimatedCashbackNaira = '0.00';
                if ($loyaltyStatus === 1 && $cashbackRatePercent > 0 && $exchangeRate > 0) {
                    $estimatedCashbackNaira = bcmul(
                        bcadd((string) $reservation->total_amount, '0', 2),
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
                    // [AI] Cashback to earn when paying at store — app shows this as a promise
                    'cashback_to_earn' => [
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
    public function index(Request $request): JsonResponse
    {
        $customerId = auth('customer')->id() ?? (int) ($request->user('customer')?->id ?? 0);
        if (!$customerId && auth('api')->check()) {
            $customerId = (int) auth('api')->id();
        }

        if (!$customerId) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $reservations = $this->reservationService->listReservationsForCustomer($customerId);

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

        try {
            $result = $this->paymentInitService->initializePayment(
                $customerId,
                $reservationCode,
                $useCashback,
                $ttlMinutes,
                $callbackUrl
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
}

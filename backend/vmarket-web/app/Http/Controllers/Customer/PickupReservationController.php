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

            return response()->json([
                'status' => true,
                'message' => 'Pickup reservation(s) created successfully.',
                'reservations_count' => count($reservations),
                'reservations' => $reservations,
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

        try {
            $result = $this->paymentInitService->initializePayment(
                $customerId,
                $reservationCode,
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

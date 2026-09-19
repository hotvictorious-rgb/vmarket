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
class PickupReservationController extends Controller
{
    public function __construct(
        protected PickupReservationService $reservationService
    ) {
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
}

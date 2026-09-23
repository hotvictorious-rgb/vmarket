<?php

namespace App\Http\Controllers\Vendor\Order;

use App\Http\Controllers\Controller;
use App\Models\PickupReservation;
use App\Models\Shop;
use App\Services\PickupReservationService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * [AI] Controller PickupReservationController (Vendor Portal & Vendor App API)
 *
 * Handles physical inspection workflows for In-Shop Pickup Reservations:
 * 1. Display listing of In-Shop Pickup Reservations (Queue).
 * 2. Verify reservation code at merchant shop counter.
 * 3. Accept reservation after physical goods inspection (unlocks customer payment).
 * 4. Reject reservation if customer declines or physical stock is inadequate.
 *
 * Invariants:
 * - Scoped strictly to authenticated vendor and assigned shop location (Zero IDOR).
 * - Does NOT create PaymentRequest, Order, or decrement stock.
 * - Rejection does NOT touch customer cart or issue refunds (payment has not happened).
 */
class PickupReservationController extends Controller
{
    public function __construct(
        protected PickupReservationService $reservationService
    ) {
    }

    /**
     * Display a listing of In-Shop Pickup Reservations for the vendor's shop.
     */
    public function index(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        [$sellerId, $shopId] = $this->resolveVendorContext($request);
        if (!$sellerId) {
            Toastr::error(translate('unauthorized_vendor_access'));
            return redirect()->route('vendor.dashboard.index');
        }

        $status = $request->input('status', 'all');
        $searchValue = $request->input('searchValue');

        $query = PickupReservation::with(['customer', 'shop', 'order'])
            ->where('seller_id', $sellerId);

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('reservation_code', 'like', "%{$searchValue}%")
                  ->orWhereHas('customer', function ($cq) use ($searchValue) {
                      $cq->where('f_name', 'like', "%{$searchValue}%")
                         ->orWhere('l_name', 'like', "%{$searchValue}%");
                  });
            });
        }

        $reservations = $query->latest()->paginate(getPagination())->appends($request->query());

        $baseQuery = PickupReservation::where('seller_id', $sellerId);
        if ($shopId) {
            $baseQuery->where('shop_id', $shopId);
        }

        $statusCounts = [
            'all' => (clone $baseQuery)->count(),
            'pending_inspection' => (clone $baseQuery)->where('status', 'pending_inspection')->count(),
            'inspected_accepted' => (clone $baseQuery)->where('status', 'inspected_accepted')->count(),
            'inspected_rejected' => (clone $baseQuery)->where('status', 'inspected_rejected')->count(),
            'order_placed' => (clone $baseQuery)->where('status', 'order_placed')->count(),
            'expired' => (clone $baseQuery)->where('status', 'expired')->count(),
        ];

        return view('vendor-views.order.pickup-reservations.index', compact('reservations', 'status', 'searchValue', 'statusCounts'));
    }

    /**
     * Resolves authenticated vendor seller_id and shop_id.
     */
    protected function resolveVendorContext(Request $request): array
    {
        $sellerId = auth('seller')->id()
            ?? (is_array($request->seller) ? ($request->seller['id'] ?? null) : ($request->seller->id ?? null));

        if (!$sellerId && auth('api')->check()) {
            $sellerId = (int) auth('api')->id();
        }

        $shopId = $request->header('X-Branch-ID')
            ?? $request->input('shop_id')
            ?? ($request['employee_shop_id'] ?? null);

        if (!$shopId && $sellerId) {
            $shop = Shop::where('seller_id', $sellerId)->first();
            $shopId = $shop ? (int) $shop->id : null;
        }

        return [(int) $sellerId, $shopId ? (int) $shopId : null];
    }

    /**
     * Verifies reservation code at vendor store for physical inspection.
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'reservation_code' => 'required|string|max:32',
        ]);

        [$sellerId, $shopId] = $this->resolveVendorContext($request);
        if (!$sellerId) {
            return response()->json(['status' => false, 'message' => 'Unauthorized vendor access.'], 403);
        }

        $result = $this->reservationService->verifyReservationForVendor(
            trim($request->reservation_code),
            $sellerId,
            $shopId
        );

        return match ($result['status']) {
            'VERIFIED' => response()->json([
                'status' => true,
                'message' => 'Reservation code verified. Proceed with physical item inspection.',
                'data' => $result,
            ], 200),
            'NOT_FOUND' => response()->json([
                'status' => false,
                'message' => $result['message'],
            ], 404),
            'FORBIDDEN' => response()->json([
                'status' => false,
                'message' => $result['message'],
            ], 403),
            'EXPIRED' => response()->json([
                'status' => false,
                'message' => $result['message'],
            ], 410),
            default => response()->json([
                'status' => false,
                'message' => $result['message'] ?? 'Unable to verify reservation.',
            ], 400),
        };
    }

    /**
     * Accepts physical inspection for the reservation.
     */
    public function accept(Request $request): JsonResponse
    {
        $request->validate([
            'reservation_code' => 'required|string|max:32',
            'notes' => 'nullable|string|max:500',
        ]);

        [$sellerId, $shopId] = $this->resolveVendorContext($request);
        if (!$sellerId) {
            return response()->json(['status' => false, 'message' => 'Unauthorized vendor access.'], 403);
        }

        $result = $this->reservationService->acceptInspection(
            trim($request->reservation_code),
            $sellerId,
            $shopId,
            ['notes' => $request->notes ?? '']
        );

        return match ($result['status']) {
            'SUCCESS', 'ALREADY_ACCEPTED' => response()->json([
                'status' => true,
                'message' => $result['message'],
                'reservation' => $result['reservation'] ?? null,
            ], 200),
            'NOT_FOUND' => response()->json(['status' => false, 'message' => $result['message']], 404),
            'FORBIDDEN' => response()->json(['status' => false, 'message' => $result['message']], 403),
            'EXPIRED' => response()->json(['status' => false, 'message' => $result['message']], 410),
            'INVALID_TRANSITION' => response()->json(['status' => false, 'message' => $result['message']], 409),
            default => response()->json(['status' => false, 'message' => $result['message'] ?? 'Failed to accept.'], 400),
        };
    }

    /**
     * Rejects physical inspection for the reservation.
     */
    public function reject(Request $request): JsonResponse
    {
        $request->validate([
            'reservation_code' => 'required|string|max:32',
            'reason' => 'nullable|string|max:255',
        ]);

        [$sellerId, $shopId] = $this->resolveVendorContext($request);
        if (!$sellerId) {
            return response()->json(['status' => false, 'message' => 'Unauthorized vendor access.'], 403);
        }

        $result = $this->reservationService->rejectInspection(
            trim($request->reservation_code),
            $sellerId,
            $shopId,
            trim((string) ($request->reason ?? 'Customer declined or stock unavailable.'))
        );

        return match ($result['status']) {
            'SUCCESS', 'ALREADY_REJECTED' => response()->json([
                'status' => true,
                'message' => $result['message'],
                'reservation' => $result['reservation'] ?? null,
            ], 200),
            'NOT_FOUND' => response()->json(['status' => false, 'message' => $result['message']], 404),
            'FORBIDDEN' => response()->json(['status' => false, 'message' => $result['message']], 403),
            'EXPIRED' => response()->json(['status' => false, 'message' => $result['message']], 410),
            'INVALID_TRANSITION' => response()->json(['status' => false, 'message' => $result['message']], 409),
            default => response()->json(['status' => false, 'message' => $result['message'] ?? 'Failed to reject.'], 400),
        };
    }
}

<?php

namespace App\Http\Controllers\Admin\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PickupReservation;
use App\Utils\Helpers;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * [AI] Class PickupOrderController
 * Comprehensive administrative oversight for In-Shop Pickup reservations, inspection auditing, and OTP handover.
 * Spec Reference: Sections 14, 28, 29
 * Invariant: Pickup is an independent fulfillment path from delivery logistics. Inspection rejection remains distinct from cancellation.
 */
class PickupOrderController extends Controller
{
    /**
     * Display pickup reservations and orders list
     */
    public function index(Request $request): View|RedirectResponse
    {
        if (!Helpers::module_permission_check('pickup.manage') && !Helpers::module_permission_check('order_management')) {
            ToastMagic::error(translate('Access Denied: Permission required to access in-shop pickup management.'));
            return redirect()->route('admin.dashboard.index');
        }

        $status = $request->get('status', 'all');

        $query = PickupReservation::with(['customer', 'shop', 'product', 'order'])
            ->when($status !== 'all', function ($q) use ($status) {
                return $q->where('status', $status);
            });

        if ($request->has('searchValue') && !empty($request->searchValue)) {
            $search = $request->searchValue;
            $query->where(function ($q) use ($search) {
                $q->where('reservation_code', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('f_name', 'like', "%{$search}%")
                           ->orWhere('l_name', 'like', "%{$search}%")
                           ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('shop', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $reservations = $query->latest()->paginate(20);

        // Additional summary metrics
        $metrics = [
            'total_reservations' => PickupReservation::count(),
            'pending_inspection' => PickupReservation::where('status', 'pending_inspection')->count(),
            'inspected_accepted' => PickupReservation::where('status', 'inspected_accepted')->count(),
            'inspected_rejected' => PickupReservation::where('status', 'inspected_rejected')->count(),
            'completed' => PickupReservation::where('status', 'completed')->count(),
        ];

        return view('admin-views.order.pickup-list', compact('reservations', 'status', 'metrics'));
    }
}

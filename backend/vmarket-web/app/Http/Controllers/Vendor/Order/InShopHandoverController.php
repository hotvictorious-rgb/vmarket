<?php

namespace App\Http\Controllers\Vendor\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderHandoverLog;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * [AI] Class InShopHandoverController
 * Implements the Staff-Attributed Handshake Protocol for rider in-shop parcel pick-ups.
 */
class InShopHandoverController extends Controller
{
    public function verifyPickupOtp(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'order_id' => 'required|integer',
            'pickup_otp' => 'required|string|size:6',
        ]);

        $seller = auth('seller')->user();
        $sellerId = auth('seller')->id();

        $order = Order::where('id', $request->order_id)
            ->where('seller_id', $sellerId)
            ->firstOrFail();

        if (!$order->pickup_verification_code) {
            ToastMagic::error(translate('No_pickup_verification_code_assigned_to_this_order'));
            return back();
        }

        // Constant-time OTP comparison to prevent timing attacks
        if (!hash_equals((string)$order->pickup_verification_code, (string)$request->pickup_otp)) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => false,
                    'message' => translate('Invalid_6-digit_Secret_Pickup_OTP._Custody_transfer_rejected.'),
                ], 422);
            }
            ToastMagic::error(translate('Invalid_6-digit_Secret_Pickup_OTP._Custody_transfer_rejected.'));
            return back();
        }

        // Determine exact worker on duty (Vendor Employee vs Store Owner)
        $employeeData = session('vendor_employee_data');
        if (!empty($employeeData)) {
            $staffId = (int)($employeeData['id'] ?? $sellerId);
            $roleTitle = session('vendor_employee_role.name') ?? 'Staff';
            $staffName = ($employeeData['name'] ?? 'Worker') . " ({$roleTitle} - Staff #{$staffId})";
        } else {
            $staffId = (int)$sellerId;
            $staffName = ($seller->name ?? ($seller->f_name . ' ' . $seller->l_name)) . ' (Store Owner)';
        }

        $branchId = $seller->shop->id ?? ($order->handover_branch_id ?? null);

        DB::transaction(function () use ($order, $sellerId, $staffId, $staffName, $branchId, $request) {
            $order->order_status = 'out_for_delivery';
            $order->handed_over_by_id = $staffId;
            $order->handed_over_by_name = $staffName;
            $order->handed_over_at = now();
            $order->handover_branch_id = $branchId;
            $order->save();

            OrderHandoverLog::create([
                'order_id' => $order->id,
                'seller_id' => $sellerId,
                'branch_id' => $branchId,
                'handed_over_by_id' => $staffId,
                'handed_over_by_name' => $staffName,
                'delivery_man_id' => $order->delivery_man_id,
                'delivery_man_name' => $order->deliveryMan ? ($order->deliveryMan->f_name . ' ' . $order->deliveryMan->l_name) : 'Assigned Rider',
                'pickup_otp_used' => $request->pickup_otp,
                'handed_over_at' => now(),
                'notes' => $request->notes ?? "In-shop custody transferred by {$staffName} via 6-digit OTP verification",
            ]);
        });

        $successMessage = translate('Custody_transferred_successfully!_Staff_') . $staffName . translate('_recorded_on_Audit_Log.');

        if ($request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => $successMessage,
                'handed_over_by' => $staffName,
                'handed_over_at' => now()->format('d M Y, h:i A'),
            ]);
        }

        ToastMagic::success($successMessage);
        return back();
    }
}

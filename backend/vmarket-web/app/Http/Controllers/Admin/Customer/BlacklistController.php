<?php

namespace App\Http\Controllers\Admin\Customer;

use App\Http\Controllers\Controller;
use App\Models\BlacklistedCustomer;
use App\Models\Order;
use App\Models\User;
use App\Services\ReceiptOcrAiService;
use App\Services\ReceiptUploadService;
use App\Services\WhatsAppAutomationWorkflow;
use Brian2694\Toastr\Facades\Toastr;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BlacklistController extends Controller
{
    /**
     * [AI] 1-Click Ban Customer & Blacklist identifiers.
     */
    public function banCustomer(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'user_id' => 'nullable|integer',
            'phone' => 'required|string',
            'reason' => 'nullable|string',
        ]);

        try {
            $user = null;
            if ($request->user_id) {
                $user = User::find($request->user_id);
            }

            if ($user) {
                $user->update(['is_active' => 0]);
            }

            BlacklistedCustomer::updateOrCreate(
                ['phone' => $request->phone],
                [
                    'user_id' => $user?->id,
                    'email' => $user?->email ?? $request->email,
                    'ip_address' => $request->ip(),
                    'reason' => $request->reason ?? 'Manual payment fraud / Suspicious activity',
                    'banned_by' => auth('admin')->id() ?? 1,
                ]
            );

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Customer successfully banned and blacklisted across all platforms.',
                ]);
            }

            Toastr::success('Customer successfully banned and blacklisted.');
            return back();

        } catch (Exception $e) {
            Log::error('[BlacklistController Ban Error] ' . $e->getMessage());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
            }
            Toastr::error('Failed to ban customer.');
            return back();
        }
    }

    /**
     * [AI] 1-Click Unban Customer.
     */
    public function unbanCustomer(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        try {
            BlacklistedCustomer::where('phone', $request->phone)->delete();
            User::where('phone', $request->phone)->update(['is_active' => 1]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => true, 'message' => 'Customer access restored.']);
            }

            Toastr::success('Customer access restored.');
            return back();

        } catch (Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
            }
            Toastr::error('Failed to restore customer.');
            return back();
        }
    }

    /**
     * [AI] 1-Click Approve & Confirm Bank Transfer Receipt for an Order.
     */
    public function verifyReceipt(Request $request, $orderId): JsonResponse|RedirectResponse
    {
        try {
            $order = Order::findOrFail($orderId);

            DB::transaction(function () use ($order) {
                $order->payment_status = 'paid';
                $order->order_status = 'confirmed';
                $order->receipt_verified_by = auth('admin')->id() ?? 1;
                $order->receipt_verified_at = now();
                $order->save();
            });

            // Trigger WhatsApp delivery notification with 6-digit OTP
            WhatsAppAutomationWorkflow::triggerOrderConfirmedNotification($order);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Receipt verified! Order #'.$order->id.' is confirmed and OTP issued.',
                ]);
            }

            Toastr::success('Receipt verified! Order confirmed.');
            return back();

        } catch (Exception $e) {
            Log::error('[BlacklistController Verify Error] ' . $e->getMessage());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
            }
            Toastr::error('Verification failed.');
            return back();
        }
    }

    /**
     * [AI] Reject Bank Transfer Receipt with optional WhatsApp feedback.
     */
    public function rejectReceipt(Request $request, $orderId): JsonResponse|RedirectResponse
    {
        $request->validate([
            'reason' => 'required|string',
        ]);

        try {
            $order = Order::findOrFail($orderId);
            $order->update([
                'payment_note' => 'Receipt rejected: ' . $request->reason,
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Receipt rejected for Order #' . $order->id,
                ]);
            }

            Toastr::info('Receipt rejected.');
            return back();

        } catch (Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
            }
            Toastr::error('Failed to reject receipt.');
            return back();
        }
    }
}

<?php

namespace App\Services;

use App\Models\DeliveryMan;
use App\Models\Order;
use App\Utils\SMSModule;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WhatsAppRiderService
{
    /**
     * [AI] Resolve authenticated delivery rider by verified phone number.
     */
    public static function getRider(string $phone): ?DeliveryMan
    {
        $normalizedPhone = SMSModule::formatNigerianPhone($phone);
        return DeliveryMan::where(function ($query) use ($normalizedPhone) {
                $query->where('phone', $normalizedPhone)
                    ->orWhere('phone', '0' . substr($normalizedPhone, 3))
                    ->orWhere('phone', '+' . $normalizedPhone);
            })
            ->where('is_active', 1)
            ->first();
    }

    /**
     * [AI] Fetch active delivery route & stops for this rider.
     */
    public static function getRiderRoute(string $phone): array
    {
        $rider = self::getRider($phone);
        if (!$rider) {
            return ['status' => false, 'message' => 'No active dispatch rider profile found for this phone number.'];
        }

        $orders = Order::where('delivery_man_id', $rider->id)
            ->whereIn('order_status', ['confirmed', 'processing', 'out_for_delivery'])
            ->with(['customer'])
            ->orderBy('id', 'asc')
            ->get();

        $stops = [];
        $totalCashToCollect = 0.0;

        foreach ($orders as $index => $ord) {
            $shipping = is_array($ord->shipping_address_data) ? $ord->shipping_address_data : json_decode($ord->shipping_address_data, true);
            $address = $shipping['address'] ?? ($ord->customer?->street_address ?? 'Uyo, Akwa Ibom');
            $custName = $shipping['contact_person_name'] ?? ($ord->customer ? trim($ord->customer->f_name . ' ' . $ord->customer->l_name) : 'Customer');
            $custPhone = $shipping['phone'] ?? ($ord->customer?->phone ?? 'N/A');

            $isPod = ($ord->payment_method === 'cash_on_delivery');
            $cashDue = $isPod ? (float)$ord->order_amount : 0.0;
            $totalCashToCollect += $cashDue;

            $mapUrl = 'https://maps.google.com/?q=' . urlencode($address . ', Uyo, Akwa Ibom');

            $stops[] = [
                'stop_number' => $index + 1,
                'order_id' => $ord->id,
                'customer_name' => $custName,
                'customer_phone' => $custPhone,
                'delivery_address' => $address,
                'google_maps_url' => $mapUrl,
                'payment_type' => $isPod ? 'Cash on Delivery (POD)' : 'Prepaid (Online)',
                'cash_to_collect' => $cashDue,
                'formatted_cash_due' => '₦' . number_format($cashDue, 2),
                'order_status' => $ord->order_status,
            ];
        }

        return [
            'status' => true,
            'rider_name' => trim($rider->f_name . ' ' . $rider->l_name),
            'total_active_stops' => count($stops),
            'total_cash_to_collect' => $totalCashToCollect,
            'formatted_total_cash' => '₦' . number_format($totalCashToCollect, 2),
            'stops' => $stops,
        ];
    }

    /**
     * [AI] Vendor Shop Pickup Verification (Phase 1 of Handshake).
     */
    public static function confirmPickup(string $phone, int $orderId, string $pickupCode): array
    {
        $rider = self::getRider($phone);
        if (!$rider) {
            return ['status' => false, 'message' => 'Unauthorized rider access.'];
        }

        $order = Order::where('id', $orderId)
            ->where('delivery_man_id', $rider->id)
            ->first();

        if (!$order) {
            return ['status' => false, 'message' => "Order #{$orderId} is not assigned to your route."];
        }

        $lockKey = "rider_pickup_attempts_{$orderId}";
        $attempts = (int)\Illuminate\Support\Facades\Cache::get($lockKey, 0);
        if ($attempts >= 5) {
            return [
                'status' => false,
                'message' => "⛔ Pickup verification for Order #{$orderId} is temporarily locked due to 5 failed attempts. Please contact central dispatch.",
            ];
        }

        $cleanInputCode = trim((string)$pickupCode);
        $savedCode = trim((string)$order->pickup_verification_code);

        if (!hash_equals($savedCode, $cleanInputCode)) {
            \Illuminate\Support\Facades\Cache::put($lockKey, $attempts + 1, now()->addMinutes(15));
            $remaining = 5 - ($attempts + 1);
            return [
                'status' => false,
                'message' => "❌ Invalid Pickup Code for Order #{$orderId} ({$remaining} attempts remaining). Please request the 6-digit Pickup Code from the vendor.",
            ];
        }

        \Illuminate\Support\Facades\Cache::forget($lockKey);

        $order->update([
            'order_status' => 'out_for_delivery',
        ]);

        return [
            'status' => true,
            'order_id' => $orderId,
            'message' => "📦 Pickup Confirmed for Order #{$orderId}! Order is now Out for Delivery.",
        ];
    }

    /**
     * [AI] Doorstep 6-digit Delivery OTP Verification with instant order completion & brute-force lockout.
     */
    public static function verifyDoorstepOtp(string $phone, int $orderId, string $otp): array
    {
        $rider = self::getRider($phone);
        if (!$rider) {
            return ['status' => false, 'message' => 'Unauthorized rider access.'];
        }

        $order = Order::where('id', $orderId)
            ->where('delivery_man_id', $rider->id)
            ->first();

        if (!$order) {
            return ['status' => false, 'message' => "Order #{$orderId} is not assigned to your delivery route."];
        }

        if ($order->order_status === 'delivered') {
            return ['status' => false, 'message' => "Order #{$orderId} has already been marked delivered."];
        }

        // [AI] Brute-Force Rate Limiter: Max 5 attempts per order (15-min lockout)
        $lockKey = "rider_otp_attempts_{$orderId}";
        $attempts = (int)\Illuminate\Support\Facades\Cache::get($lockKey, 0);
        if ($attempts >= 5) {
            return [
                'status' => false,
                'message' => "⛔ Doorstep OTP verification for Order #{$orderId} is locked due to 5 consecutive failed attempts. Contact dispatch support.",
            ];
        }

        // [AI] Cryptographic Constant-Time Comparison (Zero Timing-Leak)
        $cleanInputOtp = trim((string)$otp);
        $savedOtp = trim((string)$order->verification_code);

        if (!hash_equals($savedOtp, $cleanInputOtp)) {
            \Illuminate\Support\Facades\Cache::put($lockKey, $attempts + 1, now()->addMinutes(15));
            $remaining = 5 - ($attempts + 1);
            return [
                'status' => false,
                'message' => "❌ Invalid OTP code for Order #{$orderId} ({$remaining} attempts remaining). Please request the correct 6-digit code from the customer at the doorstep.",
            ];
        }

        \Illuminate\Support\Facades\Cache::forget($lockKey);

        // OTP Matches! Complete delivery atomically
        DB::transaction(function () use ($order) {
            $order->update([
                'order_status' => 'delivered',
                'payment_status' => 'paid',
                'delivered_at' => now(),
            ]);
        });

        // Trigger customer delivery confirmation
        \App\Services\WhatsAppAutomationWorkflow::triggerOrderDeliveredNotification($order);

        return [
            'status' => true,
            'order_id' => $orderId,
            'message' => "✅ Order #{$orderId} delivered successfully! OTP verified and customer notified.",
        ];
    }

    /**
     * [AI] Cash-in-Hand calculation for today's delivered POD orders.
     */
    public static function getCashInHand(string $phone): array
    {
        $rider = self::getRider($phone);
        if (!$rider) {
            return ['status' => false, 'message' => 'Unauthorized rider access.'];
        }

        $cashTotal = Order::where('delivery_man_id', $rider->id)
            ->where('payment_method', 'cash_on_delivery')
            ->where('order_status', 'delivered')
            ->whereDate('updated_at', today())
            ->sum('order_amount');

        return [
            'status' => true,
            'rider_name' => trim($rider->f_name . ' ' . $rider->l_name),
            'cash_in_hand_today' => (float)$cashTotal,
            'formatted_cash_in_hand' => '₦' . number_format($cashTotal, 2),
            'instruction' => 'Please remit collected physical cash to the Uyo Central Hub supervisor at the end of your shift.',
        ];
    }
}

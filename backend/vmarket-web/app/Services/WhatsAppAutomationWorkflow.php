<?php

namespace App\Services;

use App\Jobs\SendWhatsAppJob;
use App\Models\Order;
use App\Models\User;
use App\Utils\SMSModule;

class WhatsAppAutomationWorkflow
{
    /**
     * [AI] Trigger Out for Delivery Notification with 6-digit Delivery OTP & Dynamic Paystack Link.
     */
    public static function triggerOutForDeliveryNotification(Order $order): void
    {
        $phone = $order->customer?->phone ?? $order->billing_address_data['phone'] ?? null;
        if (empty($phone)) return;

        $customerName = $order->customer ? $order->customer->f_name : 'Customer';
        $orderId = $order->id;
        $otp = $order->verification_code ?? '------';
        $riderName = $order->delivery_man ? ($order->delivery_man->f_name . ' ' . $order->delivery_man->l_name) : 'Our Dispatch Rider';
        $riderPhone = $order->delivery_man->phone ?? '';

        $message = "🛵 *Victorious MARKET Delivery Alert*\n\n"
            . "Hello {$customerName}! Your order *#{$orderId}* is now *OUT FOR DELIVERY* with rider {$riderName} ({$riderPhone}).\n\n"
            . "🔑 *Your 6-Digit Delivery OTP:* [ *{$otp}* ]\n"
            . "(Please provide this code to the rider upon receiving your package).\n\n";

        if ($order->payment_status !== 'paid') {
            $payLink = url("/pay/order/{$orderId}");
            $message .= "💳 *Pay on Delivery:* Paying via bank transfer? Tap here to pay securely via Paystack:\n{$payLink}\n\n";
        }

        $message .= "Thank you for choosing Victorious MARKET!";

        dispatch(new SendWhatsAppJob($phone, 'text', ['text' => $message]));
    }

    /**
     * [AI] Trigger Post-Delivery Thank You & Review Request (2 hours post-delivery).
     */
    public static function triggerPostDeliveryReview(Order $order): void
    {
        $phone = $order->customer?->phone ?? null;
        if (empty($phone)) return;

        $customerName = $order->customer ? $order->customer->f_name : 'Customer';
        $orderId = $order->id;

        $message = "⭐ *How was your experience, {$customerName}?*\n\n"
            . "Your order *#{$orderId}* was successfully delivered! We hope you love your purchase.\n\n"
            . "Please rate your experience or reply here if you need any assistance. Have a wonderful day!";

        dispatch(new SendWhatsAppJob($phone, 'text', ['text' => $message]));
    }

    /**
     * [AI] Trigger Abandoned Cart Recovery Reminder.
     */
    public static function triggerCartRecovery(User $user, string $productName, float $cartTotal): void
    {
        if (empty($user->phone)) return;

        $checkoutUrl = url('/cart');
        $message = "👋 *Hi {$user->f_name}! Did you forget something?*\n\n"
            . "You left *{$productName}* (Total: ₦" . number_format($cartTotal, 2) . ") in your Victorious MARKET cart.\n\n"
            . "Items sell fast in Uyo! Tap below to complete your order right now:\n{$checkoutUrl}";

        dispatch(new SendWhatsAppJob($user->phone, 'text', ['text' => $message]));
    }
}

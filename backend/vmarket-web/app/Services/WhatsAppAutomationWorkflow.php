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
     * [AI] Trigger Vendor WhatsApp Notification on New Order Received.
     */
    public static function triggerVendorNewOrderAlert(Order $order): void
    {
        $order->loadMissing(['details.seller', 'details.product']);
        $sellersNotified = [];

        foreach ($order->details as $detail) {
            $seller = $detail->seller;
            if (!$seller || empty($seller->phone) || in_array($seller->id, $sellersNotified)) {
                continue;
            }

            $sellersNotified[] = $seller->id;
            $vendorName = $seller->f_name ?? 'Partner Vendor';
            $productName = $detail->product ? $detail->product->name : 'Product Item';
            $qty = $detail->qty;

            $message = "📦 *Victorious MARKET — New Order Alert!*\n\n"
                . "Hello {$vendorName}! You have received a new order *#{$order->id}*.\n\n"
                . "🛒 *Item to prepare:* {$qty}x {$productName}\n"
                . "📍 *Pickup Hub:* Uyo Central Hub\n"
                . "🕒 Please package this item promptly for courier pickup.\n\n"
                . "Thank you for partnering with Victorious MARKET!";

            dispatch(new SendWhatsAppJob($seller->phone, 'text', ['text' => $message]));
        }
    }

    /**
     * [AI] Trigger Delivery Rider WhatsApp Notification on Order Assignment.
     */
    public static function triggerDeliveryManAssignmentAlert(Order $order, $deliveryMan): void
    {
        if (!$deliveryMan || empty($deliveryMan->phone)) return;

        $riderName = $deliveryMan->f_name ?? 'Dispatch Rider';
        $orderId = $order->id;
        $shippingData = json_decode($order->shipping_address_data, true) ?? [];
        $dropoff = $shippingData['address'] ?? 'Uyo, Akwa Ibom';
        $customerName = $shippingData['contact_person_name'] ?? 'Customer';
        $customerPhone = $shippingData['phone'] ?? '';

        $message = "🛵 *Victorious MARKET — New Delivery Assignment!*\n\n"
            . "Hello {$riderName}! Order *#{$orderId}* has been assigned to you.\n\n"
            . "📍 *Dropoff Address:* {$dropoff}\n"
            . "👤 *Customer:* {$customerName} ({$customerPhone})\n"
            . "💰 *Order Total:* ₦" . number_format($order->order_amount, 2) . " (" . strtoupper($order->payment_status) . ")\n\n"
            . "⚠️ *CRITICAL:* Collect the customer's secret 6-digit Delivery OTP upon doorstep arrival to complete the delivery.\n\n"
            . "Drive safely!";

        dispatch(new SendWhatsAppJob($deliveryMan->phone, 'text', ['text' => $message]));
    }

    /**
     * [AI] Trigger Vendor WhatsApp Notification on Payout / Withdrawal Approval.
     */
    public static function triggerVendorWithdrawalApprovedAlert($withdraw): void
    {
        $seller = $withdraw->seller ?? null;
        if (!$seller || empty($seller->phone)) return;

        $vendorName = $seller->f_name ?? 'Partner Vendor';
        $amount = number_format($withdraw->amount, 2);

        $message = "💰 *Victorious MARKET — Payout Approved!*\n\n"
            . "Hello {$vendorName}! Your withdrawal request of *₦{$amount}* has been approved and disbursed to your registered bank account.\n\n"
            . "Thank you for your business!";

        dispatch(new SendWhatsAppJob($seller->phone, 'text', ['text' => $message]));
    }
}

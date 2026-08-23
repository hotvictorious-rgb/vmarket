<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;

class WhatsAppCustomerTransformer
{
    /**
     * [AI] Strips all sensitive vendor, supplier, bank, and wholesale data before AI or chat consumption.
     */
    public static function sanitizeProductForChat(Product $product): array
    {
        $thumbnail = null;
        if (is_array($product->thumbnail_full_url)) {
            $thumbnail = $product->thumbnail_full_url['path'] ?? null;
        } elseif (is_string($product->thumbnail_full_url)) {
            $thumbnail = $product->thumbnail_full_url;
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'price' => (float) $product->unit_price,
            'discount' => (float) $product->discount,
            'discount_type' => $product->discount_type,
            'current_stock' => (int) $product->current_stock,
            'thumbnail' => $thumbnail,
            'colors' => json_decode($product->colors ?? '[]', true),
            'choice_options' => json_decode($product->choice_options ?? '[]', true),
            'fulfillment_center' => 'Victorious MARKET Central Hub (Uyo)',
            
            // ❌ STRICT PRIVACY EXCLUSIONS:
            // 'user_id', 'added_by', 'seller_id', 'purchase_price' (cost),
            // 'seller.phone', 'seller.email', 'seller.bank_name', 'seller.account_no'
        ];
    }

    /**
     * [AI] Sanitizes Order details for customer chat and AI status queries.
     */
    public static function sanitizeOrderForChat(Order $order): array
    {
        $rider = null;
        if ($order->delivery_man) {
            $rider = [
                'name' => trim(($order->delivery_man->f_name ?? '') . ' ' . ($order->delivery_man->l_name ?? '')),
                'phone' => $order->delivery_man->phone ?? '',
            ];
        }

        $items = [];
        if ($order->details) {
            foreach ($order->details as $detail) {
                $productDetails = json_decode($detail->product_details ?? '{}', true);
                $items[] = [
                    'product_name' => $productDetails['name'] ?? ($detail->product->name ?? 'Item'),
                    'qty' => (int) $detail->qty,
                    'price' => (float) $detail->price,
                    'variant' => $detail->variant ?? '',
                ];
            }
        }

        return [
            'id' => $order->id,
            'order_id' => $order->id,
            'order_type' => $order->order_type ?? 'default_type',
            'status' => $order->order_status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'order_amount' => (float) $order->order_amount,
            'pod_dispatch_fee' => (float) ($order->pod_dispatch_fee ?? 0.0),
            'doorstep_due_amount' => (float) ($order->doorstep_due_amount > 0 ? $order->doorstep_due_amount : $order->order_amount),
            'bank_session_id' => $order->bank_session_id,
            'receipt_image' => $order->receipt_image ? (str_starts_with($order->receipt_image, 'http') ? $order->receipt_image : asset('storage/' . $order->receipt_image)) : null,
            'receipt_metadata' => $order->receipt_metadata,
            'verification_code' => $order->verification_code, // 6-digit Customer Delivery OTP
            'delivery_rider' => $rider,
            'items' => $items,
            'hub_name' => $order->delivery_hub?->name ?? 'Victorious MARKET Uyo Hub',
            'created_at' => $order->created_at?->toFormattedDateString(),
        ];
    }
}

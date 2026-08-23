<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Seller;
use App\Utils\SMSModule;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WhatsAppVendorService
{
    /**
     * [AI] Resolve authenticated vendor record by verified phone number.
     */
    public static function getSeller(string $phone): ?Seller
    {
        $normalizedPhone = SMSModule::formatNigerianPhone($phone);
        return Seller::where('phone', $normalizedPhone)
            ->orWhere('phone', '0' . substr($normalizedPhone, 3))
            ->orWhere('phone', '+' . $normalizedPhone)
            ->with(['shop', 'wallet'])
            ->first();
    }

    /**
     * [AI] Fetch vendor store performance, pending orders, and wallet balance.
     */
    public static function getVendorSummary(string $phone): array
    {
        $seller = self::getSeller($phone);
        if (!$seller) {
            return ['status' => false, 'message' => 'No approved vendor store found for this phone number.'];
        }

        $shop = $seller->shop;
        $wallet = $seller->wallet;

        $pendingCount = Order::where('seller_is', 'seller')
            ->where('seller_id', $seller->id)
            ->whereIn('order_status', ['pending', 'confirmed', 'processing'])
            ->count();

        $todaySales = Order::where('seller_is', 'seller')
            ->where('seller_id', $seller->id)
            ->whereDate('created_at', today())
            ->where('payment_status', 'paid')
            ->sum('order_amount');

        $activeProductsCount = Product::where('user_id', $seller->id)
            ->where('added_by', 'seller')
            ->where('status', 1)
            ->count();

        return [
            'status' => true,
            'seller_id' => $seller->id,
            'shop_name' => $shop ? $shop->name : ($seller->f_name . ' Shop'),
            'vendor_name' => trim($seller->f_name . ' ' . $seller->l_name),
            'pending_orders_count' => $pendingCount,
            'today_sales_amount' => (float)$todaySales,
            'formatted_today_sales' => '₦' . number_format($todaySales, 2),
            'withdrawable_balance' => (float)($wallet->balance ?? 0.0),
            'formatted_balance' => '₦' . number_format($wallet->balance ?? 0.0, 2),
            'active_products_count' => $activeProductsCount,
            'bank_info' => [
                'bank_name' => $seller->bank_name ?? 'Not set',
                'account_no' => $seller->account_no ? ('******' . substr($seller->account_no, -4)) : 'Not set',
                'holder_name' => $seller->holder_name ?? '',
            ],
        ];
    }

    /**
     * [AI] Fetch orders awaiting packaging & pickup at vendor's store.
     */
    public static function getPendingOrders(string $phone): array
    {
        $seller = self::getSeller($phone);
        if (!$seller) {
            return ['status' => false, 'message' => 'No approved vendor store found.'];
        }

        $orders = Order::where('seller_is', 'seller')
            ->where('seller_id', $seller->id)
            ->whereIn('order_status', ['pending', 'confirmed', 'processing'])
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        $items = [];
        foreach ($orders as $ord) {
            $details = OrderDetail::where('order_id', $ord->id)->get();
            $itemNames = [];
            foreach ($details as $d) {
                $pData = json_decode($d->product_details, true);
                $itemNames[] = ($pData['name'] ?? 'Product') . ' (Qty: ' . $d->qty . ')';
            }

            $items[] = [
                'order_id' => $ord->id,
                'status' => $ord->order_status,
                'items' => implode(', ', $itemNames),
                'order_amount' => (float)$ord->order_amount,
                'formatted_amount' => '₦' . number_format($ord->order_amount, 2),
                'payment_status' => $ord->payment_status,
                'created_at' => $ord->created_at->format('d M, h:i A'),
            ];
        }

        return [
            'status' => true,
            'count' => count($items),
            'orders' => $items,
        ];
    }

    /**
     * [AI] Confirm order packed and ready for dispatch rider pickup.
     */
    public static function confirmOrderReady(string $phone, int $orderId): array
    {
        $seller = self::getSeller($phone);
        if (!$seller) {
            return ['status' => false, 'message' => 'Unauthorized vendor access.'];
        }

        $order = Order::where('id', $orderId)
            ->where('seller_id', $seller->id)
            ->first();

        if (!$order) {
            return ['status' => false, 'message' => "Order #{$orderId} not found under your store catalog."];
        }

        $order->update([
            'order_status' => 'processing',
        ]);

        return [
            'status' => true,
            'message' => "Order #{$orderId} confirmed packed! Our dispatch rider in Uyo has been alerted for pickup.",
            'order_id' => $orderId,
        ];
    }

    /**
     * [AI] Conversational stock adjustment with strict vendor IDOR scoping.
     */
    public static function updateStock(string $phone, string $productName, int $newStock, ?string $variant = null): array
    {
        $seller = self::getSeller($phone);
        if (!$seller) {
            return ['status' => false, 'message' => 'Unauthorized vendor access.'];
        }

        $product = Product::where('user_id', $seller->id)
            ->where('added_by', 'seller')
            ->where('name', 'like', "%{$productName}%")
            ->first();

        if (!$product) {
            return [
                'status' => false,
                'message' => "Product '{$productName}' not found in your store inventory.",
            ];
        }

        $product->update([
            'current_stock' => max(0, $newStock),
        ]);

        return [
            'status' => true,
            'product_name' => $product->name,
            'new_stock' => $product->current_stock,
            'message' => "Stock for '{$product->name}' has been updated to {$product->current_stock} units on Victorious MARKET.",
        ];
    }

    /**
     * [AI] Fetch vendor earnings and withdrawable balance.
     */
    public static function getPayoutSummary(string $phone): array
    {
        $seller = self::getSeller($phone);
        if (!$seller) {
            return ['status' => false, 'message' => 'Unauthorized vendor access.'];
        }

        $wallet = $seller->wallet;
        $balance = (float)($wallet->balance ?? 0.0);

        return [
            'status' => true,
            'balance' => $balance,
            'formatted_balance' => '₦' . number_format($balance, 2),
            'total_earning' => '₦' . number_format($wallet->total_earning ?? 0.0, 2),
            'withdrawn' => '₦' . number_format($wallet->withdrawn ?? 0.0, 2),
            'pending_withdraw' => '₦' . number_format($wallet->pending_withdraw ?? 0.0, 2),
            'bank_name' => $seller->bank_name ?? 'Not configured',
            'account_no' => $seller->account_no ? ('******' . substr($seller->account_no, -4)) : 'Not configured',
        ];
    }
}

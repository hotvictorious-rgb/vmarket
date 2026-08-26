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
        return Seller::where(function ($query) use ($normalizedPhone) {
                $query->where('phone', $normalizedPhone)
                    ->orWhere('phone', '0' . substr($normalizedPhone, 3))
                    ->orWhere('phone', '+' . $normalizedPhone);
            })
            ->where('status', 'approved')
            ->with(['shop', 'wallet'])
            ->first();
    }

    /**
     * [AI] Verify if a seller has an active paid Pro AI/Multi-Branch subscription.
     */
    public static function isSubscribedVendor(int $sellerId): bool
    {
        return \App\Models\PosSubscription::where('seller_id', $sellerId)
            ->where('plan_type', '!=', 'starter_free')
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * [AI] Return professional upgrade guidance message for unsubscribed merchants.
     */
    public static function getSubscriptionGuardMessage(Seller $seller): array
    {
        $shopName = $seller->shop ? $seller->shop->name : ($seller->f_name . "'s Store");
        $upgradeUrl = url('/seller/subscription');

        return [
            'status' => false,
            'is_unsubscribed' => true,
            'message' => "🔒 *Exclusive Pro Merchant Feature*\n\nHello *{$shopName}*! Your merchant account is currently on the *Free Starter Plan*.\n\n✨ To unlock your *24/7 WhatsApp AI Store Sales Agent*, instant WhatsApp voice/text inventory updates, and daily automated performance summaries, please upgrade to the *Pro AI Tier* (₦10,000/mo).\n\n👉 *Upgrade Your Store Now:* {$upgradeUrl}\n\n_If you are shopping as a customer, feel free to search products or ask for recommendations!_",
        ];
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

        // [AI] Strict Subscription Guard
        if (!self::isSubscribedVendor($seller->id)) {
            return self::getSubscriptionGuardMessage($seller);
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

        // [AI] Strict Subscription Guard
        if (!self::isSubscribedVendor($seller->id)) {
            return self::getSubscriptionGuardMessage($seller);
        }

        $orders = Order::where('seller_is', 'seller')
            ->where('seller_id', $seller->id)
            ->whereIn('order_status', ['pending', 'confirmed', 'processing'])
            ->with(['delivery_man', 'customer'])
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        $items = [];
        foreach ($orders as $ord) {
            $details = OrderDetail::where('order_id', $ord->id)->get();
            $itemLines = [];
            foreach ($details as $d) {
                $pData = json_decode($d->product_details, true);
                $varStr = !empty($d->variant) ? " [{$d->variant}]" : '';
                $itemLines[] = "• {$d->qty}x " . ($pData['name'] ?? 'Product') . $varStr;
            }

            $shipping = is_array($ord->shipping_address_data) ? $ord->shipping_address_data : json_decode($ord->shipping_address_data, true);
            $destination = $shipping['address'] ?? ($ord->customer?->street_address ?? 'Uyo, Akwa Ibom');

            $riderInfo = 'Assigning dispatch rider...';
            if ($ord->delivery_man) {
                $riderInfo = trim($ord->delivery_man->f_name . ' ' . $ord->delivery_man->l_name) . ' (📞 ' . ($ord->delivery_man->phone ?? 'N/A') . ')';
            }

            $items[] = [
                'order_id' => $ord->id,
                'status' => $ord->order_status,
                'items_list' => implode("\n", $itemLines),
                'total_quantity' => $details->sum('qty'),
                'order_amount' => (float)$ord->order_amount,
                'formatted_amount' => '₦' . number_format($ord->order_amount, 2),
                'payment_status' => $ord->payment_status,
                'delivery_destination' => $destination,
                'assigned_rider' => $riderInfo,
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

        // [AI] Strict Subscription Guard
        if (!self::isSubscribedVendor($seller->id)) {
            return self::getSubscriptionGuardMessage($seller);
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
     * [AI] Fetch vendor pickup code for rider shop collection with detailed parcel summary.
     */
    public static function getPickupCode(string $phone, int $orderId): array
    {
        $seller = self::getSeller($phone);
        if (!$seller) {
            return ['status' => false, 'message' => 'Unauthorized vendor access.'];
        }

        // [AI] Strict Subscription Guard
        if (!self::isSubscribedVendor($seller->id)) {
            return self::getSubscriptionGuardMessage($seller);
        }

        $order = Order::where('id', $orderId)
            ->where('seller_id', $seller->id)
            ->with(['delivery_man', 'details'])
            ->first();

        if (!$order) {
            return ['status' => false, 'message' => "Order #{$orderId} not found in your store catalog."];
        }

        if (empty($order->pickup_verification_code)) {
            $order->update(['pickup_verification_code' => (string)rand(100000, 999999)]);
            $order->refresh();
        }

        $itemLines = [];
        foreach ($order->details as $d) {
            $pData = json_decode($d->product_details, true);
            $varStr = !empty($d->variant) ? " [{$d->variant}]" : '';
            $itemLines[] = "• {$d->qty}x " . ($pData['name'] ?? 'Product') . $varStr;
        }

        $riderName = $order->delivery_man ? trim($order->delivery_man->f_name . ' ' . $order->delivery_man->l_name) : 'Assigned Victorious MARKET Rider';

        return [
            'status' => true,
            'order_id' => $order->id,
            'pickup_code' => $order->pickup_verification_code,
            'items_breakdown' => implode("\n", $itemLines),
            'assigned_rider' => $riderName,
            'message' => "🔑 *Pickup Code for Order #{$order->id}*: *{$order->pickup_verification_code}*\n\n📦 *Parcel Contents:*\n" . implode("\n", $itemLines) . "\n\n🛵 *Give this 6-digit code to {$riderName} upon package handover.*",
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

        // [AI] Strict Subscription Guard
        if (!self::isSubscribedVendor($seller->id)) {
            return self::getSubscriptionGuardMessage($seller);
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
     * [AI] Fetch vendor earnings, withdrawable balance, and recent payout receipts.
     * Note: Bank account editing is STRICTLY FORBIDDEN over WhatsApp for anti-fraud security.
     */
    public static function getPayoutSummary(string $phone): array
    {
        $seller = self::getSeller($phone);
        if (!$seller) {
            return ['status' => false, 'message' => 'Unauthorized vendor access.'];
        }

        // [AI] Strict Subscription Guard
        if (!self::isSubscribedVendor($seller->id)) {
            return self::getSubscriptionGuardMessage($seller);
        }

        $wallet = $seller->wallet;
        $balance = (float)($wallet->balance ?? 0.0);

        // Fetch recent payout receipts
        $withdrawals = \App\Models\WithdrawRequest::where('seller_id', $seller->id)
            ->orderBy('id', 'desc')
            ->take(3)
            ->get();

        $receipts = [];
        foreach ($withdrawals as $w) {
            $statusText = match ((int)$w->approved) {
                1 => '✅ Approved & Settled',
                2 => '❌ Denied / Returned',
                default => '⏳ Pending Admin Processing',
            };
            $receipts[] = [
                'ref_id' => 'WD-' . str_pad($w->id, 6, '0', STR_PAD_LEFT),
                'amount' => '₦' . number_format($w->amount, 2),
                'status' => $statusText,
                'date' => $w->created_at->format('d M Y, h:i A'),
            ];
        }

        return [
            'status' => true,
            'balance' => $balance,
            'formatted_balance' => '₦' . number_format($balance, 2),
            'total_earning' => '₦' . number_format($wallet->total_earning ?? 0.0, 2),
            'withdrawn' => '₦' . number_format($wallet->withdrawn ?? 0.0, 2),
            'pending_withdraw' => '₦' . number_format($wallet->pending_withdraw ?? 0.0, 2),
            'registered_bank' => [
                'bank_name' => $seller->bank_name ?? 'Not configured',
                'account_no' => $seller->account_no ? ('******' . substr($seller->account_no, -4)) : 'Not configured',
                'account_holder' => $seller->holder_name ?? 'Registered Merchant',
                'security_notice' => '🔒 For security, bank account edits must be performed inside your Seller Web Panel with 2FA verification.',
            ],
            'recent_payout_receipts' => $receipts,
        ];
    }

    /**
     * [AI] Securely request a withdrawal to the vendor's verified registered bank account.
     */
    public static function requestPayout(string $phone, float $amount): array
    {
        $seller = self::getSeller($phone);
        if (!$seller) {
            return ['status' => false, 'message' => 'Unauthorized vendor access.'];
        }

        // [AI] Strict Subscription Guard
        if (!self::isSubscribedVendor($seller->id)) {
            return self::getSubscriptionGuardMessage($seller);
        }

        if (empty($seller->account_no) || empty($seller->bank_name)) {
            return [
                'status' => false,
                'message' => "❌ No bank account configured! Please log in to your Seller Web Panel to link and verify your payout bank account before requesting withdrawals.",
            ];
        }

        if ($amount < 1000) {
            return [
                'status' => false,
                'message' => "❌ Minimum withdrawal amount is ₦1,000.00.",
            ];
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($seller, $amount) {
            $wallet = \App\Models\SellerWallet::where('seller_id', $seller->id)->lockForUpdate()->first();
            if (!$wallet || (float)$wallet->balance < $amount) {
                $available = (float)($wallet->balance ?? 0.0);
                return [
                    'status' => false,
                    'message' => "❌ Insufficient available balance! You have ₦" . number_format($available, 2) . " available for withdrawal.",
                ];
            }

            // Atomic balance deduction
            $wallet->decrement('balance', $amount);
            $wallet->increment('pending_withdraw', $amount);

            $withdraw = \App\Models\WithdrawRequest::create([
                'seller_id' => $seller->id,
                'amount' => $amount,
                'transaction_note' => 'Requested via WhatsApp AI Assistant',
                'approved' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $maskedAcc = '******' . substr($seller->account_no, -4);

            return [
                'status' => true,
                'ref_id' => 'WD-' . str_pad($withdraw->id, 6, '0', STR_PAD_LEFT),
                'amount' => $amount,
                'formatted_amount' => '₦' . number_format($amount, 2),
                'remaining_balance' => (float)($wallet->balance),
                'formatted_remaining_balance' => '₦' . number_format($wallet->balance, 2),
                'destination_bank' => "{$seller->bank_name} ({$maskedAcc})",
                'message' => "✅ Payout Request Submitted Successfully!\n\n📋 *Ref ID:* WD-" . str_pad($withdraw->id, 6, '0', STR_PAD_LEFT) . "\n💰 *Amount:* ₦" . number_format($amount, 2) . "\n🏦 *Destination:* {$seller->bank_name} ({$maskedAcc})\n⏳ *Status:* Pending Admin Bank Transfer\n\nYour remaining store balance is ₦" . number_format($wallet->balance, 2) . ". You will receive a notification once the bank transfer is disbursed!",
            ];
        });
    }

    /**
     * [AI] Create a product draft from WhatsApp conversational merchant listing.
     * Enforces request_status = 0 (Pending Admin Approval) & status = 0 (Inactive/Hidden).
     */
    public static function createProductDraft(
        string $phone,
        string $name,
        float $unitPrice,
        int $stock = 1,
        ?string $categoryName = null,
        ?string $details = null,
        ?string $imageUrl = null
    ): array
    {
        $seller = self::getSeller($phone);
        if (!$seller) {
            return ['status' => false, 'message' => 'Unauthorized vendor access. Your store must be approved by admin.'];
        }

        // [AI] Strict Subscription Guard
        if (!self::isSubscribedVendor($seller->id)) {
            return self::getSubscriptionGuardMessage($seller);
        }

        if ($unitPrice < 50) {
            return ['status' => false, 'message' => 'Invalid product price. Must be at least ₦50.00.'];
        }

        // 1. Resolve or fallback category
        $category = null;
        if (!empty($categoryName)) {
            $category = \App\Models\Category::where('name', 'like', "%{$categoryName}%")->first();
        }
        if (!$category) {
            $category = \App\Models\Category::first();
        }

        $categoryIds = [];
        if ($category) {
            $categoryIds[] = [
                'id' => (string)$category->id,
                'position' => 1,
            ];
        }

        // 2. Generate unique slug and SKU code
        $cleanName = trim($name);
        $slug = \Illuminate\Support\Str::slug($cleanName) . '-' . \Illuminate\Support\Str::random(6);
        $code = 'PRD-' . rand(100000, 999999);
        $merchantShopName = $seller->shop?->name ?? 'Merchant';
        $productDetails = $details ?? "Authentic {$cleanName} listed by {$merchantShopName} in Uyo on Victorious MARKET.";

        // 3. Create product record with strict Pending & Inactive state
        $product = \App\Models\Product::create([
            'user_id' => $seller->id,
            'shop_id' => $seller->shop?->id ?? 0,
            'added_by' => 'seller',
            'name' => $cleanName,
            'code' => $code,
            'slug' => $slug,
            'category_ids' => json_encode($categoryIds),
            'category_id' => $category?->id,
            'unit_price' => $unitPrice,
            'purchase_price' => $unitPrice,
            'tax' => 0.00,
            'tax_type' => 'percent',
            'tax_model' => 'exclude',
            'discount' => 0.00,
            'discount_type' => 'flat',
            'current_stock' => max(1, $stock),
            'minimum_order_qty' => 1,
            'details' => $productDetails,
            'product_type' => 'physical',
            'thumbnail' => $imageUrl ?? 'def.png',
            'images' => json_encode($imageUrl ? [$imageUrl] : ['def.png']),
            'status' => 0, // 🔒 HIDDEN from customer storefront until approved
            'request_status' => 0, // 🔒 PENDING Super Admin Review & Approval
            'published' => 0,
            'colors' => json_encode([]),
            'choice_options' => json_encode([]),
            'variation' => json_encode([]),
        ]);

        return [
            'status' => true,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => $unitPrice,
            'formatted_price' => '₦' . number_format($unitPrice, 2),
            'stock' => $product->current_stock,
            'category' => $category?->name ?? 'General',
            'request_status' => 'Pending Admin Approval',
            'message' => "🎉 *Product Draft Created Successfully!*\n\n📦 *Item:* {$product->name}\n💰 *Price:* ₦" . number_format($unitPrice, 2) . "\n📊 *Stock:* {$product->current_stock} units\n🏷️ *Category:* " . ($category?->name ?? 'General') . "\n⏳ *Status:* Submitted to Super Admin for review.\n\nYour product will be reviewed and published to the customer storefront shortly!",
        ];
    }
}

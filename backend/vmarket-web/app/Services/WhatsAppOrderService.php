<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\DeliveryHub;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\User;
use App\Utils\SMSModule;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppOrderService
{
    /**
     * [AI] Finds or auto-registers a customer from WhatsApp.
     */
    public static function getOrCreateCustomer(string $phone, ?string $name = null, ?string $address = null): User
    {
        $formattedPhone = SMSModule::formatNigerianPhone($phone);

        $user = User::where('phone', $formattedPhone)
            ->orWhere('phone', '0' . substr($formattedPhone, 3))
            ->orWhere('phone', '+' . $formattedPhone)
            ->first();

        if (!$user) {
            $nameParts = explode(' ', trim($name ?? 'Valued Customer'), 2);
            $fName = $nameParts[0] ?? 'Valued';
            $lName = $nameParts[1] ?? 'Shopper';

            $user = User::create([
                'f_name' => $fName,
                'l_name' => $lName,
                'phone' => $formattedPhone,
                'email' => $formattedPhone . '@victoriousmarket.com',
                'password' => bcrypt(Str::random(16)),
                'street_address' => $address ?? 'Uyo, Akwa Ibom',
                'is_active' => 1,
                'is_phone_verified' => 1,
                'is_email_verified' => 1,
            ]);
        } elseif ($address && empty($user->street_address)) {
            $user->update(['street_address' => $address]);
        }

        return $user;
    }

    /**
     * [AI] Add product to customer's cart with live stock validation.
     */
    public static function addToCart(string $phone, int $productId, int $qty = 1, ?string $variant = null): array
    {
        $user = self::getOrCreateCustomer($phone);
        $product = Product::active()->find($productId);

        if (!$product) {
            return ['status' => false, 'message' => 'Product not found or unavailable.'];
        }

        if ($product->current_stock < $qty) {
            return [
                'status' => false,
                'message' => "Sorry, only {$product->current_stock} units left in stock for {$product->name}."
            ];
        }

        $price = (float) $product->unit_price;
        $discount = (float) $product->discount;
        if ($product->discount_type === 'percent') {
            $price -= ($price * $discount / 100);
        } elseif ($product->discount_type === 'flat') {
            $price -= $discount;
        }

        $cart = Cart::updateOrCreate(
            [
                'customer_id' => $user->id,
                'product_id' => $productId,
                'variant' => $variant ?? '',
            ],
            [
                'name' => $product->name,
                'seller_id' => $product->user_id,
                'seller_is' => $product->added_by,
                'quantity' => DB::raw("quantity + {$qty}"),
                'price' => $price,
                'tax' => 0,
                'slug' => $product->slug,
                'thumbnail' => is_array($product->thumbnail_full_url) ? ($product->thumbnail_full_url['path'] ?? '') : ($product->thumbnail_full_url ?? ''),
            ]
        );

        return [
            'status' => true,
            'message' => "Added {$qty}x {$product->name} to cart!",
            'product_name' => $product->name,
            'unit_price' => $price,
            'cart_summary' => self::getCartSummary($phone),
        ];
    }

    /**
     * [AI] Computes itemized cart summary and shipping estimates.
     */
    public static function getCartSummary(string $phone): array
    {
        $user = self::getOrCreateCustomer($phone);
        $cartItems = Cart::where('customer_id', $user->id)->get();

        $items = [];
        $subtotal = 0.0;

        foreach ($cartItems as $c) {
            $itemTotal = (float)$c->price * (int)$c->quantity;
            $subtotal += $itemTotal;
            $items[] = [
                'id' => $c->id,
                'product_id' => $c->product_id,
                'name' => $c->name,
                'qty' => (int)$c->quantity,
                'unit_price' => (float)$c->price,
                'item_total' => $itemTotal,
                'variant' => $c->variant,
            ];
        }

        // Standard Uyo central hub shipping rate
        $shippingFee = $subtotal > 0 ? 1000.0 : 0.0;
        $grandTotal = $subtotal + $shippingFee;

        $points = (float)($user->loyalty_point ?? 0.0);
        $exchangeRate = (float)(\App\Models\BusinessSetting::where('type', 'loyalty_point_exchange_rate')->first()?->value ?: 1);
        $minPoint = (int)(\App\Models\BusinessSetting::where('type', 'loyalty_point_minimum_point')->first()?->value ?: 100);
        $equivalentNaira = $exchangeRate > 0 ? ($points / $exchangeRate) : 0.0;
        $loyaltyUpsell = null;
        if ($points >= $minPoint) {
            $loyaltyUpsell = "💡 You have " . number_format($points) . " Loyalty Points (worth ₦" . number_format($equivalentNaira, 2) . "). You can convert them to instant wallet funds to pay for this order!";
        }

        return [
            'item_count' => count($items),
            'items' => $items,
            'subtotal' => $subtotal,
            'shipping_fee' => $shippingFee,
            'grand_total' => $grandTotal,
            'formatted_total' => '₦' . number_format($grandTotal, 2),
            'loyalty_points_available' => $points,
            'loyalty_upsell' => $loyaltyUpsell,
        ];
    }

    /**
     * [AI] Clears customer cart.
     */
    public static function clearCart(string $phone): bool
    {
        $user = self::getOrCreateCustomer($phone);
        return Cart::where('customer_id', $user->id)->delete() > 0;
    }

    /**
     * [AI] Validates and applies coupon code.
     */
    public static function applyCoupon(string $phone, string $couponCode): array
    {
        $user = self::getOrCreateCustomer($phone);
        $cartSummary = self::getCartSummary($phone);

        if ($cartSummary['subtotal'] <= 0) {
            return ['status' => false, 'message' => 'Your cart is empty. Add items before applying coupon.'];
        }

        $coupon = Coupon::where('code', trim($couponCode))
            ->where('status', 1)
            ->whereDate('start_date', '<=', now())
            ->whereDate('expire_date', '>=', now())
            ->first();

        if (!$coupon) {
            return ['status' => false, 'message' => 'Invalid or expired coupon code.'];
        }

        if ($cartSummary['subtotal'] < $coupon->min_purchase) {
            return [
                'status' => false,
                'message' => "Minimum purchase of ₦" . number_format($coupon->min_purchase, 2) . " required for this coupon."
            ];
        }

        $discount = 0.0;
        if ($coupon->coupon_type === 'discount_on_purchase') {
            if ($coupon->discount_type === 'percent') {
                $discount = ($cartSummary['subtotal'] * (float)$coupon->discount) / 100;
                if (!empty($coupon->max_discount) && $discount > (float)$coupon->max_discount) {
                    $discount = (float)$coupon->max_discount;
                }
            } else {
                $discount = (float)$coupon->discount;
            }
        } elseif ($coupon->coupon_type === 'free_delivery') {
            $discount = $cartSummary['shipping_fee'];
        }

        $newTotal = max(0, $cartSummary['grand_total'] - $discount);

        return [
            'status' => true,
            'coupon_code' => $coupon->code,
            'discount_amount' => $discount,
            'formatted_discount' => '-₦' . number_format($discount, 2),
            'new_grand_total' => $newTotal,
            'formatted_new_total' => '₦' . number_format($newTotal, 2),
            'message' => "Coupon {$coupon->code} applied successfully! You saved ₦" . number_format($discount, 2),
        ];
    }

    /**
     * [AI] Atomically places the formal order in MySQL and generates 6-digit Delivery OTP.
     */
    public static function placeOrder(
        string $phone,
        string $deliveryAddress,
        string $paymentMethod = 'cash_on_delivery',
        ?string $customerName = null,
        ?string $couponCode = null
    ): array {
        $formattedPhone = SMSModule::formatNigerianPhone($phone);
        $user = self::getOrCreateCustomer($formattedPhone, $customerName, $deliveryAddress);

        $cartSummary = self::getCartSummary($formattedPhone);
        if ($cartSummary['item_count'] <= 0) {
            return ['status' => false, 'message' => 'Your cart is empty. Please add items to order.'];
        }

        // Apply coupon discount if provided
        $discountAmount = 0.0;
        if (!empty($couponCode)) {
            $couponResult = self::applyCoupon($formattedPhone, $couponCode);
            if ($couponResult['status']) {
                $discountAmount = (float)$couponResult['discount_amount'];
            }
        }

        $finalOrderAmount = max(0, $cartSummary['grand_total'] - $discountAmount);

        try {
            return DB::transaction(function () use ($user, $formattedPhone, $deliveryAddress, $paymentMethod, $cartSummary, $discountAmount, $finalOrderAmount, $couponCode) {
                // [AI] Universal 6-digit Cryptographic Delivery & Pickup OTP (CSPRNG)
                $deliveryOtp = (string) random_int(100000, 999999);
                $pickupOtp = (string) random_int(100000, 999999);

                $shippingAddressData = [
                    'contact_person_name' => trim($user->f_name . ' ' . $user->l_name),
                    'address' => $deliveryAddress,
                    'city' => 'Uyo',
                    'zip' => '520001',
                    'phone' => $formattedPhone,
                    'created_at' => now(),
                ];

                // 1. Create Core Order Record
                $order = Order::create([
                    'customer_id' => $user->id,
                    'is_guest' => 0,
                    'customer_type' => 'customer',
                    'payment_status' => 'unpaid',
                    'order_status' => 'pending',
                    'payment_method' => $paymentMethod,
                    'order_amount' => $finalOrderAmount,
                    'discount_amount' => $discountAmount,
                    'discount_type' => !empty($couponCode) ? 'coupon_discount' : null,
                    'coupon_code' => $couponCode,
                    'shipping_cost' => $cartSummary['shipping_fee'],
                    'verification_code' => $deliveryOtp, // Universal 6-Digit Delivery OTP
                    'pickup_verification_code' => $pickupOtp, // 6-Digit Vendor Pickup OTP
                    'shipping_address_data' => json_encode($shippingAddressData),
                    'order_note' => 'Order placed via WhatsApp AI Assistant',
                    'order_type' => 'default_type',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // 2. Insert Order Details & Decrement Inventory
                foreach ($cartSummary['items'] as $item) {
                    $product = Product::lockForUpdate()->find($item['product_id']);
                    
                    OrderDetail::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'seller_id' => $product ? $product->user_id : 1,
                        'product_details' => json_encode($product ?? []),
                        'qty' => $item['qty'],
                        'price' => $item['unit_price'],
                        'tax' => 0,
                        'discount' => 0,
                        'variant' => $item['variant'],
                        'delivery_status' => 'pending',
                        'payment_status' => 'unpaid',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Atomically decrement stock
                    if ($product) {
                        $product->decrement('current_stock', $item['qty']);
                    }
                }

                // 3. Clear Cart in Database
                Cart::where('customer_id', $user->id)->delete();

                // 4. Generate Paystack link if payment method requires online payment
                $paystackUrl = url("/pay/order/{$order->id}");

                // 5. [AI] Trigger Automated WhatsApp Alert to Vendors
                WhatsAppAutomationWorkflow::triggerVendorNewOrderAlert($order);

                $isSelfPickup = stripos($deliveryAddress, 'pickup') !== false
                    || ($order->order_type ?? '') === 'pickup'
                    || ($order->order_type ?? '') === 'self_pickup';

                return [
                    'status' => true,
                    'order_id' => $order->id,
                    'order_amount' => $finalOrderAmount,
                    'formatted_amount' => '₦' . number_format($finalOrderAmount, 2),
                    'delivery_otp' => $deliveryOtp,
                    'pickup_otp' => $pickupOtp,
                    'handover_otp' => $isSelfPickup ? $pickupOtp : $deliveryOtp,
                    'is_self_pickup' => $isSelfPickup,
                    'delivery_address' => $deliveryAddress,
                    'payment_method' => $paymentMethod,
                    'paystack_url' => $paystackUrl,
                    'message' => "Order #{$order->id} placed successfully!",
                ];
            });
        } catch (Exception $e) {
            Log::error('[AI WhatsApp Order Placement Exception] ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Failed to place order. Please try again or contact support.'
            ];
        }
    }

    /**
     * [AI] Fetches live verified wallet balance and loyalty status for WhatsApp shopper.
     */
    public static function getWalletSummary(string $phone): array
    {
        $user = self::getOrCreateCustomer($phone);
        $balance = (float)($user->wallet_balance ?? 0.0);

        return [
            'status' => true,
            'user_id' => $user->id,
            'customer_name' => trim($user->f_name . ' ' . $user->l_name),
            'wallet_balance' => $balance,
            'formatted_balance' => '₦' . number_format($balance, 2),
        ];
    }

    /**
     * [AI] Customer Wallet Decommissioned: Wallet top-up is permanently blocked.
     */
    public static function generateWalletTopUpLink(string $phone, float $amount): array
    {
        return [
            'status' => false,
            'message' => 'Customer wallet top-up is permanently decommissioned in Victorious MARKET. Please pay online via Paystack, OPay, or select Pay at Pickup.',
        ];
    }

    /**
     * [AI] Customer Wallet Decommissioned: 1-Click WhatsApp Wallet Checkout is permanently decommissioned.
     */
    public static function payWithWallet(string $phone, ?int $orderId = null): array
    {
        return [
            'status' => false,
            'message' => 'Customer wallet payment is permanently decommissioned in Victorious MARKET. Please pay online via Paystack, OPay, or select Pay at Pickup.',
        ];
    }

    /**
     * [AI] Fetch customer loyalty points balance and recent transaction history.
     */
    public static function getLoyaltySummary(string $phone): array
    {
        $user = self::getOrCreateCustomer($phone);
        $points = (float)($user->loyalty_point ?? 0.0);

        $exchangeRate = (float)(\App\Models\BusinessSetting::where('type', 'loyalty_point_exchange_rate')->first()?->value ?: 1);
        $minPoint = (int)(\App\Models\BusinessSetting::where('type', 'loyalty_point_minimum_point')->first()?->value ?: 100);
        $equivalentNaira = $exchangeRate > 0 ? ($points / $exchangeRate) : 0.0;

        // Fetch recent point transactions
        $recentTransactions = \App\Models\LoyaltyPointTransaction::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->take(3)
            ->get();

        $history = [];
        foreach ($recentTransactions as $tx) {
            $isCredit = ($tx->credit > 0);
            $history[] = [
                'type' => $isCredit ? '➕ Earned' : '➖ Redeemed',
                'points' => $isCredit ? "+{$tx->credit} pts" : "-{$tx->debit} pts",
                'reason' => ucwords(str_replace('_', ' ', $tx->transaction_type)),
                'date' => $tx->created_at->format('d M Y'),
            ];
        }

        return [
            'status' => true,
            'loyalty_points' => $points,
            'equivalent_naira' => $equivalentNaira,
            'formatted_naira' => '₦' . number_format($equivalentNaira, 2),
            'exchange_rate_text' => "{$exchangeRate} Points = ₦1.00",
            'min_conversion_point' => $minPoint,
            'can_convert' => ($points >= $minPoint),
            'history' => $history,
            'message' => "🌟 *Your Loyalty Points:* *" . number_format($points) . " Points* (worth *₦" . number_format($equivalentNaira, 2) . "* in wallet credit).\n" . ($points >= $minPoint ? "👉 You can convert your points to instant wallet funds anytime!" : "👉 Minimum points required for wallet conversion: {$minPoint} points."),
        ];
    }

    /**
     * [AI] Convert loyalty points to instant wallet funds with pessimistic lock.
     */
    public static function convertLoyaltyToWallet(string $phone, ?int $pointsToConvert = null): array
    {
        $user = self::getOrCreateCustomer($phone);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($user, $pointsToConvert) {
            $lockedUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();
            $currentPoints = (float)($lockedUser->loyalty_point ?? 0.0);

            $exchangeRate = (float)(\App\Models\BusinessSetting::where('type', 'loyalty_point_exchange_rate')->first()?->value ?: 1);
            $minPoint = (int)(\App\Models\BusinessSetting::where('type', 'loyalty_point_minimum_point')->first()?->value ?: 100);

            $pts = $pointsToConvert ? min((int)$pointsToConvert, (int)$currentPoints) : (int)$currentPoints;

            if ($pts < $minPoint || $currentPoints < $minPoint) {
                return [
                    'status' => false,
                    'message' => "❌ You need at least {$minPoint} loyalty points to convert to wallet funds. (You currently have {$currentPoints} points).",
                ];
            }

            $nairaCredit = $exchangeRate > 0 ? ($pts / $exchangeRate) : 0.0;

            // Execute unified CustomerManager transaction
            $walletTx = \App\Utils\CustomerManager::create_wallet_transaction($lockedUser->id, $pts, 'loyalty_point', 'point_to_wallet');
            if ($walletTx) {
                \App\Utils\CustomerManager::create_loyalty_point_transaction($lockedUser->id, $walletTx->transaction_id, $pts, 'point_to_wallet');
            }

            $refreshedUser = $lockedUser->fresh();
            $creditedNaira = (float)($walletTx?->credit ?? $nairaCredit);

            return [
                'status' => true,
                'converted_points' => $pts,
                'credited_naira' => $creditedNaira,
                'formatted_credit' => '₦' . number_format($creditedNaira, 2),
                'new_wallet_balance' => (float)$refreshedUser->wallet_balance,
                'formatted_new_wallet' => '₦' . number_format($refreshedUser->wallet_balance, 2),
                'remaining_points' => (float)$refreshedUser->loyalty_point,
                'message' => "🎉 *Conversion Successful!*\n\n⭐ *Points Converted:* " . number_format($pts) . " Points\n💰 *Wallet Credited:* ₦" . number_format($creditedNaira, 2) . "\n💳 *New Wallet Balance:* ₦" . number_format($refreshedUser->wallet_balance, 2) . "\n\nYou can use your wallet balance to pay for orders with 0% gateway fees!",
            ];
        });
    }
}


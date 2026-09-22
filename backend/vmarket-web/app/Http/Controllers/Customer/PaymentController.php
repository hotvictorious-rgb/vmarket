<?php

namespace App\Http\Controllers\Customer;

use App\Exceptions\IdempotencyConflictException;
use App\Exceptions\InvalidCartException;
use App\Exceptions\InvalidPaymentStateException;
use App\Exceptions\PaymentInitializationException;
use App\Exceptions\ProductUnavailableException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ShippingAddress;
use App\Models\User;
use App\Services\DeliveryCheckoutIntentService;
use App\Services\DeliveryPaymentInitializationService;
use App\Utils\Helpers;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * [AI] PaymentController — Canonical V1 Customer Payment Entry Point
 *
 * Consumers:
 *   - Customer Mobile App: POST /api/v1/digital-payment   (payment_request_from = 'app')
 *   - Web Storefront:      POST /customer/web-payment-request
 *
 * This controller is the public API surface that both the Flutter Customer App and the
 * Web Storefront hit to initiate a marketplace delivery checkout payment.
 * Internally it delegates ALL financial logic to:
 *   1. DeliveryCheckoutIntentService::createCheckoutIntent()  → freezes the cart amount server-side
 *   2. DeliveryPaymentInitializationService::initializePayment() → creates PaymentRequest + Paystack authorization_url
 *
 * The legacy flow (Payment trait → generate_link → digital_payment_success hook) has been removed.
 * Guest checkout (is_guest = 1) is not supported in V1 — authentication is required.
 */
class PaymentController extends Controller
{
    public function __construct(
        protected DeliveryCheckoutIntentService $intentService,
        protected DeliveryPaymentInitializationService $paymentService
    ) {}

    /**
     * Entry point for delivery checkout payment.
     * API (app): returns JSON { redirect_link: string }
     * Web:       redirects to Paystack authorization URL
     *
     * [AI] Clients: Customer Mobile App, Web Storefront
     */
    public function payment(Request $request): JsonResponse|Redirector|RedirectResponse
    {
        $isApp = in_array($request->input('payment_request_from'), ['app']);

        // ── 1. Reject guest checkout — V1 requires authenticated customers ──────────────────
        $isGuest = (bool) $request->input('is_guest', false);
        if ($isGuest) {
            $err = ['code' => 'guest-not-supported', 'message' => 'Guest checkout is not supported. Please log in to place an order.'];
            if ($isApp) {
                return response()->json(['errors' => [$err]], 403);
            }
            Toastr::error(translate('Please log in to place an order.'));
            return redirect()->route('customer.auth.login');
        }

        // ── 2. Resolve Authenticated Customer ────────────────────────────────────────────────
        // Try API guard first (mobile app), then web customer guard
        $customer = auth('api')->user() ?? auth('customer')->user();
        if (!$customer) {
            $err = ['code' => 'unauthenticated', 'message' => 'Authentication required.'];
            if ($isApp) {
                return response()->json(['errors' => [$err]], 401);
            }
            Toastr::error(translate('Please log in to place an order.'));
            return redirect()->route('customer.auth.login');
        }

        // ── 3. Validate Core Payment Request Params ──────────────────────────────────────────
        $validator = Validator::make($request->all(), [
            'payment_method'   => 'required|string',
            'payment_platform' => 'required|string',
            'address_id'       => 'required',
        ]);

        if ($validator->fails()) {
            if ($isApp) {
                return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
            }
            foreach (Helpers::validationErrorProcessor($validator) as $v) {
                Toastr::error(translate($v['message']));
            }
            return back();
        }

        // Only Paystack is authorized in V1
        if (strtolower($request->input('payment_method')) !== 'paystack') {
            $err = ['code' => 'unsupported-gateway', 'message' => 'Only Paystack payments are accepted.'];
            if ($isApp) {
                return response()->json(['errors' => [$err]], 422);
            }
            Toastr::error(translate('Only Paystack payments are accepted.'));
            return back();
        }

        // ── 4. Resolve Shipping Address (IDOR-protected) ─────────────────────────────────────
        $addressId = (int) ($request->input('address_id') ?? 0);
        $shippingAddress = ShippingAddress::where('id', $addressId)
            ->where('customer_id', $customer->id)
            ->where('is_guest', 0)
            ->first();

        if (!$shippingAddress) {
            $err = ['code' => 'invalid-address', 'message' => 'Shipping address not found or does not belong to your account.'];
            if ($isApp) {
                return response()->json(['errors' => [$err]], 422);
            }
            Toastr::error(translate('Shipping address not found.'));
            return back();
        }

        $billingAddressId = (int) ($request->input('billing_address_id') ?? 0);
        $billingAddress = null;
        if ($billingAddressId > 0) {
            $billingAddress = ShippingAddress::where('id', $billingAddressId)
                ->where('customer_id', $customer->id)
                ->where('is_guest', 0)
                ->first();
        }

        // ── 5. Build Idempotency Key ─────────────────────────────────────────────────────────
        // Allows retry-safe checkout attempts. Uses explicit key if provided, otherwise derives one.
        $idempotencyKey = $request->input('idempotency_key');
        if (empty($idempotencyKey) || !preg_match('/^[A-Za-z0-9_\-\:]{8,64}$/', $idempotencyKey)) {
            // Derive a stable key: customer + address + unix minute (allows retries within the same minute)
            $idempotencyKey = 'CHK-' . $customer->id . '-' . $addressId . '-' . floor(time() / 60);
        }

        // [AI] Victorious MARKET V1: Cashback is the sole customer order-reduction mechanism.
        // Coupon codes and referral discounts are decommissioned from checkout.
        $useCashback = filter_var($request->input('use_cashback', false), FILTER_VALIDATE_BOOLEAN);

        // ── 6. Phase 1: Create / Replay Frozen CheckoutIntent ────────────────────────────────
        try {
            $intent = $this->intentService->createCheckoutIntent(
                customer: $customer,
                idempotencyKey: $idempotencyKey,
                shippingAddress: $shippingAddress,
                billingAddress: $billingAddress,
                useCashback: $useCashback,
                cartItemIds: null, // Use all checked cart items
            );
        } catch (IdempotencyConflictException $e) {
            $err = ['code' => 'idempotency-conflict', 'message' => 'Checkout parameters changed mid-session. Please restart checkout.'];
            if ($isApp) {
                return response()->json(['errors' => [$err]], 409);
            }
            Toastr::error(translate('Checkout parameters changed. Please restart checkout.'));
            return redirect()->route('shop-cart');
        } catch (InvalidCartException | ProductUnavailableException $e) {
            $err = ['code' => 'cart-error', 'message' => $e->getMessage()];
            if ($isApp) {
                return response()->json(['errors' => [$err]], 422);
            }
            Toastr::error(translate($e->getMessage()));
            return redirect()->route('shop-cart');
        } catch (\Exception $e) {
            Log::error('[AI] PaymentController::payment - CheckoutIntent creation failed', [
                'customer_id' => $customer->id,
                'error'       => $e->getMessage(),
            ]);
            $err = ['code' => 'server-error', 'message' => 'Unable to create checkout agreement. Please try again.'];
            if ($isApp) {
                return response()->json(['errors' => [$err]], 500);
            }
            Toastr::error(translate('Something went wrong. Please try again.'));
            return back();
        }

        // ── 7. Phase 2: Initialize Paystack Payment Attempt ──────────────────────────────────
        try {
            $result = $this->paymentService->initializePayment(
                customer: $customer,
                intentInput: $intent,
                ttlMinutes: 30,
            );
        } catch (InvalidCartException | InvalidPaymentStateException $e) {
            $err = ['code' => 'payment-state-error', 'message' => $e->getMessage()];
            if ($isApp) {
                return response()->json(['errors' => [$err]], 409);
            }
            Toastr::error(translate($e->getMessage()));
            return redirect()->route('shop-cart');
        } catch (PaymentInitializationException $e) {
            $err = ['code' => 'gateway-error', 'message' => 'Payment gateway unavailable. Please try again shortly.'];
            if ($isApp) {
                return response()->json(['errors' => [$err]], 502);
            }
            Toastr::error(translate('Payment gateway unavailable. Please try again.'));
            return back();
        } catch (\Exception $e) {
            Log::error('[AI] PaymentController::payment - PaymentInitialization failed', [
                'customer_id' => $customer->id,
                'intent_id'   => $intent->id ?? null,
                'error'       => $e->getMessage(),
            ]);
            $err = ['code' => 'server-error', 'message' => 'Payment initialization failed. Please try again.'];
            if ($isApp) {
                return response()->json(['errors' => [$err]], 500);
            }
            Toastr::error(translate('Payment initialization failed. Please try again.'));
            return back();
        }

        // ── 8. Extract Authorization URL and Return ───────────────────────────────────────────
        $status = $result['status'] ?? 'unknown';

        if ($status === 'success') {
            $redirectLink = $result['authorization_url'];
        } elseif ($status === 'pending_on_gateway') {
            // Existing attempt recovered from Paystack — reuse the existing authorization URL
            $additionalData = json_decode($result['payment_request']->additional_data ?? '{}', true);
            $redirectLink = $additionalData['authorization_url'] ?? null;
        } else {
            // ambiguous_transport or unknown
            Log::warning('[AI] PaymentController::payment - Ambiguous Paystack initialization status', [
                'status'      => $status,
                'customer_id' => $customer->id,
            ]);
            $err = ['code' => 'gateway-unavailable', 'message' => 'Payment gateway temporarily unavailable. Please retry.'];
            if ($isApp) {
                return response()->json(['errors' => [$err]], 503);
            }
            Toastr::error(translate('Payment gateway temporarily unavailable. Please retry.'));
            return back();
        }

        if (empty($redirectLink)) {
            $err = ['code' => 'no-redirect-url', 'message' => 'No payment URL received from gateway.'];
            if ($isApp) {
                return response()->json(['errors' => [$err]], 502);
            }
            Toastr::error(translate('No payment URL received. Please try again.'));
            return back();
        }

        // Return redirect link — same contract as the legacy payment() method
        if ($isApp) {
            return response()->json([
                'redirect_link' => $redirectLink,
                'new_user'      => 0,
            ], 200);
        }

        return redirect($redirectLink);
    }

    // ──────────────────────────────────────────────────────────────────────────────────────────
    // Response Handlers
    // ──────────────────────────────────────────────────────────────────────────────────────────

    public function success(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Payment succeeded'], 200);
    }

    public function fail(): JsonResponse
    {
        return response()->json(['message' => 'Payment failed'], 403);
    }

    public function web_payment_success(Request $request)
    {
        if ($request->flag == 'success') {
            if (session()->has('payment_mode') && session('payment_mode') == 'app') {
                return response()->json(['message' => 'Payment succeeded'], 200);
            } else {
                $data = [];
                if (!empty($request?->token)) {
                    $decoded = $request->token ? base64_decode($request->token) : '';
                    $parts = explode('&&', $decoded);
                    foreach ($parts as $part) {
                        [$key, $value] = explode('=', $part);
                        $data[$key] = $value;
                    }
                }
                $transactionReference = $data['transaction_reference'] ?? null;
                if ($transactionReference) {
                    $orderIds = Order::where(['transaction_ref' => $transactionReference])->get()->pluck('id')->toArray();
                    session(['order_success_ids' => $orderIds]);
                }

                Toastr::success(translate('Payment_success'));
                session()->forget('newCustomerRegister');
                if (auth()->guard('customer')->check()) {
                    return redirect()->route('account-oder');
                } else {
                    return redirect()->route('home');
                }
            }
        } else {
            if (session()->has('payment_mode') && session('payment_mode') == 'app') {
                return response()->json(['message' => 'Payment failed'], 403);
            } else {
                Toastr::error(translate('Payment_failed') . '!');
                return redirect(url('/'));
            }
        }
    }
}

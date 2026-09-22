# Phase 1: Customer App ↔ Backend Impact Map

## CHECKOUT

**Customer App**
- `lib/features/checkout/screens/checkout_screen.dart`
- `lib/features/checkout/screens/pickup_payment_screen.dart`
- `lib/features/checkout/controllers/checkout_controller.dart`
- `lib/features/checkout/domain/services/checkout_service.dart`
- `lib/features/checkout/domain/repositories/checkout_repository.dart`

**API Endpoints**
- `POST /api/v1/checkout/intent` (Phase 1)
- `POST /api/v1/checkout/intent/{orderGroupId}/pay` (Phase 2)
**API Endpoints:**
- `POST /api/v1/checkout/intent` (Phase 1: Create frozen CheckoutIntent snapshot)
- `POST /api/v1/checkout/intent/{orderGroupId}/pay` (Phase 2: Initialize Paystack gateway attempt)
- `POST /api/v1/digital-payment` (Legacy compatibility shim; calls DeliveryCheckoutIntentService internally)

**Backend Implementation:**
- `App\Http\Controllers\RestAPI\v1\customer\DeliveryCheckoutIntentController`
- `App\Services\DeliveryCheckoutIntentService`
- `App\Services\DeliveryPaymentInitializationService`
- `App\Models\CheckoutIntent`, `App\Models\PaymentRequest`

**Audit Findings & Discrepancies:**
- **ALIGNED:** Two-phase checkout intent creation is wired (`checkout_controller.dart:369` -> `POST /api/v1/checkout/intent`).
- **DISCREPANCY:** In `digital_payment_order_place_screen.dart:77`, payment success detection still relies on WebView URL string sniffing (`url.contains('success') && url.contains('token')`) rather than an explicit backend REST order status check.

---

## 2. FULFILLMENT AVAILABILITY & DELIVERY LANES

**Customer App Files:**
- `lib/features/checkout/controllers/checkout_controller.dart` (`checkFulfillmentAvailability` method: lines 332-354)
- `lib/features/checkout/domain/models/fulfillment_availability_model.dart`
- `lib/features/cart/screens/cart_screen.dart` (Lines 403–420: Legacy shipping gate)
- `lib/features/shipping/controllers/shipping_controller.dart` (Legacy 6valley shipping)

**API Endpoints:**
- `POST /api/v1/fulfillment/availability`
- `POST /api/v1/fulfillment/delivery-fee`

**Backend Implementation:**
- `App\Http\Controllers\RestAPI\v1\customer\FulfillmentAvailabilityController`
- `App\Services\FulfillmentAvailabilityService`
- Models: `DeliveryLane`, `Shop` (pickup settings)

**Audit Findings & Discrepancies:**
- **CRITICAL MISMATCH:** In `cart_screen.dart:403–420`, the cart screen still forces the customer to pick a legacy 6valley shipping method (`ShippingMethodBottomSheetWidget`) before proceeding to checkout.
- **CRITICAL MISMATCH:** In `checkout_controller.dart:332`, `checkFulfillmentAvailability` is defined but **NEVER CALLED by any screen** in the Customer App. The UI displays legacy `widget.shippingFee` instead of querying the live directional lane fee.
- **API MISMATCH:** `FulfillmentAvailabilityController.php:40` requires `shop_id` in request validation, whereas Spec Section 19 specifies that client sends only customer-owned info: `{"address_id": 123, "cart_item_ids": [...]}`.

---

## 3. CANONICAL GEOGRAPHY & ADDRESS SYSTEM

**Customer App Files:**
- `lib/features/address/screens/add_new_address_screen.dart` (Country -> State -> LGA cascade)
- `lib/features/address/screens/saved_address_list_screen.dart`
- `lib/features/address/controllers/address_controller.dart` (`getCountries`, `getStates`, `getLgas`)
- `lib/features/address/domain/models/address_model.dart`, `geography_models.dart`
- `lib/features/address/domain/services/address_service.dart`
- `lib/features/address/domain/repositories/address_repository.dart`

**API Endpoints:**
- `GET /api/v1/geography/countries`
- `GET /api/v1/geography/states/{country_id}`
- `GET /api/v1/geography/lgas/{state_id}`
- `POST /api/v1/customer/address/add`
- `GET /api/v1/customer/address/list`
- `POST /api/v1/customer/address/update`
- `DELETE /api/v1/customer/address/`

**Backend Implementation:**
- `App\Http\Controllers\RestAPI\v1\GeographyController`
- `App\Http\Controllers\RestAPI\v1\CustomerController`
- Models: `Country`, `State`, `Lga`, `ShippingAddress`
- Validation: `App\Rules\ValidLgaForState`

**Audit Findings:**
- **ALIGNED:** Customer App uses canonical `Country -> State -> LGA` dropdowns. Backend strictly validates LGA belongs to State. Zero-trust IDOR ownership verified (`customer_id = auth('api')->id()`).

---

## 4. AUTHENTICATION & SECURE TOKEN STORAGE

**Customer App Files:**
- `lib/features/auth/screens/auth_screen.dart`, `login_screen.dart`, `otp_registration_screen.dart`
- `lib/features/auth/controllers/auth_controller.dart`
- `lib/features/auth/domain/repositories/auth_repository.dart`
- `lib/services/storage_service.dart` (Backed by `FlutterSecureStorage`)

**API Endpoints:**
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/register`
- `POST /api/v1/auth/check-phone`, `check-email`, `verify-otp`
- `GET /api/v1/auth/logout`

**Backend Implementation:**
- `App\Http\Controllers\RestAPI\v1\auth\CustomerAPIAuthController`
- `App\Http\Controllers\RestAPI\v1\auth\PassportAuthController`
- Model: `User`

**Audit Findings:**
- **ALIGNED:** 6-digit cryptographic OTP formatting (`rand(100000, 999999)`), exact identity matching, secure token storage via `StorageService` (`FlutterSecureStorage`).

---

## 5. IN-SHOP PICKUP RESERVATIONS

**Customer App Files:**
- `lib/features/checkout/screens/pickup_reservation_success_screen.dart`
- `lib/features/checkout/screens/my_reservations_screen.dart`
- `lib/features/checkout/screens/reservation_detail_screen.dart`
- `lib/features/checkout/screens/pickup_payment_screen.dart`
- `lib/features/checkout/screens/pickup_order_success_screen.dart`
- `lib/features/order_details/widgets/order_payment_info_widget.dart`

**API Endpoints:**
- `POST /api/v1/customer/pickup-reservations`
- `GET /api/v1/customer/pickup-reservations`
- `GET /api/v1/customer/pickup-reservations/{code}`
- `POST /api/v1/customer/pickup-reservations/{code}/pay`

**Backend Implementation:**
- `App\Http\Controllers\Customer\PickupReservationController`
- `App\Services\PickupReservationService`
- `App\Services\PickupPaymentInitializationService`
- `App\Services\PickupOrderSettlementService`
- Model: `PickupReservation`

**Audit Findings & Discrepancies:**
- **ALIGNED:** 24-hr stock hold reservation, `pending_inspection` state, ₦0.00 upfront payment, Paystack payment upon store inspection.
- **DISCREPANCY:** In `order_payment_info_widget.dart:52`, line 52 extracts `final String displayOtp = orderProvider.orders?.verificationCode ?? '';`. If `verificationCode` is null and only `pickupVerificationCode` is populated, the OTP widget renders blank dots (`••••••`).

---

## 6. MULTI-VENDOR & MIXED FULFILLMENT

**Audit Findings & Discrepancies:**
- **CRITICAL MISMATCH:** In `checkout_screen.dart:162`, fulfillment is handled by a single global flag `orderProvider.isPickup`. The user is forced to pick either ALL delivery or ALL pickup. There is no vendor-level segmented fulfillment selector (Spec Section 34).

---

## 7. VICTORIOUS CASHBACK 5% REWARD LEDGER

**Customer App Files:**
- `lib/features/cashback/screens/cashback_screen.dart`
- `lib/features/cashback/controllers/cashback_controller.dart`
- `lib/features/checkout/screens/checkout_screen.dart` (`isUseCashback` toggle)

**API Endpoints:**
- `GET /api/v1/cashback/summary`
- `GET /api/v1/cashback/list`

**Backend Implementation:**
- `App\Http\Controllers\RestAPI\v1\CustomerCashbackController`
- `App\Services\DeliveryCheckoutIntentService` (Cashback redemption bounds)
- Models: `CustomerCashback`, `CashbackRedemption`

**Audit Findings:**
- **ALIGNED:** 5% cashback reward ledger, redemption at checkout via `use_cashback: true`, backend-validated under pessimistic balance locks. Coupons and referral discounts decommissioned in V1.

---

## 8. REMAINING DECOMMISSIONING ACTIONS

| Component | File / Location | Action Required |
|---|---|---|
| Cart Shipping Gate | `User app/lib/features/cart/screens/cart_screen.dart:403–420` | Remove legacy `shippingController` check; navigate directly to checkout. |
| Lane Availability Wiring | `User app/lib/features/checkout/screens/checkout_screen.dart` | Trigger `checkFulfillmentAvailability` on shipping address selection. |
| Mixed Fulfillment Cards | `User app/lib/features/checkout/screens/checkout_screen.dart` | Support per-vendor fulfillment selection (Delivery vs Pickup). |
| Pickup OTP Fallback | `User app/lib/features/order_details/widgets/order_payment_info_widget.dart:52` | Read `pickupVerificationCode` when `verificationCode` is null. |
| Availability Request Schema | `backend/.../FulfillmentAvailabilityController.php:40` | Support customer-only schema: `{"address_id": 123, "cart_item_ids": [...]}`. |

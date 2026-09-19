# VMarket V1 — Commit 7 Security Gate + WhatsApp/AI Detachment Audit

> **Audit Execution Date:** 2026-09-19  
> **Audit Mode:** STRICT READ-ONLY (No code edits, no refactoring, no deletions, no migrations, no git commits)  
> **Audit Scope:** Independent verification of Commit 7 committed codebase, legacy backfill safety, payout/delivery/refund authorities, and comprehensive inventory of WhatsApp and AI components across backend, database, configuration, and Flutter apps.

---

## Overall Audit Executive Summary

| Audit Domain | Assessment Status | Core Finding / Impact |
|---|---|---|
| **Commit 7 Core Engine** | **PASS** | Two-phase delivery codes, 24h clock, delivery fee boundary upon customer receipt, and vendor hold state machine are fully implemented and verified via automated test suites. |
| **Legacy Backfill Safety** | **WARNING** | Migration sets `settled` strictly on `order_transactions.status = 'disburse'`. However, unpaid legacy delivered orders received `held` with `updated_at + 24h`, causing them to immediately qualify as `eligible` for manual admin payout without prior receipt audit. |
| **Payout Authority Scope** | **CRITICAL BLOCKER** | Alternate status updates in `Admin\Order\OrderController::status` and `Vendor\Order\OrderController::status` call `OrderRepository::manageWalletOnOrderStatusChange()`, which completely bypasses the Commit 7 hold gate and directly credits `SellerWallet->total_earning`. |
| **Delivery Status Authorities** | **CRITICAL BLOCKER** | Multiple legacy endpoints (`Admin`, `Vendor Web`, `Vendor App REST API v2/v3`, and `Paystack Webhook`) can set `order_status = 'delivered'` without 6-digit OTP verification, without fulfillment mode check, and without stamping `received_at`. |
| **Refund Authorities Scope** | **CRITICAL BLOCKER** | Web customer refund endpoint (`UserProfileController::submit_refund_details`) was NOT updated in Commit 7: it lacks `isWithinRefundWindow()` checks and does not transition `vendor_settlement_status = 'disputed'`. Furthermore, `Admin\Order\RefundController` deducts refunds from `SellerWallet` even when the order was never disbursed. |
| **Cashback Maturation Scope** | **WARNING** | `CustomerCashbackLedger::creditRewardForOrder()` falls back to `now() + 24h` if `refund_window_expires_at` is null, and does not verify `received_at IS NOT NULL`. |
| **WhatsApp Detachment** | **NOT DONE (ACTIVE)** | WhatsApp remains deeply woven into backend CRM, Meta webhooks, rider automation, order workflows, and SMS fallback. |
| **AI Detachment** | **NOT DONE (ACTIVE)** | Standalone `Modules/AI`, `openai-php/laravel`, Gemini services, prompt templates, and Vendor Flutter App AI screens/controllers remain fully active. |

---

## PART A — COMMIT 7 SECURITY VERIFICATION

### 1. Legacy Settlement Backfill Safety Analysis

**Migration File Inspected:** [`backend/vmarket-web/database/migrations/2026_09_19_000007_add_post_receipt_lifecycle_to_orders_table.php`](file:///c:/Users/SOOQ/Downloads/vmarket/backend/vmarket-web/database/migrations/2026_09_19_000007_add_post_receipt_lifecycle_to_orders_table.php)

#### Exact Migration SQL Traced:
```sql
-- Step 1: Backfill pickup orders
UPDATE orders SET received_at = handed_over_at, refund_window_expires_at = DATE_ADD(handed_over_at, INTERVAL 24 HOUR)
WHERE order_type = 'pickup' AND order_status IN ('delivered', 'returned') AND handed_over_at IS NOT NULL AND received_at IS NULL;

-- Step 2: Backfill delivery orders using updated_at proxy
UPDATE orders SET received_at = updated_at, refund_window_expires_at = DATE_ADD(updated_at, INTERVAL 24 HOUR)
WHERE order_type = 'delivery' AND order_status IN ('delivered', 'returned') AND received_at IS NULL;

-- Step 3: Classify by financial state (settled / refunded)
UPDATE orders SET vendor_settlement_status = 'settled'
WHERE seller_is = 'seller' AND order_status IN ('delivered', 'returned')
  AND id IN (SELECT order_id FROM order_transactions WHERE status = 'disburse');

UPDATE orders SET vendor_settlement_status = 'refunded'
WHERE seller_is = 'seller'
  AND id IN (SELECT order_id FROM order_transactions WHERE status = 'refunded');

-- Step 4: Active third-party orders with backfilled received_at -> held
UPDATE orders SET vendor_settlement_status = 'held'
WHERE seller_is = 'seller' AND received_at IS NOT NULL AND vendor_settlement_status IS NULL;

-- Step 5: Unresolvable legacy orders -> legacy_hold sentinel
UPDATE orders SET vendor_settlement_status = 'legacy_hold'
WHERE seller_is = 'seller' AND order_status IN ('delivered', 'returned')
  AND received_at IS NULL AND vendor_settlement_status IS NULL;
```

#### Assessment: **WARNING**
1. **Settled Classification Proof:** Step 3 **does NOT classify orders as `settled` based on age (`>24h`) alone**. It strictly requires `id IN (SELECT order_id FROM order_transactions WHERE status = 'disburse')`. The walkthrough text previously misstated this; the actual database code is safe regarding `settled`.
2. **The `legacy_hold` Vulnerability Identified:**
   - In Step 2, **every historical delivered order** where `order_type = 'delivery'` had its `received_at` populated with `updated_at`.
   - Consequently, in Step 4, all unpaid historical orders were marked `'held'` instead of `'legacy_hold'`, because their `received_at` was no longer null!
   - Step 5 (`legacy_hold`) therefore matched zero delivery records whose `updated_at` was present.
   - Because `refund_window_expires_at` was backfilled as `updated_at + 24h`, historical orders (e.g., delivered 30 days ago) immediately evaluate `isRefundWindowExpired() === true`.
   - When `VendorSettlementService::evaluateOrderSettlementEligibility()` evaluates them, they transition to `'eligible'`, allowing an admin to disburse funds without proof that the customer actually received the physical goods.

---

### 2. Payout Authority Audit

**Question:** Is `VendorSettlementService::executeManualSettlement()` the only valid vendor payout authority in the codebase?  
**Result:** **CRITICAL BLOCKER — ALTERNATE BYPASS PATHS FOUND**

#### Complete Inventory of Wallet Crediting Authorities:
1. **`VendorSettlementService::executeManualSettlement()` (Authorized V1 Authority):**
   - Location: [`app/Services/VendorSettlementService.php:121`](file:///c:/Users/SOOQ/Downloads/vmarket/backend/vmarket-web/app/Services/VendorSettlementService.php#L121)
   - Evaluates `vendor_settlement_status === 'eligible'`.
   - Enforces pessimistic row locks on `Order`, `SellerWallet`, and `AdminWallet`.
   - Calls `OrderManager::disburseSettledVendorOrder()`.
   - Updates `vendor_settlement_status = 'settled'`, stamps `settled_at`, `settled_by_id`, `settlement_reference`.
2. **`OrderManager::getWalletManageOnOrderStatusChange()` (Gated in Commit 7, but Incomplete):**
   - Location: [`app/Utils/OrderManager.php:151-157`](file:///c:/Users/SOOQ/Downloads/vmarket/backend/vmarket-web/app/Utils/OrderManager.php#L151-L157)
   - Hold gate: `in_array($order->vendor_settlement_status, ['held', 'disputed', 'legacy_hold'], true)`.
   - **Flaw:** If an order reaches status `'eligible'`, this method is **NOT blocked**. Any trigger calling `getWalletManageOnOrderStatusChange()` on an eligible order will directly credit `SellerWallet->total_earning` and set `OrderTransaction->status = 'disburse'`, bypassing `VendorSettlementService`.
3. **`OrderRepository::manageWalletOnOrderStatusChange()` (UNPROTECTED CRITICAL BYPASS):**
   - Location: [`app/Repositories/OrderRepository.php:458-745`](file:///c:/Users/SOOQ/Downloads/vmarket/backend/vmarket-web/app/Repositories/OrderRepository.php#L458-L745)
   - **Bypass Status:** Has **ZERO hold gates**, zero checks on `vendor_settlement_status`, zero OTP checks.
   - Directly executes:
     ```php
     $wallet->total_earning += ($order_amount - $commission) + $order_summary['total_tax'];
     ...
     $transaction->status = 'disburse';
     ```
   - **Live Callers:**
     - `Admin\Order\OrderController::status` ([Line 696](file:///c:/Users/SOOQ/Downloads/vmarket/backend/vmarket-web/app/Http/Controllers/Admin/Order/OrderController.php#L696)): Admin changes status to `delivered` $\rightarrow$ immediately executes legacy disbursement!
     - `Vendor\Order\OrderController::status` ([Line 768](file:///c:/Users/SOOQ/Downloads/vmarket/backend/vmarket-web/app/Http/Controllers/Vendor/Order/OrderController.php#L768)): Vendor changes status to `delivered` $\rightarrow$ immediately executes legacy disbursement!
4. **`RestAPI\v3\seller\OrderController::order_detail_status`:**
   - Location: [`app/Http/Controllers/RestAPI/v3/seller/OrderController.php:711`](file:///c:/Users/SOOQ/Downloads/vmarket/backend/vmarket-web/app/Http/Controllers/RestAPI/v3/seller/OrderController.php#L711)
   - Calls `OrderManager::getWalletManageOnOrderStatusChange($refreshedOrder, 'seller')`.

---

### 3. Delivery Verification Audit

**Trace of Endpoints Updating to `out_for_delivery` and `delivered`:**

| Endpoint / Method | Target Status | OTP / Verification Check | Fulfillment Guard | Stamped Fields | Assessment |
|---|---|---|---|---|---|
| `DeliveryManController::update_order_status` (Phase 1) | `out_for_delivery` | `pickup_verification_code` (hash_equals, 5-attempt limit) | Delivery only (`order_type != 'pickup'`) | `rider_picked_up_at`, `rider_picked_up_by` | **PASS** |
| `DeliveryManController::update_order_status` (Phase 2) | `delivered` | Customer `verification_code` (hash_equals, 5-attempt limit) | Delivery only, requires `out_for_delivery` | `received_at`, `refund_window_expires_at` (+24h) | **PASS** |
| `InShopHandoverController::verifyPickupOtp` (Rider Handshake) | `out_for_delivery` | `pickup_verification_code` | Delivery orders (`!isCustomerPickup`) | `rider_picked_up_at`, `rider_picked_up_by`, `handed_over_at` | **PASS** |
| `WhatsAppRiderService::confirmPickup` | `out_for_delivery` | `pickup_verification_code` | Delivery only | `rider_picked_up_at`, `rider_picked_up_by` | **PASS** (retains WhatsApp) |
| `WhatsAppRiderService::verifyDoorstepOtp` | `delivered` | Customer `verification_code` | Delivery only | `received_at`, `refund_window_expires_at` | **PASS** (retains WhatsApp) |
| `Admin\Order\OrderController::status` | `delivered` | **NONE** | **NONE** | **None** (`received_at = NULL`) | **CRITICAL BLOCKER** |
| `Vendor\Order\OrderController::status` | `delivered` | **NONE** | **NONE** | **None** (`received_at = NULL`) | **CRITICAL BLOCKER** |
| `RestAPI\v3\seller\OrderController::order_detail_status` | `delivered` | **NONE** | **NONE** | **None** (`received_at = NULL`) | **CRITICAL BLOCKER** |
| `RestAPI\v2\seller\OrderController::order_detail_status` | `delivered` | **NONE** | **NONE** | **None** (`received_at = NULL`) | **CRITICAL BLOCKER** |
| `PaystackController` line 622 (Webhook) | `delivered` | **NONE** | **NONE** | **None** (`received_at = NULL`) | **CRITICAL BLOCKER** |

---

### 4. Pickup Handover Audit

**Controller Inspected:** [`app/Http/Controllers/Vendor/Order/InShopHandoverController.php`](file:///c:/Users/SOOQ/Downloads/vmarket/backend/vmarket-web/app/Http/Controllers/Vendor/Order/InShopHandoverController.php)

- **Fulfillment Separation:**
  - `isCustomerPickup` evaluated on `($order->order_type === 'pickup' || $order->delivery_type === 'self_pickup')`.
  - Customer pickup path transitions to `delivered`, sets `handed_over_at`, `received_at`, and `refund_window_expires_at = received_at + 24h`.
  - Delivery orders cannot complete delivery via this path — they only transition to `out_for_delivery` (custody transfer to rider).
  - No delivery fees charged or disbursed on pickup orders.
- **Wallet Hold:**
  - Invokes `OrderManager::getWalletManageOnOrderStatusChange($order, 'delivered')`, which is caught by Commit 7 hold gate (`held`). No immediate SellerWallet disbursement occurs.
- **Minor Finding (WARNING):**
  - Line 116 allows fallback to `$order->verification_code` even if `pickup_verification_code` was distinct.
  - The controller still handles both vendor-to-rider dispatch and customer pickup in the same method.

---

### 5. Refund Audit

**Controllers & Services Inspected:**
- [`app/Http/Controllers/RestAPI/v1/OrderController.php`](file:///c:/Users/SOOQ/Downloads/vmarket/backend/vmarket-web/app/Http/Controllers/RestAPI/v1/OrderController.php)
- [`app/Http/Controllers/Web/UserProfileController.php`](file:///c:/Users/SOOQ/Downloads/vmarket/backend/vmarket-web/app/Http/Controllers/Web/UserProfileController.php)
- [`app/Http/Controllers/Admin/Order/RefundController.php`](file:///c:/Users/SOOQ/Downloads/vmarket/backend/vmarket-web/app/Http/Controllers/Admin/Order/RefundController.php)
- [`app/Services/VendorSettlementService.php`](file:///c:/Users/SOOQ/Downloads/vmarket/backend/vmarket-web/app/Services/VendorSettlementService.php)

#### Findings:
1. **App API Refund (`RestAPI\v1\OrderController.php`):**
   - Properly enforces `$order->isWithinRefundWindow()` ([Line 508](file:///c:/Users/SOOQ/Downloads/vmarket/backend/vmarket-web/app/Http/Controllers/RestAPI/v1/OrderController.php#L508)).
   - Transitions `vendor_settlement_status = 'disputed'` for third-party sellers.
2. **Web Storefront Refund (`Web\UserProfileController.php`):**
   - **CRITICAL BLOCKER:** Line 1010 only checks `$orderDetails->delivery_status !== 'delivered'`. It **does not call `isWithinRefundWindow()`** and does not set `vendor_settlement_status = 'disputed'`. A web shopper can file refunds past 24 hours.
3. **Delivery Fee Invariant:**
   - `VendorSettlementService::executeUndeliveredOrderRefund()` correctly verifies `received_at === null`, issues full merchandise + delivery fee refund, and reverses delivery fee from `AdminWallet.delivery_charge_earned`.
   - In post-delivery returns (`received_at !== null`), delivery fee is retained in `AdminWallet` ($\text{reversal} = \text{₦}0.00$).
4. **Admin Refund Approval Vulnerability (`Admin\Order\RefundController.php`):**
   - **CRITICAL BLOCKER:** When admin clicks `refunded`, lines 147-174 deduct the refund amount from `SellerWallet->total_earning` and may assess seller debt on `collected_cash`, **even if the order was never settled or disbursed to the seller**.
   - Line 178 revokes cashback (`status = 'cancelled'`).

---

### 6. Cashback Audit

**Model & Command Inspected:**
- [`app/Models/CustomerCashbackLedger.php`](file:///c:/Users/SOOQ/Downloads/vmarket/backend/vmarket-web/app/Models/CustomerCashbackLedger.php)
- [`app/Console/Commands/MatureCustomerCashbackCommand.php`](file:///c:/Users/SOOQ/Downloads/vmarket/backend/vmarket-web/app/Console/Commands/MatureCustomerCashbackCommand.php)

#### All Callers of `creditRewardForOrder()`:
1. `OrderManager::getWalletManageOnOrderStatusChange()` ([Line 160](file:///c:/Users/SOOQ/Downloads/vmarket/backend/vmarket-web/app/Utils/OrderManager.php#L160))
2. `DeliveryManController::update_order_status()` ([Line 287](file:///c:/Users/SOOQ/Downloads/vmarket/backend/vmarket-web/app/Http/Controllers/RestAPI/v2/delivery_man/DeliveryManController.php#L287))
3. `InShopHandoverController::verifyPickupOtp()` ([Line 190](file:///c:/Users/SOOQ/Downloads/vmarket/backend/vmarket-web/app/Http/Controllers/Vendor/Order/InShopHandoverController.php#L190))
4. `WhatsAppRiderService::verifyDoorstepOtp()` ([Line 265](file:///c:/Users/SOOQ/Downloads/vmarket/backend/vmarket-web/app/Services/WhatsAppRiderService.php#L265))

#### Assessment: **WARNING**
- **Idempotency:** Protected by `CustomerCashbackLedger::where('order_id', $order->id)->first()`.
- **Receipt Authority Gap:** Line 103 assigns `'available_at' => $order->refund_window_expires_at ?? now()->addHours(24)`. If an order is marked delivered by a legacy endpoint with `received_at = NULL` and `refund_window_expires_at = NULL`, cashback is still created and will mature after 24 hours without proof of receipt.

---

### 7. Cross-Fulfillment Bypass Audit

| Bypass Vector | Tested Flow | Result / Vulnerability | Rating |
|---|---|---|---|
| **Pickup submitted to Delivery endpoint** | DeliveryManController `update_order_status` | Blocked explicitly with 422 ("Pickup orders cannot be fulfilled via rider protocol") | **PASS** |
| **Delivery submitted to Pickup handover** | InShopHandoverController `verifyPickupOtp` | Allowed only for Phase 1 custody transfer (`out_for_delivery`); cannot complete delivery | **PASS** |
| **Direct Web Delivery Bypass** | `Admin\OrderController::status` or `Vendor\OrderController::status` | **Allowed!** Updates status to `delivered` directly without OTP, without `received_at`, and triggers legacy payout! | **CRITICAL BLOCKER** |
| **Direct Seller App API Bypass** | `RestAPI\v3\seller\OrderController::order_detail_status` | **Allowed!** Sets `order_status = 'delivered'` via mobile PUT request without OTP! | **CRITICAL BLOCKER** |
| **Paystack Webhook Bypass** | `PaystackController` line 622 | **Allowed!** Sets `order_status = 'delivered'` on `delivery_payment` webhook! | **CRITICAL BLOCKER** |
| **Wrong Rider Attempt** | DeliveryManController `delivery_man_id != rider->id` | Blocked with 403 / "Order not assigned to your route" | **PASS** |
| **Replay Attack** | Re-submitting valid OTP on delivered order | Blocked: `in_array(order_status, ['delivered', ...])` returns 400 | **PASS** |

---

## PART B — WHATSAPP DETACHMENT AUDIT

### Complete WhatsApp File Inventory & Classification

| File Path | Type | Role & Contents | Classification | Refactor / Destination Plan |
|---|---|---|---|---|
| `app/Services/WhatsAppRiderService.php` | Service | Rider route, stops, cash-in-hand, payouts, pickup/doorstep OTP verification | **B** | **Refactor Before Removal:** Move `getRiderRoute()`, `getCashInHand()`, and `requestPayout()` to `DeliveryManService` / `DeliveryManWithdrawController`. Delivery verification logic is already native in `DeliveryManController`. Delete WhatsApp transport wrapper. |
| `app/Services/WhatsAppAutomationWorkflow.php` | Service | Dispatches WhatsApp notifications on order confirm, assign, deliver | **A** | **Remove directly.** Replace notification calls in controllers with standard database/Firebase events (`OrderStatusEvent`). |
| `app/Services/WhatsAppAiService.php` | Service | Autonomous Gemini AI WhatsApp conversation loop (917 lines) | **A** | **Remove directly.** |
| `app/Services/WhatsAppCrmService.php` | Service | Meta Graph API HTTP client (text, templates, buttons, media) | **A** | **Remove directly.** |
| `app/Services/WhatsAppOrderService.php` | Service | WhatsApp order placement & customer auto-registration | **A** | **Remove directly.** (Orders must originate from Web/Mobile apps only). |
| `app/Services/WhatsAppVendorService.php` | Service | WhatsApp AI merchant sales agent & pro tier upgrades | **A** | **Remove directly.** |
| `app/Services/WhatsAppRoleRouter.php` | Service | Routes inbound WhatsApp messages by role (customer/vendor/rider) | **A** | **Remove directly.** |
| `app/Services/WhatsAppBroadcastService.php` | Service | WhatsApp marketing broadcast campaigns | **A** | **Remove directly.** |
| `app/Services/WhatsAppCustomerTransformer.php` | Service | Formats customer profiles for WhatsApp payload | **A** | **Remove directly.** |
| `app/Http/Controllers/RestAPI/v1/WhatsAppWebhookController.php` | Controller | Inbound Meta WhatsApp webhook handler & verification | **A** | **Remove directly.** |
| `app/Http/Controllers/Admin/WhatsApp/WhatsAppCrmController.php` | Controller | Admin web panel for WhatsApp CRM chat threads | **A** | **Remove directly.** |
| `app/Http/Controllers/Admin/WhatsApp/WhatsAppBroadcastController.php` | Controller | Admin web panel for WhatsApp broadcast campaigns | **A** | **Remove directly.** |
| `app/Http/Controllers/Admin/WhatsApp/WhatsAppAiSettingsController.php` | Controller | Admin web panel for WhatsApp AI prompt & FAQ config | **A** | **Remove directly.** |
| `app/Jobs/SendWhatsAppJob.php` | Job | Queue job dispatching outbound WhatsApp HTTP payloads | **A** | **Remove directly.** |
| `app/Console/Commands/WhatsAppAutoResumeHumanChatsCommand.php` | Command | Auto-resumes dormant agent chats | **A** | **Remove directly.** |
| `app/Models/WhatsAppConversation.php` | Model | Chat sessions | **A** | **Remove directly.** |
| `app/Models/WhatsAppMessage.php` | Model | Chat messages | **A** | **Remove directly.** |
| `app/Models/WhatsAppFaq.php` | Model | WhatsApp AI FAQs | **A** | **Remove directly.** |
| `app/Models/WhatsAppBroadcast.php` | Model | Broadcast campaigns | **A** | **Remove directly.** |
| `app/Models/WhatsAppBroadcastLog.php` | Model | Broadcast delivery logs | **A** | **Remove directly.** |
| `app/Models/WhatsAppCustomerAiProfile.php` | Model | Customer AI relationship profiles | **A** | **Remove directly.** |
| `app/Models/WhatsAppAiCorrection.php` | Model | Admin corrections to AI responses | **A** | **Remove directly.** |
| `app/Utils/SMSModule.php` (lines 21-25, 580-630) | Utility | WhatsApp Meta Cloud API SMS gateway fallback | **B** | **Refactor:** Remove `whatsapp_meta` gateway case; keep Termii, Ebulksms, SmartSMS, KudiSMS. |
| `routes/rest_api/v1/api.php` (lines 51-54) | Route | `webhooks/whatsapp` | **A** | **Remove directly.** |
| `routes/admin/routes.php` (lines 127-144) | Route | `whatsapp-crm.*` route group | **A** | **Remove directly.** |
| `resources/views/admin-views/whatsapp-crm/` (3 files) | Views | Blade templates for CRM, AI settings, broadcasts | **A** | **Remove directly.** |
| `database/migrations/2026_08_22_000000_create_whatsapp_crm_tables.php` | Migration | 5 CRM tables | **D** | **Legacy:** Drop tables via clean down-migration or archive. |
| `database/migrations/2026_08_24_000005_enhance_whatsapp_ai_crm.php` | Migration | AI CRM profile & correction tables | **D** | **Legacy:** Drop tables via clean down-migration or archive. |
| `User app/lib/features/chat/widgets/whatsapp_*.dart` (4 files) | Flutter | In-app chat bubble tail, wallpaper, reaction popup, voice bar | **C** | **False Positive:** Pure Flutter UI styling widgets for internal customer-to-vendor chat. Re-brand / rename cosmetically to `custom_chat_*.dart` if desired, but they have zero WhatsApp network dependency. |
| `Vendor app/lib/features/chat/widgets/whatsapp_*.dart` (4 files) | Flutter | Internal chat styling widgets | **C** | **False Positive:** Same as User app. |
| `Delivery Man App/lib/features/chat/widgets/whatsapp_*.dart` (4 files) | Flutter | Internal chat styling widgets | **C** | **False Positive:** Same as User app. |

---

## PART C — COMPLETE AI DETACHMENT AUDIT

### Complete AI Component Inventory & Classification

| Component | Path / Location | Purpose | Class | Action / Replacement Plan |
|---|---|---|---|---|
| **Module Directory** | `backend/vmarket-web/Modules/AI/` | Entire nwidart module containing AI prompt templates, services, controllers, views | **A** | **Remove directly.** Delete `Modules/AI` directory. |
| **Composer Package** | `composer.json` (`openai-php/laravel`) | OpenAI Laravel package dependency | **A** | **Remove directly.** Remove from `composer.json` and run `composer update --no-dev`. |
| **Config File** | `config/openai.php` | OpenAI API key & organization configuration | **A** | **Remove directly.** |
| **Global Settings Helper** | `app/Utils/settings.php` (line 218) | `getAISetting()` querying `\Modules\AI\app\Models\AISetting` | **A** | **Remove directly.** Strip `getAISetting()` or return `null`. |
| **Vendor Product Controller** | `app/Http/Controllers/Vendor/Product/ProductController.php` (line 49) | Imports `AIUsageManagerService` | **A** | **Refactor:** Remove AI usage manager injection and AI generation action methods. |
| **Config Controller** | `app/Http/Controllers/RestAPI/v1/ConfigController.php` (line 15) | `use Modules\AI\app\Traits\AIModuleManager;` | **A** | **Refactor:** Remove trait and return `'is_ai_features_enabled' => false`. |
| **Receipt Vision OCR** | `app/Services/ReceiptOcrAiService.php` | Gemini 1.5 Flash Vision receipt screenshot parsing | **A** | **Remove directly.** Nigerian bank payments must be verified via direct Paystack webhooks or manual admin review. |
| **Vendor AI Reporting** | `app/Services/VendorAiReportService.php` | Autonomous AI executive sales summary generation | **A** | **Remove directly.** |
| **Customer AI Relationship** | `app/Services/CustomerAiRelationshipEngine.php` | AI memory & relationship dossier engine | **A** | **Remove directly.** |
| **Episodic Memory Service** | `app/Services/EpisodicMemoryService.php` | Customer long-term vector/memory storage | **A** | **Remove directly.** |
| **AI DB Migrations** | `Modules/AI/database/migrations/` (2 files) | `ai_settings`, `ai_setting_logs` tables | **D** | **Remove directly.** |
| **Vendor Flutter App AI Feature** | `Vendor app/lib/features/ai/` (11 files) | Full AI generation controllers, services, repositories, bottom sheets | **A** | **Remove directly.** Delete `features/ai/` folder in Vendor app. |
| **Vendor App Dependency Injection** | `Vendor app/lib/di_container.dart` (lines 9-13) | AI controller & service registration | **A** | **Refactor:** Remove AI registrations. |
| **Vendor App Product Screen UI** | `Vendor app/lib/features/addProduct/screens/add_product_screen.dart`, `add_product_next_screen.dart`, `add_product_seo_screen.dart` | AI generation buttons ("Generate with AI") | **A** | **Refactor:** Remove AI bottom sheet triggers from product add/edit screens. |
| **Vendor App Config Model** | `Vendor app/lib/features/splash/domain/models/config_model.dart` (line 296) | Reads `json['is_ai_features_enabled']` | **A** | **Refactor:** Default to `false` or remove field. |

### False-Positive AI Inventory (DO NOT DELETE)
- Files with "ai" in ordinary words: `EmailTemplateService.php`, `MailService.php`, `OrderDetailsService.php`, `Detail.php`, `Main.dart`, `maintenance_screen.dart`, `painter.dart`, `domain/` directories across Flutter apps.
- Country code `ai` in country/currency tables.

---

## PART D — WHATSAPP × AI INTERSECTION

The following components represent direct intersections of both systems:

1. **`app/Services/WhatsAppAiService.php`:**
   - Connects WhatsApp webhook input to Gemini reasoning loop.
   - Depends on `CustomerAiRelationshipEngine` and `ReceiptOcrAiService`.
2. **`app/Http/Controllers/Admin/WhatsApp/WhatsAppAiSettingsController.php`:**
   - Web panel allowing admins to edit AI system prompts and FAQs for WhatsApp conversations.
3. **`app/Models/WhatsAppCustomerAiProfile.php` & `WhatsAppAiCorrection.php`:**
   - Store conversational AI profiles, sentiment, and admin corrections for WhatsApp users.
4. **`app/Services/VendorAiReportService.php`:**
   - Synthesizes vendor analytics using AI prompts and dispatches summaries to vendors via WhatsApp.
5. **`app/Services/EpisodicMemoryService.php`:**
   - Memory service that persists interaction context specifically indexed by WhatsApp phone number.

**Removal Constraint:** These intersection files MUST be deleted in a coordinated single step. Removing WhatsApp without removing `WhatsAppAiService` will leave dangling Gemini loops; removing AI without removing `WhatsAppCrmController` will break views that render AI settings tabs.

---

## PART E — FINAL DEPENDENCY GRAPH & CALLER TRACE

```
[Inbound Meta Webhook]
       │
       ▼
WhatsAppWebhookController
       │
       ▼
WhatsAppRoleRouter
       ├──▶ (Customer) ──▶ WhatsAppAiService ──▶ CustomerAiRelationshipEngine / Gemini
       ├──▶ (Vendor)   ──▶ WhatsAppVendorService
       └──▶ (Rider)    ──▶ WhatsAppRiderService (Route, stops, custody transfer, doorstep OTP)
```

#### Other Callers to Strip Before Deleting WhatsApp:
1. `app/Http/Controllers/Admin/Order/OrderController.php:800`:
   - `\App\Services\WhatsAppAutomationWorkflow::triggerDeliveryManAssignmentAlert($order, $order->deliveryMan);`
2. `app/Http/Controllers/Admin/Customer/BlacklistController.php:120`:
   - `WhatsAppAutomationWorkflow::triggerOrderConfirmedNotification($order);`
3. `app/Utils/SMSModule.php:23`:
   - `whatsapp_meta` gateway case in `send()`.

---

## RECOMMENDED DETACHMENT EXECUTION PLAN (Step-by-Step)

### Phase 1: Remediate Commit 7 Security Gates & Flaws First (Pre-Requisite)
1. **Fix Alternate Delivery Status Endpoints:**
   - In `Admin\Order\OrderController::status`, `Vendor\Order\OrderController::status`, and `RestAPI\v3\seller\OrderController::order_detail_status`:
     - Disallow status transition to `delivered` directly from the web or vendor apps.
     - Mandate that delivery orders can ONLY be marked delivered via `DeliveryManController::update_order_status` with valid Customer Delivery Code (`verification_code`).
     - Mandate that pickup orders can ONLY be completed via `InShopHandoverController::verifyPickupOtp` with valid Customer Handover OTP.
2. **Close the `OrderRepository::manageWalletOnOrderStatusChange()` Bypass:**
   - Add the identical Commit 7 hold gate inside `OrderRepository::manageWalletOnOrderStatusChange()`, or redirect all calls to `OrderManager::getWalletManageOnOrderStatusChange()`.
3. **Fix Web Storefront Refund Window:**
   - In `UserProfileController::submit_refund_details`, add `if (!$order->isWithinRefundWindow()) { ... }` and transition `vendor_settlement_status = 'disputed'`.
4. **Fix Admin Refund Payout Deduction:**
   - In `Admin\Order\RefundController::updateRefundStatus`, check if order was actually settled before deducting from `SellerWallet`. If order is `held` or `disputed`, do not debit `SellerWallet`.

### Phase 2: Migrate Reusable Business Logic from `WhatsAppRiderService`
1. Move `getRiderRoute()`, `getCashInHand()`, and `requestPayout()` to `App\Services\DeliveryManService` or native delivery controllers.
2. Ensure `DeliveryManController` remains the sole native authority for rider actions.

### Phase 3: Clean Backend WhatsApp Removal
1. Strip calls in `OrderController.php`, `BlacklistController.php`, and `SMSModule.php`.
2. Delete WhatsApp controllers (`app/Http/Controllers/Admin/WhatsApp/`, `WhatsAppWebhookController.php`).
3. Delete WhatsApp routes in `routes/admin/routes.php` and `routes/rest_api/v1/api.php`.
4. Delete WhatsApp views in `resources/views/admin-views/whatsapp-crm/`.
5. Delete WhatsApp services, jobs, commands, and models.

### Phase 4: Clean Backend AI Removal
1. Strip `getAISetting()` from `app/Utils/settings.php`.
2. Clean `ProductController.php` and `ConfigController.php`.
3. Delete `Modules/AI` directory.
4. Delete `config/openai.php`.
5. Remove `openai-php/laravel` from `composer.json`.
6. Delete AI services (`ReceiptOcrAiService.php`, `CustomerAiRelationshipEngine.php`, `VendorAiReportService.php`, `EpisodicMemoryService.php`).

### Phase 5: Clean Flutter Vendor App AI Removal
1. Delete `Vendor app/lib/features/ai/`.
2. Remove AI injections in `Vendor app/lib/di_container.dart` and `main.dart`.
3. Remove AI bottom sheet UI references from `add_product_screen.dart`, `add_product_next_screen.dart`, and `add_product_seo_screen.dart`.

### Phase 6: Post-Removal Test Suite Execution
1. Re-run syntax validation (`php -l`) across all modified backend files.
2. Re-run `test_commit7_post_receipt_lifecycle.php` (72/72 tests).
3. Re-run `v1_transaction_certification.php` (82/82 tests).
4. Run `flutter analyze` on `Vendor app`.

---

## Summary of CRITICAL BLOCKERS Identified

1. **`OrderRepository::manageWalletOnOrderStatusChange()` bypass:** Admin and Vendor web status changes directly credit `SellerWallet` without Commit 7 hold checks.
2. **Alternate `delivered` status updates:** 5 separate controllers/endpoints can mark orders delivered without OTP verification and without stamping `received_at`.
3. **Web Storefront Refund Gap:** `UserProfileController::submit_refund_details` allows refund requests past 24 hours and fails to trigger `'disputed'` settlement status.
4. **Active WhatsApp & AI Infrastructure:** Both WhatsApp CRM/workflows and `Modules/AI` / Flutter AI remain fully present in the codebase.

# VMarket money-path audit — 3 October 2026

**Verdict: CHANGES_REQUIRED. Do not treat the current financial lifecycle as launch-certified.**

Reviewed local checkout on branch `v1`, HEAD `74845a6dbdde22b6b650a0a9ac5f8c2b9450f847`. Findings concern the inspected working tree, rather than a deployment or a clean release snapshot. Other work appeared in the shared checkout during the audit; those changes were not modified or staged by this audit. Source fingerprints are recorded separately below.

Scope: Laravel payment entry points, Paystack callbacks/webhooks, delivery and pickup settlement, order/transaction/wallet records, customer receipt, refunds, cashback maturity/redemption, vendor and rider withdrawals, admin/vendor portals, active storefront theme, and Flutter customer/vendor/rider consumers. Compared code with V1_BUSINESS_RULEBOOK.md and the canonical platform specifications. No product source was changed, no deployment was performed, and no real payment, transfer, or refund was initiated.

## Evidence and limits

Two verification modes were used:

1. Source and caller tracing across routes, controllers, services, models, migrations, Blade, JavaScript, and Dart.
2. Actual PHP controller/service probes against **SQLite `:memory:`**, seeded from the repository's installation schema with narrowly required missing columns added inside that ephemeral database. Provider requests were prohibited with `Http::preventStrayRequests()`. Notifications were faked for the vendor-refund probe. Nothing from these probes was committed to a product database.

Eight isolated probe scenarios produced the results documented below. They prove the sequential logic defects shown, not MySQL isolation behavior, real gateway behavior, or route middleware authorization. The vendor/admin controller calls were made directly with valid actor context; they are not evidence of an unauthenticated exploit.

Existing checks executed:

| Check | Result | What the result establishes |
| --- | --- | --- |
| `php vendor/bin/phpunit tests/Feature/DeliveryFlowLifecycleTest.php --do-not-cache-result`, forced testing/SQLite in memory | 1 test, 32 assertions passed; 1 PHPUnit deprecation | The existing lifecycle test passes. It does not exercise the complete money lifecycle, live gateway, every HTTP route, or concurrent MySQL transactions. |
| `php tests/Unit/PaymentFulfillmentBoundarySecurityTest.php` | 21 passed, 0 failed | This script primarily tests copied closures and mock objects. It does not establish that production controllers enforce those same rules. |
| Isolated actual-code probes | Defects reproduced in withdrawals, tax-bearing hold transactions, partial-refund settlement, vendor refund finalization, provider-reference handling, scheduler registration, and fake cashback refund confirmation | Concrete results appear in findings F01–F08. |

Not performed: live Paystack payment/refund calls, production bank/settlement reconciliation, MySQL concurrent-process races, device/emulator flows, browser click-through of every portal, full Flutter analysis/builds, or a dependency/security audit. Those remain required release checks. No claim is made about whether historical production balances already contain these defects.

## Financial flow map

```text
Customer app / storefront
  -> checked cart + owned address
  -> DeliveryCheckoutIntentService: freeze quote and optionally reserve rewards
  -> DeliveryPaymentInitializationService: persist payment attempt + gateway reference
  -> Paystack hosted payment
  -> verified callback / signed webhook
  -> DeliveryOrderSettlementService
  -> child vendor orders + stock deduction + held OrderTransaction + AdminWallet.pending_amount

Customer pickup reservation
  -> vendor inspection acceptance
  -> PickupPaymentInitializationService: reserve rewards, initialize money payment
     or internally settle a fully reward-funded pickup
  -> PickupOrderSettlementService
  -> pickup order + held transaction + stock deduction

Receipt
  -> rider/customer OTP or vendor/customer pickup OTP
  -> received_at + refund_window_expires_at
  -> pending cashback issuance
  -> maturity and settlement eligibility jobs
     [F03: booted scheduler registers no events]
  -> eligible vendor order
  -> manual settlement
     [F04: no production caller/portal action found]
  -> vendor wallet / withdrawal request / admin payout confirmation

Returns
  -> customer refund request
  -> vendor response and admin review
     [F01: vendor can also falsely set the terminal refunded state]
  -> current admin flow: approval -> external manual refund -> confirmation
  -> PaystackRefundService: balances, restored rewards, refund transactions
     [F05/F07: escrow over-release and false cashback-only confirmation]

Provider refund webhook
  -> signed correlation -> finalizeRefundAccounting
     [F08: gateway reference compared with internal order reference]
```

## Findings

### F01 — P1: vendors can claim a refund was completed without returning money

**Confirmed by actual controller probe. Affects vendor app, vendor web, customer status, cashback and settlement eligibility.**

[Vendor API RefundController](C:/Users/USER/Downloads/vmarket/backend/vmarket-web/app/Http/Controllers/RestAPI/v3/seller/RefundController.php:149) validates `refund_status` only as required, then assigns the submitted value directly to the refund. It permits `refunded` without an accounting service, provider proof, or a recorded manual refund. The [vendor web controller](C:/Users/USER/Downloads/vmarket/backend/vmarket-web/app/Http/Controllers/Vendor/RefundController.php:154) and its request validator also permit terminal `refunded`; the web path additionally sets the item flag to 4.

Probe: an owning vendor submitted `refund_status=refunded` for a pending request. Result: HTTP 200, RefundRequest.status=`refunded`, **zero RefundTransaction records**, and Order.hasUnresolvedRefund()=`false`. The API left the item's flag at 1, producing inconsistent request/item states. No money moved.

Effect: a merchant can close a customer's financial claim falsely. Admin refund handling then rejects the terminal state; cashback/eligibility checks treat the dispute as resolved. The web version also excludes the item from future settlement calculations.

**Repair:** vendor actions should record merchant acknowledgment, evidence, or recommendation only. Permit terminal financial completion exclusively through an authorized platform refund service using verified provider data or validated manual-payment evidence. Use one state machine for both web and API. Reject arbitrary states and impose the transition under a lock.

### F02 — P1: invalid withdrawal state can restore balances repeatedly

**Confirmed by actual controller/service probes. Affects admin vendor payout portal and rider payout accounting. Requires access to the payout action.**

[Admin VendorController.withdrawStatus](C:/Users/USER/Downloads/vmarket/backend/vmarket-web/app/Http/Controllers/Admin/Vendor/VendorController.php:585) has no `approved in [1,2]` validation. It locks a pending request, but any input other than 1 restores earnings and subtracts pending withdrawals, then stores the supplied status. Supplying 0 leaves the request pending and therefore eligible for the same action again.

Actual result for a pending ₦100 request:

| State | Available earnings | Pending withdrawals | Request status |
| --- | ---: | ---: | ---: |
| Before | 0 | 100 | 0 |
| First `approved=0` | 100 | 0 | 0 |
| Second `approved=0` | 200 | -100 | 0 |

[DeliveryManWithdrawService.getUpdateData](C:/Users/USER/Downloads/vmarket/backend/vmarket-web/app/Services/DeliveryManWithdrawService.php:25) similarly subtracts pending withdrawals for any non-1 value and preserves `approved=0`. Actual service execution returned `pending_withdraw=0` with `approved=0` for a ₦100 request. The vendor-side rider request validator requires a status but does not enumerate terminal states.

**Repair:** accept only explicit approve/reject transitions, persist an immutable terminal state, and require affected rows to equal one. Check balance invariants without silently clamping errors. Apply this to vendor and rider web/API payout actions. Add finance-specific authorization and audit events; the vendor payout route currently sits under broad `module:user_section` access.

### F03 — P1: financial schedules are not registered in the booted application

**Confirmed by runtime inspection. Affects all clients indirectly.**

[bootstrap/app.php](C:/Users/USER/Downloads/vmarket/backend/vmarket-web/bootstrap/app.php:23) uses Laravel 12's Application.configure(), which binds `Illuminate\Foundation\Console\Kernel`. The financial schedules reside in [App Console Kernel](C:/Users/USER/Downloads/vmarket/backend/vmarket-web/app/Console/Kernel.php:27), which is not bound by the inspected bootstrap. [routes/console.php](C:/Users/USER/Downloads/vmarket/backend/vmarket-web/routes/console.php:5) has schedules too, but the bootstrap does not load it through withRouting(commands: ...).

Actual boot result: console kernel=`Illuminate\Foundation\Console\Kernel`; resolved Schedule.events()=`[]`.

Effect: running the ordinary scheduler alone will not mature cashback or promote vendor settlements using this application bootstrap. Independently configured operating-system commands could compensate, but no deployed configuration was inspected or established.

**Repair:** register the schedules using the actual Laravel 12 bootstrap; use one authoritative schedule definition. Verify `schedule:list`, run due jobs in staging, and prove deployment cron invokes schedule:run. Add overlap protection and monitoring for delayed jobs.

### F04 — P1: vendor settlement conflates wallet release and external payout

**Correction recorded during repair on 2026-10-03: the original missing-caller assertion was incorrect. The payout semantics and replay defects remain valid.**

[VendorSettlementService.executeManualSettlement](C:/Users/USER/Downloads/vmarket/backend/vmarket-web/app/Services/VendorSettlementService.php:127) is called by `Admin/Order/OrderController::settleVendorOrder`, with the `admin.orders.settle-vendor-order` route and an order-details form. This caller was also present at audit baseline `74845a6d`. Third-party automatic disbursement is deliberately blocked on receipt; the eligibility command promotes `held/disputed` to `eligible` before the admin action.

The existing admin action is reachable and therefore exposes the following conflicting payout meanings. Findings F05/F06 are reachable through this action, rather than latent behind a missing route.

The service describes an **external manual vendor payment**, yet credits SellerWallet.total_earning, which can subsequently be withdrawn through the separate payout flow. It writes an expense Transaction for the **entire order_amount**, rather than the vendor's 90%, while its `paymentMethod` and `notes` arguments are not persisted in that transaction. Using it unchanged creates conflicting meanings for "settled" and could support paying the same entitlement twice operationally. The wrapper also writes a new expense after an inner repeated-settlement no-op.

**Repair:** decide whether this action releases an entitlement into a withdrawable wallet or confirms an external transfer. Implement exactly one payout path. Persist the release amount, actor, reference and notes; keep external payment and beneficiary evidence on the subsequent payout. Correct the existing portal action alongside F05/F06; recheck receipt, expiry and dispute state under the order lock.

### F05 — P1: partial-refund settlement consumes funds belonging to other orders

**Confirmed by actual settlement probe. Reachable through the existing admin settlement action; see the F04 correction.**

[Refund accounting](C:/Users/USER/Downloads/vmarket/backend/vmarket-web/app/Services/PaystackRefundService.php:1138) reduces AdminWallet.pending_amount by returned merchandise. Later [disburseSettledVendorOrder](C:/Users/USER/Downloads/vmarket/backend/vmarket-web/app/Utils/OrderManager.php:477) subtracts the original full order_amount, even though its vendor subtotal excludes refunded items.

Actual isolated example: order A originally ₦100, one ₦50 item already refunded, plus ₦100 escrow backing order B. Set the remaining global pending amount to ₦150. Settling A credits its vendor ₦45 but subtracts ₦100, leaving **₦50 instead of ₦100** for B. Drift: **−₦50**. `max(0, ...)` can conceal the error when funds are insufficient.

**Repair:** record and consume the remaining hold for the specific child order, not its original total or an undifferentiated wallet balance. Partial refunds must release the exact cash/reward/tax components once; settlement must consume only the remaining entitlement. Reject an inconsistent ledger rather than clamping it.

### F06 — P1: the settlement accounting does not preserve merchandise, tax, delivery, and platform revenue partitions

**Tax-bearing transaction discrepancy confirmed by actual helper execution; missing revenue postings confirmed by tracing.**

[DeliveryOrderSettlementService](C:/Users/USER/Downloads/vmarket/backend/vmarket-web/app/Services/DeliveryOrderSettlementService.php:501) calculates 10% commission from vendor `subtotal`; that subtotal includes tax. [getAddOrderTransactionsOnGenerateOrder](C:/Users/USER/Downloads/vmarket/backend/vmarket-web/app/Utils/OrderManager.php:1600) also includes tax in the transaction amount used to derive seller_amount. At manual disbursement, vendor payable is recalculated from merchandise-only order details, but the held transaction's seller_amount/admin_commission are not corrected.

Actual tax example: merchandise ₦100, tax ₦7.50, shipping ₦10. Held transaction shows seller_amount=₦96.75, commission=₦10.75, tax=₦7.50. Required merchandise allocation is vendor=₦90 and commission=₦10, with tax separate. The transaction columns disagree with eventual vendor-wallet accounting and reports that use them.

For third-party orders, [recognizeDeliveryFeeUponCustomerReceipt](C:/Users/USER/Downloads/vmarket/backend/vmarket-web/app/Utils/OrderManager.php:77) has **no callers**. Generic wallet management returns early for third-party marketplace orders. Manual settlement removes pending funds and credits vendor earnings/commission_given, but does not credit AdminWallet.commission_earned, delivery_charge_earned, or total_tax_collected. Actual partial settlement probe yielded vendor earnings ₦45, **commission_earned ₦0 instead of ₦5**.

**Repair:** post per-order entries for gross merchandise, vendor payable, gross commission, reward liability, tax liability, logistics revenue, and actual processor cash. Commission must exclude tax/shipping. Recognize delivery once at authoritative receipt, then settle remaining merchandise/tax components once. Update transaction reporting from these postings, rather than recomputing conflicting totals in different helpers.

### F07 — P1: labeling a cash refund "cashback" bypasses payment confirmation

**Confirmed by actual service probe. Affects admin refund portal. Requires access to that action.**

[finalizeManualPaymentConfirmation](C:/Users/USER/Downloads/vmarket/backend/vmarket-web/app/Services/PaystackRefundService.php:990) decides that a refund is purely reward-funded from the **submitted payment_method**, rather than the authoritative payment allocation. When it is `cashback`, the service skips the normal confirmation transition/amount path and sets moneyToRefund to zero. The admin request validator does not enumerate payment_method, so this label can reach the service through the refund action.

Actual example: Paystack-paid ₦100 order, approved request awaiting a ₦100 cash refund, zero redeemed cashback. Submitting payment_method=`cashback` returned success; status=`refunded`, execution_status=`succeeded`, **zero refund money transactions**.

**Repair:** derive refund funding type from immutable cash/reward allocations. A nonzero refundable cash amount cannot complete through the internal-reward path. Separate internal reward restoration from recording an externally executed transfer; require valid confirmation evidence and exact amount for the latter.

### F08 — P1: legitimate provider refunds fail reference validation

**Confirmed by actual finalizer probe; automated initiation currently lacks a production caller.**

New orders deliberately store an internal value in orders.transaction_ref and Paystack's reference in payment_requests.gateway_reference. [finalizeRefundAccounting](C:/Users/USER/Downloads/vmarket/backend/vmarket-web/app/Services/PaystackRefundService.php:605) nevertheless requires provider transaction_reference to equal the **internal order reference**. Earlier webhook correlation uses the gateway reference, so the two stages contradict one another.

Actual probe with a processed, NGN, exact-amount refund and matching local execution note reached execution_status=`reconciliation_required` because the provider reference differed from the internal reference.

[resolvePaystackReferenceForOrder](C:/Users/USER/Downloads/vmarket/backend/vmarket-web/app/Services/PaystackRefundService.php:49) has another pickup gap: it searches active_pickup_reservation_id, which settlement clears, or an order_group_id that pickup initialization does not persist. It should use the permanent pickup_reservation_id and successful attempt, not an active lease marker or arbitrary first paid attempt. `is_paid=1` also includes reconciled anomalies, not only successful order settlement.

The current admin refund path is manual. No production caller of initiateRefund was found. Therefore this is not evidence that today’s admin click automatically calls Paystack and fails; it is a defect in the reachable provider-webhook/future automated-refund accounting path.

**Repair:** store the specific successful payment attempt on each order; validate provider reference against it. Keep internal and external references distinct. Select attempts by successful state and permanent foreign keys; never infer the provider reference from mutable active markers.

### F09 — P1: a legacy public payment initializer creates a different, unrecorded gateway reference

**Confirmed source-path defect; no external payment was attempted. Affects routed legacy payment links.**

[PaystackController.index](C:/Users/USER/Downloads/vmarket/backend/vmarket-web/app/Http/Controllers/Payment_Methods/PaystackController.php:51) is exposed at GET payment/paystack/pay. It reads an unpaid PaymentRequest, creates a **new** highEntropyReference, calls Paystack with that new reference, and redirects without saving that reference to the payment request. Canonical settlement services subsequently look up PaymentRequest by gateway_reference, so a capture using the newly created reference cannot find the persisted attempt. Repeated visits can initialize more transactions. Ownership enforcement only rejects a mismatched customer **if one is authenticated**.

The canonical mobile/storefront initializer avoids this route today; the route remains live, and Payment trait mapping still refers to it. Its risk must therefore be closed explicitly rather than assuming it is inaccessible.

**Repair:** retire the legacy initializer after caller migration, or delegate it to the canonical attempt service with required ownership and stable reference persistence. Reopening a payment must replay its existing authorization URL. Record any uncorrelated signed capture durably for reconciliation; the current webhook can log/ack an unknown capture without creating a case.

### F10 — P2: cashback maturity can credit a stale reward after a refund adjustment or new dispute

**Concurrency risk established from code; not executed as a concurrent MySQL test.**

[MatureCustomerCashbackCommand](C:/Users/USER/Downloads/vmarket/backend/vmarket-web/app/Console/Commands/MatureCustomerCashbackCommand.php:49) gathers unresolved order IDs and eligible ledger objects before its per-user transactions. Inside the transaction it locks the user but neither reloads the ledger under lock nor rechecks disputes/receipt/refund state. It credits points from the earlier object at line 100.

Interleaving: maturity reads a pending ₦5 reward; partial refund reduces the ledger to ₦2.50; maturity updates pending -> available and credits the stale ₦5. The ledger then says ₦2.50 available while the point balance gained ₦5. A dispute arriving after the initial list can also be missed.

**Repair:** establish a consistent cross-service lock order, reload the current ledger and order state under those locks, and calculate the credited value from the locked record. Add real MySQL races for maturity versus partial refund, new dispute, expiry, redemption, and multiple job workers. Add a unique issuance/event key without forbidding legitimate refund-restoration lots.

### F11 — P2: payment-status polling mutates expired intents without a lock or releasing reserved cashback

**Source-confirmed; race impact requires concurrent testing.**

[DeliveryCheckoutIntentController.status](C:/Users/USER/Downloads/vmarket/backend/vmarket-web/app/Http/Controllers/RestAPI/v1/customer/DeliveryCheckoutIntentController.php:204) is a GET poll. It can write `expired` and clear active_cart_token based on an unlocked earlier read. It does not release reserved CashbackRedemption records there. A callback can convert the same intent between the read and write, after which the poll overwrites converted status. Expired reward reservations can remain until another cleanup path is triggered.

**Repair:** keep reads observational or perform expiry through one transactional service with conditional pending-state update and reward release. Return captured/reconciliation/settled as distinct states so clients do not interpret a paid-but-unfulfilled capture as a completed order.

### F12 — P2: checkout amounts displayed to customers are not the canonical final quote

**Confirmed code/contract mismatch. Affects storefront and customer app.**

The active [storefront order summary](C:/Users/USER/Downloads/vmarket/backend/vmarket-web/resources/themes/theme_vmarket/theme-views/partials/_order-summery.blade.php:52) labels merchandise plus tax as total, omits delivery, and says delivery fees will be calculated at checkout even on the final payment page. The payment form immediately delegates to the canonical backend and redirects to Paystack, where delivery is included. No active storefront cashback-redemption input was found in this payment flow.

[Customer checkout screen](<C:/Users/USER/Downloads/vmarket/User app/lib/features/checkout/screens/checkout_screen.dart:820>) calculates cashback caps and payable locally from points/config/cart values. It treats these as preview estimates, which does not let a customer alter the backend charge, but the final intent amount is not shown for confirmation before [placeDeliveryOrder](<C:/Users/USER/Downloads/vmarket/User app/lib/features/checkout/controllers/checkout_controller.dart:359>) initializes payment and navigates away. Multi-vendor delivery and concurrently reserved points can make the preview diverge.

**Repair:** return and display the complete frozen quote: each child order's merchandise, discount, reward spend, tax, delivery, and total payable. Confirm that quote before initialization, with backend decisions for reward eligibility. Use the same response DTO on web/mobile, and expose intended reward redemption consistently.

### F13 — P2: mobile delivery payment recovery is disconnected and callbacks use obsolete order linkage

**Confirmed source-path mismatch. Affects customer app and web callback presentation.**

[CheckoutController](<C:/Users/USER/Downloads/vmarket/User app/lib/features/checkout/controllers/checkout_controller.dart:356>) keeps the current order_group_id only in memory, generates a fresh idempotency key on each create call, and launches the older DigitalPaymentScreen. [That screen](<C:/Users/USER/Downloads/vmarket/User app/lib/features/checkout/screens/digital_payment_order_place_screen.dart:160>) treats a success URL as success and closing the WebView as cancellation. The delivery branch of PaymentStatusScreen accepts orderGroupId but no production call site supplies it. The found caller is pickup-only. No durable pending-delivery checkout state was found for restart recovery.

[Processor.payment_response](C:/Users/USER/Downloads/vmarket/backend/vmarket-web/app/Traits/Processor.php:92) still queries orders.transaction_ref using PaymentRequest.transaction_id. Canonical settlement stores separate internal order references and settled_orders/order_group_id instead, so redirect order_ids are incorrect/empty. Null failure responses also access payment_info fields without a null guard.

**Repair:** persist pending intent/attempt identity, reuse a checkout idempotency key, and always fetch authoritative status after success, close, timeout, or restart. Treat an indeterminate payment as pending, not cancelled. Build callback responses from actual settled order IDs, while clients verify the order before presenting success.

### F14 — P2: pickup payment recovery displays the wrong handover secret

**Confirmed source-path defect. Affects paid pickup customers and vendor handover.**

[PaymentStatusScreen](<C:/Users/USER/Downloads/vmarket/User app/lib/features/checkout/screens/payment_status_screen.dart:108>) prefers `verification_code` over `pickup_verification_code` when retrieving the paid pickup order. Both can be exposed to the authenticated customer by the order-details endpoint. The first is the separate delivery code; in-shop handover verifies the pickup code.

Effect: a customer who has paid can be shown a valid-looking six-digit code that the merchant correctly rejects. This is particularly disruptive during payment recovery, and may cause a handover lockout after repeated attempts.

**Repair:** retrieve the dedicated pickup secret only through the authoritative pickup/order DTO. Do not fall back to delivery OTP or a fabricated placeholder. On a missing secret, retry the verified-order fetch and show a recoverable state.

### F15 — P2: currency and numeric conventions diverge between vendor and rider money paths

**Conditional currency defect and source-confirmed precision exposure. Not evidence of current production exchange-rate configuration.**

[SellerController.withdraw_request](C:/Users/USER/Downloads/vmarket/backend/vmarket-web/app/Http/Controllers/RestAPI/v3/seller/SellerController.php:529) converts requested amounts to USD-style internal values. [Cancellation](C:/Users/USER/Downloads/vmarket/backend/vmarket-web/app/Http/Controllers/RestAPI/v3/seller/SellerController.php:594) converts the already stored amount again before restoration. In single-currency mode this is effectively a no-op; with multi_currency enabled it restores a different value. The vendor web cancellation restores the stored value directly. Rider withdrawals explicitly use NGN.

SellerWallet, AdminWallet, OrderDetail and refund amounts still use float casts in important paths. Passing those values into BCMath later does not retroactively guarantee exactness. Precision, rounding and minimum-payout policy are not consistently enforced across all consumers.

**Repair:** choose one NGN/kobo storage contract, record currency on every financial event, and restore the exact stored amount without conversion. Use decimal strings/integer minor units throughout money mutations and apply documented residual allocation. Test nontrivial decimals, sub-kobo inputs, multiple currencies being disabled/enabled, and cancellation parity.

### F16 — P2: the earlier test certificates do not cover the defects above

**Confirmed test-scope gap.**

[PaymentFulfillmentBoundarySecurityTest](C:/Users/USER/Downloads/vmarket/backend/vmarket-web/tests/Unit/PaymentFulfillmentBoundarySecurityTest.php:96) reproduces controller-like logic in closures and mock orders. Its "atomic lock" proof mutates an in-memory payment row, not two concurrent database connections. MultiActorEndToEndSimulationTest uses its own simulated actors, lanes and order engine. These are useful model checks but should not be reported as production controller or MySQL concurrency proof.

The [SQLite schema harness](C:/Users/USER/Downloads/vmarket/backend/vmarket-web/tests/Feature/DumpSchemaTestCase.php:33) explicitly strips indexes/foreign-key behavior and cannot prove MySQL uniqueness and locking. The executed lifecycle test contains real model assertions but is not a browser/mobile/Paystack-to-payout end-to-end test.

**Repair:** build regression tests against actual routed controllers and services for these findings, then run contention tests on the production database engine. Keep modeled checks labeled as modeled. Release evidence must enumerate exactly which actor flows, gateway payloads, database engine, and race scenarios were executed.

## Additional gaps requiring follow-up

These are deliberately not counted as independently reproduced high-severity findings:

- Delivery initialization performs provider network calls while the checkout/payment locks remain held. Pickup already uses a shorter transaction/initialization lease. Align delivery after regression coverage to reduce callback blocking and timeout contention.
- Settlement eligibility is checked before locking in the wrapper; disbursement does not recheck all receipt/dispute conditions under lock. Fix alongside F04.
- Manual settlement mixes wallet release and external bank-payment confirmation. Do not enable it without selecting a single meaning and tracing the subsequent withdrawal.
- Manual/provider pre-settlement refunds reduce the hold by full returned merchandise, whereas delivery holds may contain cash net of reward spend plus tax/shipping. Mixed-funded and tax-bearing refunds need a per-component conservation proof, not a shared-wallet subtraction.
- executeUndeliveredOrderRefund is not production-wired; it records a full-order refund and a separate shipping refund transaction, without proving an external transfer, and does not release the merchandise pending hold. Avoid wiring this helper as the cancellation refund engine unchanged.
- Pickup reward scheduling falls back to 5% when configured rate is zero; receipt-time issuance honors zero differently. There is a redundant earn record in cashback_redemptions, while customer_cashback_ledgers drives maturity. Decide which snapshot owns the promised earning rate, especially across configuration changes between payment and handover.
- Reward exchange-rate changes revalue aggregate point balances and expiry calculations. Persist the conversion rate/allocation at issuance/redemption; decide whether balances are denominated in points or NGN. No solvency/cap enforcement against platform commission was established for configurable reward rates.
- The legacy `delivery_payment` webhook branch updates order payment status outside the canonical PaymentRequest settlement domain and should be classified and tested or retired.
- Provider refunds can receive pending/failed events after a processed event without a terminal guard in the webhook status updates. Ensure event ordering cannot regress succeeded status.
- Current payout actions use generic withdrawal method fields; no common immutable verified-beneficiary snapshot was established across bank updates, payment-information methods, and payout approval. Audit which destination the operator actually pays and whether later edits can change a pending request.
- In-house orders require their own cost/margin/cashback expense accounting; a merchant commission model cannot establish profit for owned inventory.
- Source documents disagree on instant pickup cashback, return/stock-hold semantics, Laravel versions, and the status of manual versus automated refunds. Reconcile the specifications after selecting the authoritative lifecycle, so future implementation follows one contract.

## What is already useful

- Main web/mobile delivery initiation delegates to shared backend intent/payment services.
- Main callbacks verify Paystack server-side; webhooks enforce HMAC-SHA512 signature verification.
- Canonical settlement has transaction/locking and replay guards, exact amount comparisons, and anomaly records.
- Cart/address ownership and vendor-resource ownership checks exist in inspected paths.
- Main delivery completion requires customer OTP and records receipt/window timestamps; paid pickup uses a separate handover OTP.
- Customer rewards are non-withdrawable, and the receipt path creates pending rather than immediately available reward credit.
- Some payout actions already lock requests and wallets and require payment evidence for approval.

These foundations should be retained, with the gaps above repaired through the existing domain services.

## Cross-surface impact

| Surface | Classification | Main required work |
| --- | --- | --- |
| Laravel/domain/database | AFFECTED | State transitions, per-order holds/postings, schedules, reference linkage, currency/precision, provider reconciliation. |
| Admin portal | AFFECTED | Correct payout transitions, explicit payable settlement action, manual-refund confirmation, finance authorization, exception queue and audit evidence. |
| Vendor portal | AFFECTED | Remove terminal refund completion authority; show released versus paid balances and correct transaction breakdowns. |
| Storefront | AFFECTED | Canonical quote including child delivery fees/tax/reward spend; consistent recovery and verified success display. |
| Customer Flutter | AFFECTED | Canonical final quote, durable checkout recovery, idempotency reuse, correct pickup OTP and refund states. |
| Vendor Flutter | AFFECTED | Vendor refund authority/DTOs, payout amount/currency semantics, balances from canonical events. |
| Rider Flutter | AFFECTED | Payout status/amount parity, customer receipt contract and operational payment states. |
| Historical POS data | POTENTIALLY AFFECTED | Explicitly separate historic transactions when reconciling shared wallets; do not rewrite historical balances blindly. |
| Branding/assets/catalog styling | NOT AFFECTED | No visual redesign needed for the money repairs. |

## Recommended repair sequence

### Stage 1 — stop false financial finalization

Fix F01/F02/F07 first. Enforce actor-specific transitions and reject invalid payout states. Keep existing intended manual operations usable through verified accounting actions. Add real controller regression tests proving that no money balance or terminal state changes on an invalid action.

Acceptance: vendor cannot complete a refund; repeated/invalid payout action cannot create earnings or negative reservations; cash-backed refund cannot complete as reward-only.

### Stage 2 — establish one balanced accounting lifecycle

Resolve F04/F05/F06 and the manual payment versus wallet-release decision together. Preserve the existing 90/5/5 model, with delivery and tax separate. Create per-child-order allocations/hold events and a single payout entitlement. Use forward reconciliation adjustments for existing data rather than resetting aggregate wallets.

Required example: merchandise ₦100,000, tax ₦7,500, delivery ₦2,000, rewards spent ₦10,000:

- Gateway cash capture = ₦99,500.
- Reward liability consumed = ₦10,000.
- Total funding = ₦109,500 = merchandise ₦100,000 + tax ₦7,500 + delivery ₦2,000.
- Vendor entitlement = ₦90,000 from merchandise.
- Gross commission = ₦10,000 from merchandise.
- If the selected authoritative rule earns 5% on new merchandise cash only, new reward liability = ₦4,500; net retained commission before processor fees/operating expenses = ₦5,500.

Every capture/refund/receipt/reward/settlement/payout event must balance. Historical bank cash cannot be inferred solely from wallet counters.

### Stage 3 — repair scheduling, provider linkage, and recovery

Fix F03/F08/F09/F10/F11. Register jobs in the actual bootstrap; use durable successful payment-attempt links; keep reconciliation cases for captures without orders; align lock order and expiry cleanup. An exception queue must distinguish captured-but-unfulfilled, duplicate capture, short-stock capture, refund pending/failed, and settlement/payout exceptions.

Acceptance: schedule:list includes intended jobs; duplicates change nothing; no capture/refund disappears into a log-only acknowledgment; old callback/new poll races do not corrupt state.

### Stage 4 — align every user surface

Fix F12/F13/F14/F15 across backend DTOs, storefront, customer app, vendor app and portals. Display the same backend quote and financial states everywhere. Verify actor routes and permissions with actual accounts; browser/device walkthroughs must include app restart and network loss.

### Stage 5 — prove the release and reconcile existing balances

Replace financial certification based on modeled tests with routed integration and MySQL contention evidence. Export a read-only reconciliation worksheet from gateway payments/refunds, successful order allocations, vendor entitlements/payouts, logistics amounts, tax liabilities and reward lots. Investigate discrepancies; record authorized adjustment entries with source evidence. Then run a small controlled launch cohort.

Minimum scenarios: multi-vendor delivery; inspected pickup; full reward pickup; mixed reward/cash delivery; zero/positive tax; per-child delivery fees; discounts with quantities; partial/full/pre-receipt refunds; refund after payout; order B isolation during refund/settlement of A; duplicate webhook/callback; late/stale capture; stock failure after capture; repeated payout approval/rejection/cancellation; invalid states; unauthorized actor; rate/fee changes during checkout; app close/restart; concurrent maturity/refund/redemption; disabled scheduler/provider outage.

## Reproducible probe results

```text
Actual admin withdrawStatus:
  call 1: earning=100, pending=0, request_status=0
  call 2: earning=200, pending=-100, request_status=0

Actual rider payout update service:
  input approved=0, amount=100, pending=100
  output pending_withdraw=0, request approved=0

Actual delivery hold transaction helper:
  merchandise=100, tax=7.50, shipping=10
  seller_amount=96.75, commission=10.75, transaction tax=7.50
  expected merchandise vendor=90, commission=10

Actual partial-refund settlement:
  original order A=100, refunded item=50, order B backing=100
  pending before remaining-A settlement=150
  pending after=50 (expected 100), A vendor earning=45
  platform commission_earned=0 (expected 5)

Actual owning-vendor refund-status action:
  HTTP=200, refund status=refunded, item flag=1
  RefundTransaction count=0, unresolved dispute=false

Actual processed provider-refund finalization:
  valid processed/NGN/exact-amount/execution-note proof
  provider reference different from internal order reference
  execution_status=reconciliation_required

Actual booted scheduler:
  kernel=Illuminate\Foundation\Console\Kernel
  Schedule.events=[]

Actual manual refund finalizer with a false cashback label:
  Paystack cash-backed refund=100, redeemed cashback=0
  submitted payment_method=cashback
  returned success, status=refunded, execution=succeeded
  RefundTransaction count=0
```

Harness safety: every run forced the default connection to SQLite `:memory:` before providers booted, loaded only the install-schema snapshot into that connection, and prohibited unexpected HTTP calls. Test-only compatibility columns were ephemeral. These outputs are an audit transcript, not runner-generated release certificates.


## Source fingerprints

SHA-256 of key sources as inspected at audit completion:

| Source | SHA-256 |
| --- | --- |
| backend/vmarket-web/bootstrap/app.php | c294071e4cdbe1b35882d857ad94b45416d71df77ab1fa97ce2eb5a2d0a81540 |
| backend/vmarket-web/app/Http/Controllers/Admin/Vendor/VendorController.php | cd2d1fb1fcc7a56be7941976633235eb00c649f39b509180877dc5ad766be2f7 |
| backend/vmarket-web/app/Http/Controllers/RestAPI/v3/seller/RefundController.php | 2e0d7786da73c94b47188677bfc19a3df3b9a873ce0578b24571f5465aec6ff2 |
| backend/vmarket-web/app/Services/PaystackRefundService.php | 47c9ac8a9024c666854aefe468cad771084599f15f929cd1bf177b36206c88cc |
| backend/vmarket-web/app/Services/DeliveryOrderSettlementService.php | 6fe5c95adbe6b604f0452cd6ebe591e1dfad7f9a3cde66f9b4a723b1834c4315 |
| backend/vmarket-web/app/Services/VendorSettlementService.php | 1527130784e96c31e28778ec17916ba3ebdc37c0be6b0107566543ca3f19d77c |
| backend/vmarket-web/app/Utils/OrderManager.php | f61a0e73f34cb6e0ba425b6d6d1588102aa0314a2727b4a6abfceddaf01e21a5 |
| backend/vmarket-web/app/Console/Commands/MatureCustomerCashbackCommand.php | abd1027c278a3f921a45e70c6cc3147717b157ec92b04d59ab34ac84d368588d |
| User app/lib/features/checkout/controllers/checkout_controller.dart | 61448c16a437611b1e9b1490e9228038015348c404064fe0390a8e58b4451ddb |
| User app/lib/features/checkout/screens/payment_status_screen.dart | 660d5653ba844d283817f47405db625465965b460124251d009eb267e6ee9b94 |

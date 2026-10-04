# V1 money paths — second-pass audit, 2026-10-04

Verdict: NOT approved to close the money audit or release. Scope is branch v1, source HEAD 7ea05351b678486e731df2bdfb275d1b4de0956f. This is a read-only source/route/client review; no money transfers, configured database operations, product changes or deployment occurred.

The earlier 30-test/243-assertion integration suite passes, but omitted reachable pickup reward, alternate rider payout, refund-preview and generic order mutation scenarios. The previous statement that every audited code defect was fixed was too broad. The repair commits remain useful; these gaps require another repair cycle.

## Confirmed source findings

### M01 — High: generic order editor permits arbitrary financial-column mutation
Admin POST /admin/orders/amount-date-update and vendor POST /vendor/orders/amount-date-update delegate to OrderRepository::updateAmountDate (app/Repositories/OrderRepository.php:389). It takes field_name and field_val directly and updates the named order column around line419, without an allowlist. Vendor/Order/OrderController.php:646 supplies the authenticated seller ID only as a history parameter; the repository update is scoped by order ID, not seller ownership.

A vendor can submit field_name=payment_status or vendor_settlement_status, order_amount or refund_window_expires_at rather than the intended date/charge fields. The repository also permits another order ID. These bypass payment authority, immutable funding snapshots and refund-window controls. Fix the allowlist, strict value validation, ownership/policies, immutable paid fields and audit logging before examining less critical paths.

### M02 — High: vendor mobile rider payout approval bypasses hardened service
PUT /api/v3/seller/delivery-man/withdraw/status-update maps to RestAPI/v3/seller/DeliverymanWithdrawController::status_update (line59). Unlike the shared service, it accepts arbitrary approved values, writes approved directly, and decrements pending_withdraw in every non-1 branch (around lines86–134).

Decision approved=0 leaves the request pending; repeating it subtracts the reservation again. The rider request endpoint calculates current_balance minus pending_withdraw, so negative reservations allow over-requesting; later approval has no balance/reservation sufficiency assertion here. No payment proof is required by this alternate approval path. SellerApiAuthMiddleware owner-only pattern *seller/withdraw* misses this nested delivery-man route and there is no corresponding employee module mapping. Fix owner/finance authorization, terminal enum, proof, shared posting service, sufficiency and replay protection together.

### M03 — High: pickup reward settlement gross/net contract conflicts with new escrow invariant
PickupOrderSettlementService.php:442–473 stores reservation gross total as both order_amount and init_order_amount while storing redeemed cashback separately as discount_amount. OrderManager.php:1674–1677 expects order_amount + redeemed reward funding to equal merchandise + tax + shipping.

For gross100 and reward20, pickup records order_amount100 and reward20, so the assertion compares100 with120 and throws after capture. The settlement handler only catches PostPaymentStockFailureException around line398, so this accounting exception does not follow the durable stock-failure reconciliation path. Full-reward pickup is affected too. Fix net cash/gross semantics consistently and quarantine all verified captures whose settlement fails. This is a source-traced scenario, not a completed live gateway reproduction.

### M04 — High: pickup replacement attempt loses redeemed-reward allocation
PickupPaymentInitializationService.php:637–693 handles a gateway reference-not-found recovery by creating a replacement with discounted payment_amount but omits original gross_amount, cashback_amount and cashback_reservation metadata. Pickup settlement reads cashback_amount and captures the redemption only when its metadata is present.

For gross100/rewards20/cash80, the replacement can settle an order treated as gross100 with no reward discount or captured redemption. Shared accounting then accepts gross100=order_amount100 while the provider collected80. Preserve the locked original allocation, capture its reservation once, and revalidate terminal state after network verification. Source-traced recovery scenario; executable regression must be added before repair sign-off.

### M05 — High: normal customer refund preview fails before the form
GET /api/v1/customer/order/refund invokes OrderController::refund_request (line175). When loyalty is enabled it calls nonexistent CustomerManager::countLoyaltyPointForAmount at line194. When disabled, the delivered-item branch references undefined $order_details at lines207–219 instead of $orderDetails.

The app RefundProductWidget calls this preview before opening the refund form and requires HTTP200. Customers therefore cannot use the normal return UI. Replace this legacy preview with the canonical refund allocation and receipt-window contract; test actual endpoint with loyalty on/off and zero balance. Do not require customers to hold pending reward points to request a return.

### M06 — High: refund allocation drops final reward/cash pennies
OrderManager::getRefundDetailsForSingleOrderDetails (line2183 onward) independently prorates each item at four decimals then truncates outputs to two decimals. Finalizers cap restorations but do not allocate the final-item residual.

Example: merchandise100 split33.33/33.33/33.34, reward10. Each item emits cash30 and reward3.33, summing cash90 + reward9.99 instead100. Before release, full returns can strand .01 of escrow; after release the last item's funding33.33 versus merchandise33.34 yields a negative tax partition and reversal fails closed. Freeze per-item allocation with an explicit last-item residual and reuse it for preview, request, manual/provider accounting and reward restoration. Arithmetic/source verified; actual production-service regression still required.

### M07 — Medium: pickup checkout lacks repaired quote/recovery contract
User app/lib/features/checkout/screens/pickup_payment_screen.dart:98 initializes payment before final confirmation and around line136 opens the gateway. Its amount extraction at160–165 expects amount_kobo, while the initialization response supplies payment_amount. Digital payment fail/cancel/back branches still pop/show failure for pickup rather than polling authoritative pickup outcome; no equivalent durable pickup identity was found.

A discounted pickup can display the original gross value, and closing a WebView around a successful capture can show failure. Apply backend quote confirmation, durable reservation/attempt identity and authoritative result recovery to pickup, including web parity. This is distinct from the backend accounting defects above.

### M08 — Medium: admin payment status can diverge from captured funding
Admin POST /admin/orders/payment-status invokes OrderController::updatePaymentStatus at649. The digital guard around679 runs only for requested paid; the raw update at718 permits paid -> unpaid or other supplied strings without canonical accounting. A Super Admin reason also allows an unpaid digital order to be marked paid without establishing its captured escrow.

Require validated immutable canonical payment state and an evidence-backed reconciliation command for exceptions. A reason/audit event alone is not payment proof.

### M09 — Medium, provider-dependent: cumulative refunds include tax as returned merchandise
PaystackRefundService.php:789–795 sums paid RefundTransaction.amount for previous refunds as merchandise; those records include cash tax and separate restored rewards. The manual finalizer correctly uses saved merchandise allocations instead.

Three items each merchandise50, tax3.75 and rewards10: first refund creates cash43.75 + restored reward10 =53.75 paid records. A second50 return then reports remaining merchandise46.25 instead50 and undercalculates pending earned reward. A sufficiently small remaining item can be treated as fully returned. The signed refund webhook remains reachable, but current ordinary admin execution is manual and initiateRefund has no production caller. This is an existing/in-flight provider-event compatibility gap, not a claim that current manual refunds always take this path.

### M10 — Medium: delivery intent supersession leaves an old payment URL payable
DeliveryCheckoutIntentService.php:328–347 cancels an active intent and releases its rewards when cart/address changes, without checking for an active initialized gateway attempt. Another device or storefront can bypass the customer app's local replacement block. A capture on the old URL is safely quarantined, but the customer has paid without receiving an order through an avoidable implicit cancellation.

Block supersession while payment is unresolved, or close only after authoritative unpaid verification. Keep intentional expired/canceled captures in durable reconciliation.

## Additional observations needing follow-up

Vendor recommendation UI still exposes a second opposite decision although the service allows only pending -> one recommendation. Customer refund displays should distinguish seller recommendation from administrator decision/payment completion. Rider request creation still uses floats and numeric|min:1 without strict two-decimal NGN validation or a durable beneficiary snapshot. These require parity tests; they are not substitutes for the high-priority findings.

Admin generic order-status regressions may permit terminal delivered orders to be reset and rider earnings credited again; this was identified as a candidate, but a complete executable replay/authorization check was not finished and is NOT included as a confirmed finding.

## Verification limits

No fresh full money-path certification follows from this review. Earlier integration evidence: 30 tests/243 assertions passed on isolated SQLite; checkout analysis0errors/18warnings/14infos. Those tests do not exercise all findings above and cannot establish MySQL contention. New source scenarios need actual endpoint/service regressions, not copied arithmetic certification.

Specialist review work stopped when workspace credits were exhausted. Their confirmed source findings are included; proposed executable reproductions were not completed. No claim is made that every possible path or remaining defect has been exhausted.

## Next steps, in order

1. Repair M01–M06 with actual route/service regressions, including vendor/rider authorization and all reward/payment combinations. Preserve all preexisting unrelated files.
2. Repair pickup quote/recovery and admin payment-state parity (M07/M08), provider cumulative allocation and delivery supersession (M09/M10).
3. Run mixed/full/no-reward delivery and pickup, timeout/replacement, expired/late captures, repeated payout decisions, employee/other-owner attempts, partial/full taxed refunds and penny residuals against actual code.
4. Run MySQL8 two-connection concurrency tests; staging migration and read-only finance:reconcile-v1; approve an evidence-based historical backing procedure. Verify real cron, test-mode gateway redirects/webhooks and device/browser recovery. Do not reset balances or infer bank cash from wallets.
5. Only then close the money audit. Next non-money audit should start with authentication/roles/tenant ownership, then order/inventory/OTP state transitions, then catalog/search, notifications and operational reliability.

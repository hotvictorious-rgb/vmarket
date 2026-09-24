# Sync Channel: Admin Control Center ↔ Backend

> **Actor Focus**: Super Admin, Logistics Dispatchers & Support Employees  
> **Client Framework**: Laravel Blade (`resources/views/admin-views/`)  
> **Protocol Rules**: Strictly follow [`.agents/sync/CROSS_AGENT_COMMUNICATION_PROTOCOL.md`](file:///c:/Users/SOOQ%20ELASER/Downloads/vmarket/.agents/sync/CROSS_AGENT_COMMUNICATION_PROTOCOL.md) and [`.agents/sync/API_CONTRACT_REGISTRY.md`](file:///c:/Users/SOOQ%20ELASER/Downloads/vmarket/.agents/sync/API_CONTRACT_REGISTRY.md).

> [!CAUTION]
> **MANDATORY MULTI-AGENT GIT COMMIT DIRECTIVE (NON-NEGOTIABLE)**:
> **EVERY AI MUST STAGE AND COMMIT ONLY ITS OWN SPECIFIC CHANGES — NEVER COMMIT ALL FILES!**  
> 1. You MUST explicitly name only your own modified files: `git add <exact-file-path-1> <exact-file-path-2> AI_CHANGELOG.md`.
> 2. Running `git add .`, `git add -A`, or `git commit -a` is **STRICTLY FORBIDDEN**.
> 3. Concurrent AIs are actively modifying files in other directories (`User app/`, `Vendor app/`, `Delivery Man App/`, `backend/`, `storefront/`). When you see foreign files in `git status`, **LEAVE THEM DIRTY AND UNTOUCHED**. Do NOT stage them, do NOT commit them, and NEVER run `git restore .` or `git checkout -- .`.

> [!IMPORTANT]
> **LOCAL BACKEND SERVER LIFECYCLE & TESTING DIRECTIVE**:
> - **The Backend AI runs and maintains the central local server** at `http://127.0.0.1:8000`.
> - **Frontend AIs do NOT need to run or launch PHP or Apache/Nginx web servers.**
> - **Admin Command Center**: Access directly at `http://127.0.0.1:8000/admin`. Test directional delivery lanes (`/admin/delivery-lanes`), dispatch portal (`/admin/dispatch-portal`), and audit logs.

---

## 1. Client Invariants (Mandatory for Admin AI)
1. **Control Tower, Not a Second Engine**: The Admin Panel is strictly a presentation and command interface for the backend domain services. Never compute lane rates, commission splits, or wallet adjustments inside Blade views or controllers.
2. **Directional Delivery Lanes (`DeliveryLane`)**: Admin governs public delivery feasibility via directional `Origin LGA → Destination LGA` lanes. Direction matters (e.g. Uyo $\rightarrow$ Eket does not automatically enable Eket $\rightarrow$ Uyo).
3. **Immutable Order Snapshots**: When an admin updates lane rates or delivery fees, existing/historical order snapshots must remain immutable.
4. **Manual Refund Governance**: Customer refunds (`RefundRequest`) follow the receipt-first policy and explicit manual disbursement tracking.

---

## 2. Active Communication & RFC Tickets

### [TICKET-ADMIN-001] Directional Delivery Lane Management
- **Status**: `FULFILLED`
- **Request**: Super Admin needs interface to create, toggle, and update directional delivery lanes (`origin_lga_id`, `destination_lga_id`, `base_delivery_fee`, `estimated_delivery_time`).
- **Backend Fulfillment**:
  - Routes: `GET /admin/delivery-lanes`, `POST /admin/delivery-lanes/store`, `POST /admin/delivery-lanes/status`, `POST /admin/delivery-lanes/update/{id}`.
  - Controller: `App\Http\Controllers\Admin\Delivery\DeliveryLaneController`
  - Invariant: Unique composite index on `(origin_lga_id, destination_lga_id)` prevents duplicate lane definitions.

### [TICKET-ADMIN-002] Logistics Dispatch Portal & Waybill Manifest
- **Status**: `FULFILLED`
- **Request**: Admin dispatchers need to group pending delivery orders moving along identical LGA corridors into dispatch batches.
- **Backend Fulfillment**:
  - Routes: `GET /admin/dispatch-portal`, `POST /admin/dispatch-portal/assign-batch`, `GET /admin/dispatch-portal/print-manifest`.
  - Controller: `App\Http\Controllers\Admin\Delivery\DispatchPortalController`

### [REQ-ADMIN-20260924-001] Payment Exception Triage Queue
- **Status**: `PENDING_BACKEND_REVIEW`
- **Urgency**: `HIGH`
- **Context**: Orphaned `payment_requests` (`initialized_no_order`, `callback_failed`, `webhook_mismatch`, `amount/reference mismatch`, `duplicate`) currently have no admin triage surface. Spec §31/A7 mandates a dedicated queue so no prepaid order is silently stuck.
- **Proposed Admin Routes**: `GET /admin/payment-exceptions`, `GET /admin/payment-exceptions/{id}`, `POST /admin/payment-exceptions/{id}/retry`, `POST /admin/payment-exceptions/{id}/create-order`, `POST /admin/payment-exceptions/{id}/mark-resolved`, `POST /admin/payment-exceptions/{id}/escalate`.
- **Required Fields / Policy Rules**:
  - List filters: `exception_type`, `payment_status`, date range; detail shows Paystack reference, `order_group_id`, before/after reconciliation JSON.
  - Actions backed by `PaymentRequest` + `PaymentReconciliation` + `DeliveryCheckoutIntentService`; `retry_verification` re-invokes webhook processing idempotently; `create_order` resolves the stored intent atomically (no duplicate order).
  - Every action writes an immutable `AdminAuditLog` (`payment.exception.*`) with reason. Policy: `payments.reconcile`.
- **Mirror**: `docs/api/admin_panel_api_requests.md` AAPI-001.

### [REQ-ADMIN-20260924-002] Delivery Exception Queue
- **Status**: `PENDING_BACKEND_REVIEW`
- **Urgency**: `HIGH`
- **Context**: Rider-reported failures — customer unavailable, wrong address, damaged package, lane interruption, failed attempt (Spec §39, §14, A8) — need a dispatcher queue with resolution workflows and full audit.
- **Proposed Admin Routes**: `GET /admin/delivery-exceptions`, `GET /admin/delivery-exceptions/{id}`, `POST /admin/delivery-exceptions/{id}/assign`, `POST /admin/delivery-exceptions/{id}/reattempt`, `POST /admin/delivery-exceptions/{id}/return`, `POST /admin/delivery-exceptions/{id}/resolve`.
- **Required Fields / Policy Rules**:
  - `DeliveryException` record: `order_id`, `exception_type`, `rider_note`, `customer_contact`, `status` (`open`/`assigned`/`reattempt_scheduled`/`return_initiated`/`resolved`), `resolved_by`, `resolved_at`.
  - Reattempt scheduling and return initiation call existing dispatch/settlement services; no client-side routing decisions.
  - Every state change writes `AdminAuditLog` with reason. Policy: `delivery.exceptions.manage`.
- **Mirror**: `docs/api/admin_panel_api_requests.md` AAPI-002.

### [REQ-ADMIN-20260924-003] Stock Adjustment Workflow with Mandatory Audit
- **Status**: `PENDING_BACKEND_REVIEW`
- **Urgency**: `HIGH`
- **Context**: Admin inventory oversight (Spec §35/A9) requires a stock adjust surface where every mutation logs before/after quantities with a mandatory reason. Currently `ProductStock` exists but no adjustment UI/route.
- **Proposed Admin Routes**: `GET /admin/stock/adjustments`, `POST /admin/stock/adjustments/store`.
- **Required Fields / Policy Rules**:
  - Payload: `product_id`, `shop_id`, `adjustment_type` ∈ {`increase`, `decrease`, `correction`}, `quantity`, `reason` (`required`), `reference_order_id` nullable.
  - Writes `AdminAuditLog` with before/after `current_stock` / `available_stock`; updates `ProductStock` inside `DB::transaction` with pessimistic row lock. Never allows stock below zero.
  - Policy: `inventory.adjust`.
- **Mirror**: `docs/api/admin_panel_api_requests.md` AAPI-003.

### [REQ-ADMIN-20260924-004] Command Center Dashboard — Complete Operational Metrics
- **Status**: `PENDING_BACKEND_REVIEW`
- **Urgency**: `MEDIUM`
- **Context**: Dashboard must be an operational command center (Spec §4/A10). Current `DashboardController::index()` computes only 3 of the 13 mandated metrics.
- **Proposed Admin Changes**: Extend `DashboardController::index()` to add server-computed: `pending_payments`, `awaiting_dispatch`, `in_transit`, `failed_deliveries`, `pending_returns`, `pending_refunds`, `pending_merchant_applications`, `low_stock`, `delivery_exceptions`, `payment_exceptions`; render each as a clickable card linking to its filtered admin list.
- **Rules**: All aggregates computed server-side; zero client-side arithmetic. No new models; reuse `PaymentRequest`, `Order`, `PickupReservation`, `RefundRequest`, `MarketplaceApproval`, `ProductStock`, and the new exception queues.
- **Mirror**: `docs/api/admin_panel_api_requests.md` AAPI-004.

### [REQ-ADMIN-20260924-005] Pickup Inspection Accept/Reject & OTP Verification Oversight
- **Status**: `PENDING_BACKEND_REVIEW`
- **Urgency**: `MEDIUM`
- **Context**: `pickup-list` exists but admin cannot act on inspections (Spec §14/§28-29, A5). Need accept/reject actions plus a collection-OTP verification view for dispute audit.
- **Proposed Admin Routes**: `POST /admin/orders/pickup/{id}/inspect`, `GET /admin/orders/pickup/{id}/otp-verification`.
- **Required Fields / Policy Rules**:
  - `inspect` payload: `outcome` ∈ {`accepted`, `rejected`}, `rejection_reason` (`required` when rejected); writes `AdminAuditLog` (`pickup.inspected` / `pickup.inspection_rejected`).
  - `otp-verification` view: collection timestamp, masked OTP, rider/customer verification log — read from `PickupReservation` + related records.
  - Policy: `pickup.manage`. Server transitions only within `pending_inspection → inspected/ inspected_rejected`.
- **Mirror**: `docs/api/admin_panel_api_requests.md` AAPI-005.

### [REQ-ADMIN-20260924-006] Admin Auth Hardening — Login Audit, Brute-Force Lockout, MFA
- **Status**: `PENDING_BACKEND_REVIEW`
- **Urgency**: `MEDIUM`
- **Context**: Spec §1/A3 require defended admin auth. Current state: `throttle:10,1` on POST login, no login success/failure audit, no lockout fields, no MFA.
- **Proposed Admin Changes**:
  - `LoginController::login()` writes `AdminAuditLog` on success (`admin.login.success`) and failure (`admin.login.failed` with identity + IP).
  - Dedicated `admin.login` rate limiter (5/min) with lockout: `Admin` model fields `failed_login_attempts`, `locked_until`; account locked 15 min after 5 consecutive failures.
  - Optional MFA guard for `admin_role_id == 1` (Super Admin) — TOTP, backend-verified.
  - Confirm every admin route enforces `AdminPolicy` granular abilities, not only `module:*` middleware.
- **Mirror**: `docs/api/admin_panel_api_requests.md` AAPI-006.

---

## 3. Template for New Request Tickets (Copy & Paste to Append Below)
```markdown
### [REQ-ADMIN-YYYYMMDD-###] <Feature / Module Title>
- **Status**: `PENDING_BACKEND_REVIEW`
- **Urgency**: `LOW` | `MEDIUM` | `HIGH` | `BLOCKER`
- **Context**: <Explain the administrative governance, financial audit, or logistics need>
- **Proposed Admin Route**: `METHOD /admin/...`
- **Required Fields / Policy Rules**:
  ```json
  { ... }
  ```
```


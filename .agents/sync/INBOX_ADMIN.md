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


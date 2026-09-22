# VMarket Admin Panel Alignment Plan
**The Phased Engineering Roadmap for Admin Web Alignment**

> **GOAL:** Transform the VMarket Admin Panel into a robust, high-integrity operational control tower. Reconcile all Admin routes, controllers, views, and services with the authoritative VMarket domain architecture, ensuring zero business logic duplication, zero authorization leaks, and complete parity with Customer and Vendor platforms.

---

## 1. Overview of Phases

| Phase | Title | Focus Area |
|---|---|---|
| **Phase A1** | **Repository-Wide Admin Deep Scan** | Comprehensive inventory of Admin routes, controllers, Blade views, middleware, models, and migrations. |
| **Phase A2** | **Admin Impact Map Formulation** | Classify every Admin capability into `KEEP`, `MODIFY`, `MIGRATE`, `DEPRECATE`, `REMOVE`, or `MISSING`. |
| **Phase A3** | **Authorization & RBAC Hardening** | Implement granular permissions, Laravel Policies/Gates, rate limiting, and zero-trust IDOR guards. |
| **Phase A4** | **Geography & Fulfillment Alignment** | Canonical `Country → State → LGA` management, directional `DeliveryLane` controls, and snapshot immutability. |
| **Phase A5** | **In-Shop Pickup Oversight Alignment** | Pickup eligibility, reservation monitoring, inspection audits (`accepted`/`rejected`), and OTP collection verification. |
| **Phase A6** | **Merchant & Branch Multi-Tenancy Governance** | Multi-branch vendor management, branch employee isolation, KYC workflows, and suspension impact rules. |
| **Phase A7** | **Order Lifecycle & Payment Exceptions** | State machine order flows, Paystack server verification center, orphan payment triage, and source-gateway refunds. |
| **Phase A8** | **Delivery Operations vs Geography Logistics** | Hubs and riders as operational logistics infrastructure, dispatch console, waybills, and delivery exception queues. |
| **Phase A9** | **Cashback, Inventory & Immutable Auditing** | 5% Victorious Cashback ledger visibility, stock adjustment workflows, and immutable admin audit trail system. |
| **Phase A10** | **Command Center Dashboard & Systemic Proof** | Operational clickable dashboard metrics, system health monitors, and full regression verification. |

---

## 2. Phase-by-Phase Detailed Specifications

### Phase A1: Repository-Wide Admin Deep Scan
- Scan `backend/vmarket-web/routes/admin/` to catalog all web routes, groups, and middlewares.
- Scan `backend/vmarket-web/app/Http/Controllers/Admin/` for business logic leakage, direct SQL queries, and missing authorization.
- Scan `backend/vmarket-web/resources/views/admin-views/` for legacy forms, client-side aggregate calculations, and deprecated 6valley shipping interfaces.
- Verify migrations affecting Admin roles, permissions, geography, delivery lanes, pickups, and cashback.

### Phase A2: Admin Impact Map Formulation
- Produce an exhaustive capability mapping document (`admin_panel_impact_map.md`):
  - **`KEEP`**: Authoritative controllers and views that already adhere to VMarket architecture (e.g., `DeliveryLaneController.php`, `CustomerCashbackController.php`).
  - **`MODIFY`**: Views and controllers requiring security updates, policy checks, or improved state machine controls.
  - **`MIGRATE`**: Legacy controllers needing refactoring to consume unified domain services.
  - **`DEPRECATE`**: Obsolete 6valley shipping controllers (`DeliveryCity`, `CartShipping`, flat-rate delivery) slated for retirement.
  - **`REMOVE`**: Unused, dead code routes and orphaned views with zero callers.
  - **`MISSING`**: Capabilities mandated by the spec that need to be created (e.g., Payment Exceptions triage queue, Pickup Inspection outcome audit screen).

### Phase A3: Authorization & RBAC Hardening
- Audit and expand role definitions from legacy binary `Super Admin / Employee` to granular roles (`ORDER_ADMIN`, `FINANCE_ADMIN`, `DELIVERY_ADMIN`, `BRANCH_MANAGER`, etc.).
- Convert view-based role checks into Laravel Policies and Gates.
- Implement server-side scoping so request parameters (`shop_id`, `branch_id`, `vendor_id`) cannot be tampered with for privilege escalation.
- Add brute-force lockout and rate-limiting on Admin authentication routes.

### Phase A4: Geography & Fulfillment Alignment
- Ensure Admin views strictly manage canonical `Country ──► State ──► LGA`.
- Admin Delivery Lane interface:
  - Display directional lanes (`Origin LGA ──► Destination LGA`).
  - Independent fee and ETA configuration.
  - Operational linkage: Active shops in origin LGA, active orders on lane.
- Enforce snapshot immutability: Updates to lane fees or lane status must never modify historical order records.
- Shop relocation warning: If Admin moves a shop's LGA, require confirmation and note future routing impact.

### Phase A5: In-Shop Pickup Oversight Alignment
- Create/align Admin pickup management screen:
  - Toggle platform-level and shop-level pickup eligibility.
  - Monitor pickup reservation pipeline (`created ──► awaiting inspection ──► inspected ──► paid ──► collected`).
  - Distinguish inspection rejections (`inspected_rejected`) from customer cancellations.
  - Inspect vendor inspection notes and timestamps.

### Phase A6: Merchant & Branch Multi-Tenancy Governance
- Support vendor branch hierarchy (`Vendor ──► Branch A, Branch B`).
- Enforce branch employee isolation: branch managers and staff can only access data belonging to their assigned branch.
- Merchant approval/suspension lifecycle:
  - Audited status transitions (`Application ──► Approved / Rejected / Suspended / Reinstated`).
  - Suspension consequences: Block new purchases, preserve existing orders, retain financial records.

### Phase A7: Order Lifecycle & Payment Exceptions
- Replace unconstrained order status dropdowns with valid state machine actions.
- Order detail timeline: Render complete chronological audit trail from checkout initiation to delivery/collection.
- Payment management:
  - Server-verified status display only (Paystack).
  - Dedicated Payment Exception triage queue (initialized without order, callback failed, webhook mismatch).
  - Automated refund workflow to original payment source with mandatory audit reason.

### Phase A8: Delivery Operations vs Geography Logistics
- Cleanly separate internal logistics from public marketplace geography:
  - Internal logistics: Hubs, Riders, Dispatchers, Batch Waybills, Transit routes.
  - Marketplace geography: Origin LGA ──► Destination LGA.
- Dispatch console: Monitor ready orders, active rider workload, transit exceptions.
- Delivery exception queue: Dedicated handling for customer unavailable, damaged packages, or failed attempts.

### Phase A9: Cashback, Inventory & Immutable Auditing
- Admin Cashback center:
  - View customer Victorious Points balances and ledger history.
  - Verify mathematical parity with 5% reward from 10% platform commission.
  - Require reason and audit record for any manual adjustment.
- Inventory oversight:
  - Real-time stock levels (available vs reserved for pickup).
  - Stock adjustment log with mandatory justification.
- Immutable Audit Logging:
  - Permanent append-only audit trail for all sensitive operations (lane changes, fee updates, suspensions, refunds).
  - Explicit prohibition of audit record editing or deletion.

### Phase A10: Command Center Dashboard & Systemic Proof
- Transform dashboard into an operational command center:
  - Real-time clickable counts: Pending Pickup Inspections, Orders in Transit, Payment Exceptions, Low Stock.
  - Aggregate figures calculated strictly server-side.
- Mathematical and systemic validation:
  - Confirm financial invariant proofs ($\Delta = 0.00$).
  - Execute PHP syntax validation (`php -l`) and automated test suites.
  - Document all verifications in `AI_CHANGELOG.md` and commit.

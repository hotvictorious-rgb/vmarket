# Admin Panel ↔ Backend Production Alignment Rules

> **MANDATORY FOR ALL AIs.**
> These rules apply **unconditionally** whenever any AI makes changes to the Admin Panel (`resources/views/admin-views/`), Admin controllers (`app/Http/Controllers/Admin/`), Admin routes (`routes/admin/`), or any backend domain service, model, or migration consumed by the Admin Panel. No AI may bypass, skip, or partially apply these rules under any circumstances.

> [!IMPORTANT]
> **CANONICAL SPEC — READ FIRST:** Before acting on this rule file, every AI MUST read the full 70-section canonical specification:
> `.agents/rules/VMARKET_ADMIN_PANEL_SPEC.md`
>
> This specification is the **target production contract**. It represents the authoritative operational model for the VMarket Admin Panel. The AI must **audit current code against the spec**, identify mismatches, and reconcile them **without creating duplicate business systems**.
>
> The phased roadmap is in: `.agents/rules/ADMIN_PANEL_ALIGNMENT_PLAN.md`

---

## 0. Prime Directive: Control Tower, Not a Second Engine

> **The Admin Panel is a presentation and command interface for the backend.**
> It is **NOT** a second business engine. All calculations, pricing, validation, state transitions, permissions, and fulfillment logic reside authoritatively in the backend domain layer. The Admin Panel must never duplicate, invent, or bypass domain rules.

The Admin Panel must answer five questions at all times:
1. What is happening?
2. Why is it happening?
3. Who is allowed to change it?
4. What happens if Admin changes it?
5. Can we prove what happened afterward?

---

## 1. Zero-Trust Server-Side Authorization (Laravel Policies & Gates)

1. **No Client-Side Authorization Trust:**
   Never rely on Blade `@if` conditions, hidden buttons, or Javascript role flags (`if (user.role == 'admin')`) as a security boundary. Every HTTP request sent to an Admin route MUST be independently authorized server-side.
2. **Policy & Gate Enforcement:**
   Every controller action must enforce granular permissions using Laravel Policies (`$this->authorize(...)`) or Gates (`Gate::authorize(...)`).
3. **Explicit Permissions over Generic Roles:**
   Permissions must be atomic (e.g., `delivery.lane.disable`, `orders.approve_refund`, `merchants.suspend`). Avoid coarse checks that assume a role name grants unrestricted power.
4. **Never Trust Request Identifiers:**
   Never assume an Admin has rights over a resource simply because they passed `shop_id`, `vendor_id`, `branch_id`, or `order_id` in `$request`. The backend must scope queries and verify tenant boundaries.

---

## 2. Geography & Delivery Lanes Invariants

1. **Canonical Geography Hierarchy:**
   ```
   Country ──► State ──► LGA
   ```
   `Ward`, `Area`, `Zone`, `Corridor`, and `Hub` are strictly forbidden from marketplace public fulfillment geography.
2. **Directional Delivery Lanes:**
   Admin manages independent directional lanes (`Origin LGA ──► Destination LGA`).
   `Uyo ──► Eket` and `Eket ──► Uyo` are two distinct database rows with independent fees and statuses.
3. **Snapshot Immutability on Lane Changes:**
   When Admin modifies a delivery lane fee or disables a lane:
   - Future checkouts immediately reflect the change.
   - Historical orders MUST NOT be altered. Existing orders retain their original checkout snapshot (`delivery_fee`, `origin_lga_id`, `destination_lga_id`, `lane_id`).
4. **Shop Physical LGA Validation:**
   Shops belong to an explicit LGA. If an Admin changes a shop's LGA, the UI must issue a clear warning that future delivery availability will be re-routed.

---

## 3. In-Shop Pickup Oversight Invariants

1. **Distinct Fulfillment Lifecycle:**
   Pickup is an independent fulfillment path with its own state machine:
   `Reservation Created ──► Inspection (Accepted/Rejected) ──► Payment ──► Ready for Collection ──► OTP Verification ──► Collected`.
2. **Inspection vs Cancellation:**
   An inspection rejection (`inspected_rejected`) must remain strictly distinguishable from customer cancellation or inventory shortages.
3. **OTP Security:**
   Admin views pickup collection status but must never compromise or bypass OTP handover protocols.

---

## 4. Merchant, Branch & Multi-Tenant Isolation

1. **Branch Scoping:**
   Merchants have branches. Each branch has its own physical address, inventory, orders, and assigned staff.
2. **Cross-Tenant Guard:**
   Admin actions executed by branch-scoped managers (`BRANCH_MANAGER`) must be strictly constrained to their assigned branch. Parameter tampering (`branch_id = X`) must abort with HTTP 403.
3. **Suspension Impact:**
   Suspending a merchant stops new customer purchases immediately while preserving existing orders for fulfillment. Never hard-delete merchant records.

---

## 5. Order & Fulfillment State Machine Discipline

1. **No Arbitrary Status Overrides:**
   Admin UI must NOT present an unconstrained dropdown containing all possible statuses. Status transitions must strictly follow domain state machines.
2. **Delivery Progression:**
   `pending` ──► `confirmed` ──► `processing` ──► `out_for_delivery` ──► `delivered` (or `failed`/`returned`).
3. **Pickup Progression:**
   `pending_inspection` ──► `inspection_accepted` ──► `paid` ──► `ready_for_collection` ──► `collected`.
4. **Timeline Transparency:**
   The Admin Order Detail view must render a chronological timeline of every transition, actor, and milestone.

---

## 6. Payment, Settlement & Paystack Verification

1. **Server-Verified Payments Solely:**
   Admin must never mark an order as `paid` based on customer redirect alone. Only server-to-server Paystack verification confirms payment.
2. **Payment Exceptions Queue:**
   Admin must have dedicated visibility into failed webhooks, reference mismatches, and orphan payments where order creation was interrupted.
3. **Refunds to Source:**
   Refunds must be processed through the original payment gateway (Paystack) wherever operationally supported, with mandatory justification and audit logging.

---

## 7. Victorious Cashback & Financial Invariants

1. **Points are Loyalty Discounts, Not Cash:**
   Cashback points are strictly loyalty credits that reduce checkout totals for eligible orders. Points cannot be withdrawn as cash.
2. **Commission Funding Formula:**
   5% Victorious Cashback is funded from the 10% platform commission (net 5% platform retention). Admin displays must accurately reflect this commission split.
3. **Immutable Ledgers:**
   Customer points balances must match the sum of their `customer_cashback_ledgers` transactions ($\Delta = 0.00$). Manual adjustments require explicit justification, permission, and audit logging.

---

## 8. Delivery Logistics Operations vs Marketplace Geography

1. **Separation of Concerns:**
   Logistics Hubs, Riders, Dispatch Consoles, and Waybills are internal logistics infrastructure. They are NOT customer-facing marketplace geography.
2. **Rider Assignments:**
   Riders are assigned to fulfillment batches and orders based on operational logistics rules, not manual arbitrary overrides.
3. **Delivery Exceptions Triage:**
   Delivery failures (damaged item, customer unavailable, bad address) must enter a structured triage queue with recorded reasons and resolutions.

---

## 9. Immutable Audit Logging (Non-Negotiable)

1. **First-Class Auditing:**
   Every sensitive mutation performed in Admin (fee changes, lane toggles, merchant suspensions, refunds, stock adjustments, role assignments) MUST write an immutable audit log record.
2. **Audit Record Schema:**
   Record: `admin_id`, `action`, `resource_type`, `resource_id`, `before_state_json`, `after_state_json`, `reason`, `ip_address`, `timestamp`.
3. **No Modification or Deletion:**
   Audit logs must be append-only. No Admin user (including Super Admin) may edit or delete audit logs.

---

## 10. Legacy Code Elimination & Single Implementation

1. **Legacy Concept Ban:**
   Do NOT expose or use obsolete 6Valley shipping concepts (`DeliveryCity`, `CartShipping`, flat-rate city shipping, manual zone pricing) in any active Admin flow.
2. **Unified Domain Services:**
   Admin controllers must call the same Repositories and Services (`DeliveryLaneService`, `OrderService`, `CashbackService`, `PickupService`) used by Customer and Vendor APIs.
3. **Clean Migration:**
   Any remaining legacy Admin views must be migrated or cleanly decoupled before dead code removal.

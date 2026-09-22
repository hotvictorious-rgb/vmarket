# VMarket Production Alignment Specification — Admin Panel ↔ Backend
# VMarket Admin Panel — Deep Production Alignment Plan
**Canonical Production Reference — V1**

## 0. The Purpose of Admin

The Admin Panel should answer five questions at all times:
1. **What is happening?**
2. **Why is it happening?**
3. **Who is allowed to change it?**
4. **What happens if Admin changes it?**
5. **Can we prove what happened afterward?**

The Admin Panel must therefore control the configuration and operations of VMarket, while the backend remains the final authority.

```
                    VMARKET BACKEND
                  SOURCE OF TRUTH
                         │
                         │
                  ADMIN PANEL
                         │
        ┌────────────────┼────────────────┐
        ↓                ↓                ↓
   CONFIGURATION     OPERATIONS        OVERSIGHT
        │                │                │
 Geography          Orders           Reports
 Merchants          Delivery         Audit
 Lanes              Pickup           Security
 Products           Returns          Monitoring
 Settings           Refunds
```

The Admin UI must never become a second business engine.

---

## 1. Admin Authentication

Admin access should be completely separate from normal customer access.

Admin login should establish:
```
Admin identity
      ↓
    Role
      ↓
 Permissions
      ↓
Optional branch/scope
      ↓
   Session
      ↓
Every subsequent request authorized by backend
```

Do not rely on:
```javascript
if (frontendRole == "admin")
```
for security.

The backend must independently authorize every sensitive action. Laravel's authorization system supports resource-specific policies and gates, which is appropriate for this separation.

### Production Requirements:
- Secure authentication (guards: `admin`)
- Session/token expiration
- Logout / revocation
- Failed-login protection & brute force mitigation
- Rate limiting on admin auth endpoints
- Optional MFA for privileged administrators
- Audit login events (success and failure)
- Audit sensitive actions
- No password / API secrets exposed to frontend
- No privilege escalation through request parameters

---

## 2. Admin Role Architecture

The existing concept of `Super Admin` and `Super Admin Employee` should become much more granular.

### Target Roles:
- `SUPER_ADMIN`
- `ADMIN_EXECUTIVE`
- `BRANCH_MANAGER`
- `SUPPORT_ADMIN`
- `ORDER_ADMIN`
- `PAYMENT_ADMIN`
- `FINANCE_ADMIN`
- `MERCHANT_ADMIN`
- `PRODUCT_ADMIN`
- `CONTENT_ADMIN`
- `DELIVERY_ADMIN`
- `DISPATCH_ADMIN`
- `CUSTOMER_SUPPORT`
- `REPORTING_ADMIN`
- `SECURITY_ADMIN`

Roles and permissions must remain separate concepts.

**Example:**
- **Role:** `ORDER_ADMIN`
- **Permissions:** `orders.view`, `orders.view_sensitive`, `orders.cancel`, `orders.approve_return`, `orders.approve_refund`

Do not make the frontend assume that role names themselves determine everything.

---

## 3. Permission System

Every meaningful Admin action should have an explicit permission.

### Example Permission Matrix:
- **Geography:**
  - `geography.country.view`, `geography.country.create`, `geography.country.update`
  - `geography.state.view`, `geography.state.create`, `geography.state.update`
  - `geography.lga.view`, `geography.lga.create`, `geography.lga.update`
- **Delivery:**
  - `delivery.lane.view`, `delivery.lane.create`, `delivery.lane.update`
  - `delivery.lane.enable`, `delivery.lane.disable`, `delivery.fee.update`
- **Merchants:**
  - `merchants.view`, `merchants.review`, `merchants.approve`
  - `merchants.reject`, `merchants.suspend`, `merchants.reinstate`
- **Orders:**
  - `orders.view`, `orders.cancel`, `orders.refund`, `orders.return`, `orders.override`
- **Finance:**
  - `payments.view`, `refunds.approve`, `settlements.view`, `reconciliation.view`

This ensures new employee roles can be created without rewriting the entire authorization system.

---

## 4. Dashboard

The dashboard should not be a collection of random statistics. It must be an operational command center.

### Main Operational Sections:
- Today's Orders
- Pending Payments
- Orders Awaiting Merchant Action
- Orders Awaiting Pickup Inspection
- Orders Awaiting Dispatch
- Orders In Transit
- Failed Deliveries
- Pending Returns
- Pending Refunds
- Pending Merchant Applications
- Low Stock Alerts
- Delivery Exceptions
- Payment Exceptions
- System Alerts

Each metric must be clickable (e.g., clicking *12 Pending Pickup Inspections* navigates directly to those filtered 12 records).

---

## 5. Dashboard Must Reflect Backend State

Do not calculate aggregates client-side:
```javascript
total_orders = frontend list.length // FORBIDDEN
```
The backend must provide authoritative aggregates via dedicated queries/services. The Admin frontend is strictly a presentation layer.

---

## 6. Geography Management

This is one of the most critical Admin modules.

### Canonical Marketplace Geography:
```
Country
   ↓
 State
   ↓
  LGA
```

Nothing customer-facing should introduce `Ward`, `Area`, `Route`, `Zone`, `Corridor`, or `Hub` as a marketplace geography layer.

### Admin Capabilities:
- **Countries:** create, edit, activate, deactivate, view states
- **States:** create, edit, activate, deactivate, view LGAs
- **LGAs:** create, edit, activate, deactivate, view delivery lanes

---

## 7. Geography Safety

Admin must not be able to create orphaned or mismatched geographical records:
- `LGA.state_id` must belong to `State.country_id` before saving.
- `State.country_id` must belong to the selected active country.
- Backend validates all foreign-key hierarchies server-side.

---

## 8. Delivery Lane Management

Admin manages directional delivery lanes:
```
Origin LGA → Destination LGA
```

**Directional Independence:**
- `Uyo → Uyo`
- `Uyo → Eket`
- `Eket → Uyo`

If `Uyo → Eket` is enabled and `Eket → Uyo` is disabled, Admin must see and configure each independently.

---

## 9. Delivery Lane Screen

Each lane display must show:
- **Origin:** Country, State, LGA
- **Destination:** Country, State, LGA
- **Financial & Operational Terms:** Delivery Fee, Estimated Delivery Time (ETA)
- **Status & Metadata:** Status (Active/Inactive), Created At, Last Updated At, Updated By Admin ID
- **Operational Linkage:** Active shops using this origin LGA, recent orders using lane, current active deliveries

---

## 10. Enable / Disable Delivery Lane

Disabling a delivery lane (e.g., `Uyo → Eket`) affects future fulfillment availability decisions immediately.

However, **it must never corrupt historical orders**. Existing orders that already hold `lane_id`, `fee`, `eta`, `origin_lga_id`, and `destination_lga_id` must preserve their checkout snapshot intact.

---

## 11. Delivery Fee Management

Admin controls the authoritative delivery fee.
- Customer cannot supply it.
- Vendor cannot supply it.
- Flutter cannot calculate it.

When Admin updates a lane fee (e.g., `Uyo → Eket` from ₦1,000 to ₦1,500), future checkouts immediately use the new value. Historical orders strictly retain their original snapshot.

---

## 12. Shop Management

Admin requires complete visibility into every shop:
```
Shop
 ├── Vendor
 ├── Branch
 ├── Country
 ├── State
 ├── LGA
 ├── Address
 ├── Coordinates (lat, lng)
 ├── Status (Active / Inactive / Suspended)
 ├── Pickup Capabilities
 ├── Products
 ├── Orders
 └── Delivery Origin (derived from Shop LGA)
```

Admin identifies: *"This shop is physically in Uyo LGA."* That shop automatically serves as an origin for `Uyo → Uyo`, `Uyo → Eket`, etc., strictly depending on enabled lanes.

---

## 13. Shop Location Change

Moving a shop's physical location (e.g., `Uyo LGA → Eket LGA`) is a high-impact operation:
- The system must not silently rewrite historical orders.
- Future fulfillment uses the new location.
- Historical orders preserve their original origin snapshot.
- The Admin UI must display an explicit confirmation warning: *"Changing this shop's LGA will alter future delivery availability and lane routing."*

---

## 14. Pickup Management

In-Shop Pickup is completely separate from delivery logistics. Admin oversees:
- Shop pickup eligibility
- Reservation permissions
- Pickup operating hours & instructions
- Operational status

Admin controls platform-level eligibility. Vendors manage permitted shop-level hours/notes, but vendors can never manipulate VMarket delivery lanes.

---

## 15. Merchant Management

Admin command center must provide:
- Merchant Applications
- Approved Merchants
- Rejected Applications
- Suspended Merchants
- Verification & KYC Documents
- Associated Shops, Branches, and Employees
- Products, Orders, Financial activity, and Pickup performance

---

## 16. Merchant Approval Workflow

```
Application ──► Review ──► Verification ──► Approve
      │
      ├──► Reject
      │
      └──► (Once Approved) ──► Suspend ──► Reinstate
```
Every status transition must record the actor, timestamp, and audit justification.

---

## 17. Merchant Suspension Consequences

Suspending a merchant has strict system consequences:
- Merchant suspended in backend.
- New customer purchases disabled immediately.
- Existing confirmed orders preserved and fulfillment continues per policy.
- Admin views outstanding financial and fulfillment obligations.
- Never hard-delete the merchant record.

---

## 18. Branch Management

Admin manages multitenant branch structure:
```
Vendor
 ├── Branch A
 ├── Branch B
 └── Branch C
```
Every branch maintains its own location, inventory allocations, branch employees, orders, and pickup capability.

---

## 19. Employee Management & Tenant Isolation

Admin manages vendor and branch staff without violating tenant boundaries.
- A Branch A employee must never access Branch B data via request manipulation (e.g. changing `branch_id`).
- Backend authorization strictly verifies principal ownership and operational scope.

---

## 20. Product Management

Admin product control covers:
- Products, Categories, Brands, Attributes, Images, Pricing, Stock visibility, Approval status, Merchant, and Shop.

### Product Lifecycle:
```
Draft ──► Submitted ──► Review ──► Approved ──► Published
                                      │
                                      └──► Suspended
```

---

## 21. Product Moderation

Admin can: approve, reject, request changes, suspend, restore, inspect merchant, inspect pricing, inspect images, and inspect category.

Admin must never silently mutate merchant-controlled product content without an audit log record.

---

## 22. Customer Management

Admin inspects customer profiles:
- Account profile, saved addresses, orders, payments, refunds, returns, cashback balance/ledger, support tickets, and security events.
- Sensitive customer data (PII, tokens) must only be accessible to permitted roles (`orders.view_sensitive` / `SUPER_ADMIN`).

---

## 23. Customer Account Actions

Admin can: View, Suspend, Reactivate, Verify, Review activity.
- Avoid unrestricted "Login as customer" impersonation.
- If impersonation is ever needed, it requires strong authorization, two-party approval, and comprehensive audit logging.

---

## 24. Order Management

Central operational module searchable by:
- Order ID, Customer, Vendor, Shop, Payment Reference, Delivery Status, Fulfillment Type (`delivery` vs `pickup`), Date Range, and Order Status.

---

## 25. Order Detail View

The Order Detail screen must present the full operational snapshot:
- Customer details, Vendor, Shop
- Line items, quantities, unit prices, discounts, subtotal
- Authoritative Delivery Fee (or Pickup notice)
- Victorious Cashback points applied / earned
- Grand total and payment reference
- Fulfillment type, Origin LGA, Destination LGA, Directional Lane
- Delivery rider / pickup inspection details
- Complete operational timeline

---

## 26. Order Timeline

Comprehensive audit timeline for customer support:
- 18:01 Checkout initiated
- 18:02 Payment initialized
- 18:03 Payment server-verified
- 18:03 Order record created
- 18:04 Stock deducted / reserved
- 18:10 Merchant accepted
- 18:40 Ready for dispatch
- 19:00 Rider assigned
- 19:20 Picked up from shop
- 20:05 Delivered to customer
- 20:06 Cashback awarded to ledger

---

## 27. Delivery Order Control

Admin tracks standard delivery states:
`Awaiting merchant` ──► `Ready for pickup` ──► `Assigned` ──► `Picked up` ──► `At hub` ──► `In transit` ──► `Out for delivery` ──► `Delivered` (or `Failed` / `Returned`).

Admin must not arbitrarily skip states. All state transitions must adhere to backend business rules.

---

## 28. Pickup Order Control

Admin monitors pickup lifecycle:
`Reservation created` ──► `Awaiting inspection` ──► `Inspection accepted / rejected` ──► `Payment pending` ──► `Payment verified` ──► `Pickup order created` ──► `OTP issued` ──► `Collected`.

Admin intervenes only through authorized, audited actions.

---

## 29. Pickup Inspection Auditing

Admin inspects pickup inspection outcomes:
- Customer, Shop, Product, Reservation ID, Inspection Result (`accepted` / `rejected`), Inspecting Vendor Employee, Timestamp, Rejection Reason.
- `inspected_rejected` must remain strictly distinguishable from customer cancellation.

---

## 30. Payment Management

Authoritative Payment Center:
- Payment requests, successful payments, failed payments, pending payments, Paystack reference tracking, verification status, and refunds.
- **Rule:** Never mark `payment_status = 'paid'` merely because a frontend redirect returned. Payment MUST be verified server-side with Paystack.

---

## 31. Payment Exception Center

Admin queue to detect and remediate edge cases:
- Payment initialized but order not created
- Payment callback received but verification failed
- Payment verified but settlement failed
- Duplicate webhooks received
- Amount mismatch between gateway and order
- Reference mismatch

---

## 32. Refunds

Authoritative refund workflow preserving:
- Original payment record, original payment gateway (Paystack), refund amount, reason, approver admin ID, timestamp, status, and provider refund reference.
- **Rule:** Refund to the original payment method where operationally supported.

---

## 33. Returns

Admin controls returns lifecycle:
`Return requested` ──► `Return approved / rejected` ──► `Item received at shop/hub` ──► `Inspection` ──► `Refund / Exchange` ──► `Completed`.

Never mutate database status without recording the reason and authorizing admin.

---

## 34. Cashback / Victorious Points

Admin monitors customer loyalty:
- Customer balance, points earned, points redeemed, associated order / pickup reservation, expiration dates, and ledger adjustments.
- Admin manual adjustments require: mandatory reason, authorized permission, and immutable audit record.
- **Invariant:** Points reduce eligible order totals at checkout; points are NOT a cash withdrawal wallet.

---

## 35. Inventory Visibility & Adjustments

Admin inspects stock posture:
- Product, Shop, Opening stock, Stock in, Stock out, Sold, Returned, Reserved (pickup), Available.
- Manual stock changes require an explicit adjustment workflow with quantity, reason, authorized user, and audit entry.

---

## 36. Delivery Operations vs Geography

Logistics operations are distinct from marketplace geography:
- Admin manages: Hubs, Riders, Dispatchers, Batching, Waybills, Delivery Exceptions.
- **Invariant:** `Hub ≠ LGA`. `Route ≠ Marketplace Geography`. Customers choose destination LGA, not logistics hubs.

---

## 37. Rider Management

Admin tracks:
- Rider profile, status (Active/Offline), assigned hub, current workload, active delivery assignments, completed deliveries, and delivery failure rates.

---

## 38. Dispatch Operations

Dispatchers assign and monitor:
- Ready orders, available riders, batch assignments, hub transfers, and transit exceptions. Cross-scope unauthorized assignments are blocked server-side.

---

## 39. Delivery Exceptions Queue

Dedicated triage for delivery failures:
- Customer unavailable, wrong address, rider breakdown, merchant delay, damaged package, failed delivery attempt, lane interruption, system error.
- Each exception records: status, assigned owner, reason, resolution action, timestamp.

---

## 40. Notifications Oversight

Admin inspects platform communication events:
- Order confirmations, payment receipts, merchant dispatch notices, pickup OTPs, rider assignments, delivery completion notices, refund/return updates.

---

## 41. Customer Support Center

Unified support view linking:
`Customer Profile ──► Support Case ──► Order ──► Payment ──► Fulfillment ──► Resolution`.
Support staff operate with scoped read/write permissions without broad financial powers.

---

## 42. Audit Log (First-Class Production System)

Every sensitive Admin action must generate an immutable audit log entry containing:
- **Who:** Admin ID, Name, Role
- **What:** Action executed (e.g., `delivery_lane.disabled`, `merchant.suspended`, `fee.updated`)
- **When:** UTC Timestamp
- **Where:** Resource Type & Resource ID
- **Before:** JSON state before mutation
- **After:** JSON state after mutation
- **Reason:** Mandatory user-supplied justification
- **Client IP & User Agent**

---

## 43. Audit Log Immutability

Admin users (including Super Admins) must NOT be permitted to edit, prune, or delete audit logs through the web interface. Audit trails must remain permanently verifiable.

---

## 44. Reporting

Standardized reporting modules:
- **Sales & Orders:** GMV, Net Sales, Order Volume, Average Order Value (AOV), Completed vs Cancelled.
- **Merchants:** Active merchants, pending applications, merchant sales volume, product counts.
- **Customers:** New vs active customers, order frequency, repeat rate.
- **Delivery:** Delivered, failed, average delivery duration, lane volume/performance.
- **Pickup:** Reservations created, accepted, rejected, completed.
- **Payments:** Successful, failed, refunded, pending volume.

---

## 45. Standard Metric Definitions

Every business metric must have a single authoritative mathematical definition:
- $\text{GMV} = \sum (\text{item\_price} \times \text{qty}) + \text{delivery\_fees}$
- $\text{Net Sales} = \text{GMV} - \text{Refunds} - \text{Discounts}$
- Platform Revenue $\neq$ GMV.
- Merchant Payable $\neq$ Net Sales.
- Delivery Revenue is tracked separately from product GMV.

---

## 46. System Settings

Admin configures marketplace operational variables:
- Marketplace active status, supported countries, order limits, cashback calculation percentages (5% funded from 10% platform commission), pickup reservation windows, default notification channels.
- All configuration changes require permission checks and audit logging.

---

## 47. Feature Flags

Production feature flags allow controlled gradual rollout:
- `pickup_enabled`, `cashback_enabled`, `new_checkout_enabled`, `new_delivery_engine_enabled`.
- Feature flags must not be used as an excuse for duplicate or fragmented backend engines.

---

## 48. Legacy Code Control

Admin views must NOT expose obsolete 6Valley concepts:
- Deprecate and hide `DeliveryCity`, `CartShipping`, and flat-rate city shipping interfaces.
- The UI must exclusively utilize canonical `Country → State → LGA` and directional `DeliveryLane`.

---

## 49. Admin API Rule (Unified Domain Services)

Admin Web must consume the same authoritative backend domain services as Customer and Vendor apps:
```
                  DOMAIN SERVICES
                       │
          ┌────────────┼────────────┐
          ↓            ↓            ↓
      Customer      Vendor        Admin
         API          API          Web/API
```
Different authorization contexts, but **the exact same business truth**.

---

## 50. Admin + Customer Alignment

When Admin modifies a lane (e.g. `Uyo → Eket` at ₦1,500), the Customer App immediately reflects `available: true` and `fee: 1500`. No client-side price or availability overrides.

---

## 51. Admin + Vendor Alignment

When Admin approves or suspends a merchant/shop, the Vendor App, Customer App, and Delivery system immediately reflect that identical status without desynchronization.

---

## 52. Admin + Delivery Logistics Alignment

Delivery operations only receive orders whose lane routing was validated at checkout. Delivery personnel cannot arbitrarily invent new transit lanes outside Admin configuration.

---

## 53. Admin + Payment Alignment

Admin payment monitoring relies strictly on server-side Paystack verification. Admin cannot manually create fake "paid" records without real gateway settlement.

---

## 54. Admin + Inventory Alignment

Inventory levels reflect order settlement reservations and adjustments. Admin does not maintain a separate stock tracking formula.

---

## 55. Admin + Checkout Alignment

If Admin alters or disables a lane while a customer is browsing, the checkout process re-validates the lane server-side at order placement time.

---

## 56. Critical Concurrency Scenario

If an Admin disables `Uyo → Eket` while a customer is on the payment screen:
- The backend checkout authorization must revalidate lane status upon payment callback.
- Stale frontend availability states cannot force an order through an inactive lane.

---

## 57. Snapshot Preservation

Historical orders must permanently retain immutable snapshots of:
- `origin_lga_id`, `destination_lga_id`, `lane_id`, `delivery_fee`, `eta`, `fulfillment_type`, `shop_id`, `unit_price`.
- Updating lane fees tomorrow never alters the recorded fee of an order completed yesterday.

---

## 58. Global Admin Search

Unified global search index supporting:
- Order ID, Customer Name/Email/Phone, Merchant/Shop Name, Product SKU/Title, Payment Reference, Rider Name/Phone, Delivery Waybill, Pickup Reservation OTP. All results respect active Admin permission boundaries.

---

## 59. Bulk Operations Safety

Destructive bulk actions (bulk suspend, bulk delete, bulk disable, bulk price change) require:
1. Record selection
2. Impact preview (showing affected shops/orders)
3. Explicit two-step confirmation
4. Atomic execution
5. Comprehensive audit trail

---

## 60. Data Retention & Soft Delete Policy

Commerce and financial records must never be hard-deleted:
- Use statuses (`active`, `inactive`, `suspended`, `cancelled`, `archived`) or Soft Deletes.
- Financial transactions, orders, ledgers, and delivery waybills remain permanently intact for audit integrity.

---

## 61. State Machine Enforcement

Admin UI must not present arbitrary status dropdowns. Transitions must expose only valid next actions based on backend state machines (e.g., `pending` can transition to `approved` or `rejected`, never directly to `delivered`).

---

## 62. Meaningful Error Handling

Admin actions must return clear, descriptive validation messages:
- *"Lane cannot be disabled because active deliveries are currently in transit."*
- *"Refund already processed via Paystack reference."*
- Never expose unhandled database exceptions or raw stack traces.

---

## 63. Admin Permission Testing

Rigorous verification of authorization boundaries:
- `SUPER_ADMIN` has full platform scope.
- `ORDER_ADMIN` can view and manage orders, but cannot adjust delivery lane fees or alter system settings.
- `FINANCE_ADMIN` manages payments and refunds, but cannot publish products.
- Unauthorized access attempts must return HTTP 403 Forbidden.

---

## 64. Cross-Tenant Security Verification

Branch and vendor scopes must be enforced server-side. An admin assigned to `Branch A` must never view or modify `Branch B` orders or stock by altering query parameters.

---

## 65. Never Trust Request IDs

Backend authorization must verify that the authenticated administrator has explicit authority to act on the target resource ID (`shop_id`, `order_id`, `lane_id`). Request parameters are identifiers, never proof of authorization.

---

## 66. Production Health Monitoring

Admin command center provides observability into:
- API response times, queue backlog, webhook failures, failed background jobs, payment gateway error rates, notification delivery failures.

---

## 67. Deployment Safety

Deployment sequence for Admin and backend updates:
1. Automated syntax & test suite validation (`php -l`, PHPUnit)
2. Database migration verification (zero breaking schema changes)
3. Database backup snapshot
4. Safe overlay deployment
5. `php artisan optimize:clear`
6. Post-deployment operational health check

---

## 68. Authoritative Admin Navigation Hierarchy

```
ADMIN
│
├── Dashboard (Operational Command Center)
│
├── Orders
│   ├── All Orders
│   ├── Delivery Orders
│   ├── In-Shop Pickup Orders
│   ├── Returns
│   └── Refunds
│
├── Customers
│   ├── Customer Directory
│   ├── Wallets & Ledgers
│   └── Verification
│
├── Merchants
│   ├── Merchant Applications
│   ├── Active Merchants
│   ├── Shops Directory
│   └── Merchant Employees
│
├── Products
│   ├── Product Catalog
│   ├── Categories
│   ├── Brands
│   └── Moderation Queue
│
├── Fulfillment & Geography
│   ├── Countries
│   ├── States
│   ├── LGAs
│   ├── Delivery Lanes (Directional Origin → Destination)
│   └── Pickup Configuration
│
├── Delivery Logistics Operations
│   ├── Logistics Hubs
│   ├── Riders
│   ├── Dispatch Console
│   ├── Active Assignments
│   └── Delivery Exceptions Queue
│
├── Payments & Settlement
│   ├── Transactions
│   ├── Payment Exceptions
│   ├── Refunds Processing
│   └── Gateway Reconciliation (Paystack)
│
├── Inventory Oversight
│   ├── Stock by Shop
│   └── Stock Adjustment Log
│
├── Victorious Cashback
│   ├── Customer Points Ledger
│   └── Reward Rules (5% funded from 10% commission)
│
├── Support & Communications
│   ├── Support Cases
│   └── Notification History
│
├── Reports & Analytics
│   ├── Sales & GMV
│   ├── Delivery Performance
│   └── Merchant Performance
│
├── Governance & Security
│   ├── Staff Accounts & Roles
│   ├── Permissions Matrix
│   ├── Immutable Audit Logs
│   └── System Health & Queues
│
└── System Settings
    ├── Platform Configuration
    └── Feature Flags
```

---

## 69. The Core Architectural Invariant

The Admin Panel must **control the system without becoming a disconnected second system**.

```
                         DATABASE
                            │
                            ↓
                    DOMAIN / SERVICES
                            │
              ┌─────────────┼─────────────┐
              ↓             ↓             ↓
         CUSTOMER API    VENDOR API    ADMIN WEB/API
              │             │             │
              ↓             ↓             ↓
         CUSTOMER APP    VENDOR APP    ADMIN WEB
```

All three client platforms ultimately obey the exact same domain business rules, preventing split-brain discrepancies across customer, merchant, and admin.

---

## 70. AI Execution Protocol Before Modifying Admin

Before writing any new Admin features or modifying existing views:
1. Conduct an exhaustive repository-wide deep scan of branch `v1`.
2. Inspect every Admin route (`routes/admin/`), controller (`app/Http/Controllers/Admin/`), Blade view (`resources/views/admin-views/`), model, middleware, and migration.
3. Formulate an **Admin Impact Map** classifying every capability as `KEEP`, `MODIFY`, `MIGRATE`, `DEPRECATE`, `REMOVE`, or `MISSING`.
4. Enforce server-side authorization and snapshot immutability.
5. Reconcile Admin web controllers with shared domain services.
6. Commit all changes atomically with full documentation in `AI_CHANGELOG.md`.

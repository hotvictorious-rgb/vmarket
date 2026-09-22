# Customer App ↔ Backend Production Alignment Rules

> **MANDATORY FOR ALL AIs.**
> These rules apply **unconditionally** whenever any AI makes changes to the Customer App (`User app/`), any Customer App API contract in the backend (`backend/vmarket-web/`), or any shared model, repository, or service consumed by the Customer App. No AI may bypass, skip, or partially apply these rules.

> [!IMPORTANT]
> **CANONICAL SPEC — READ FIRST:** Before acting on this rule file, every AI MUST read the full 77-section canonical specification:
> `.agents/rules/VMARKET_CUSTOMER_APP_SPEC.md`
>
> This spec is the **target production contract**. It is not a claim that everything is already implemented correctly in v1. The AI must **audit current code against the spec**, identify mismatches, then fix them **without creating duplicate systems**.
>
> The 34-phase execution plan is in: `.agents/rules/CUSTOMER_APP_ALIGNMENT_PLAN.md`

---

## 0. Prime Directive

> **Do not redesign VMarket while performing alignment.**
> Do not create duplicate business logic. Do not replace working architecture merely because another implementation looks cleaner. First inspect the existing backend and Customer App, identify the authoritative implementation, map every dependency, then make the **smallest changes necessary** to make frontend and backend conform to the agreed VMarket architecture. Remove obsolete implementations **only after** proving their callers have migrated.

The Customer App is a **client** of the backend. It is **not** a second implementation of VMarket.

---

## 1. Authoritative Architecture

Every customer action must have:

- **One** authoritative backend behavior
- **One** API contract
- **One** frontend implementation
- **One** predictable result

No legacy endpoint, duplicate business rule, fake frontend calculation, or disconnected screen may remain.

---

## 2. Frozen Target Architecture

### Geography (canonical — no exceptions)

```
Country -> State -> LGA
```

**Banned from customer-facing flows:** Hub, Route, Corridor, Ward, Area, Zone (for marketplace fulfillment).

### Delivery

```
Shop Origin LGA -> Destination LGA -> Directional Delivery Lane -> Available/Unavailable -> Fee + ETA
```

- `Uyo -> Eket` and `Eket -> Uyo` are **separate, independent capabilities**.
- Flutter **only renders** backend fulfillment responses — it **never calculates** delivery fees, availability, or ETAs.

### Pickup

```
Shop -> Pickup enabled? -> Reservation allowed? -> Inspection -> Payment -> Order -> OTP -> Collection
```

Pickup does **not** use delivery lanes.

---

## 3. Mandatory Pre-Change Protocol

Before making **any** change to the Customer App or its backend contracts, the AI MUST:

1. **Read `AGENTS.md`** — engineering rules and governance.
2. **Read `AI_CHANGELOG.md`** — recent AI modifications.
3. **Read `CHANGE_IMPACT_PROTOCOL.md`** — 6-point impact checklist.
4. **Read `ARCHITECTURE.md`** — system topology.
5. **Identify the authoritative implementation** for the feature being changed.
6. **Map all callers and dependencies** of the code being touched.
7. **Confirm the phase** from the alignment plan that covers this change.

---

## 4. Strict Layer Rules

### Backend is the Source of Truth

The backend owns and **exclusively controls**:

- Prices
- Stock levels
- Seller and shop ownership
- Delivery fee calculation
- Fulfillment availability
- Cashback eligibility and amounts
- OTP generation and verification
- Payment verification
- Order creation and state transitions
- Return/refund/exchange authorization

### Flutter Only Renders

The Customer App may **display** backend data. It may **never**:

- Calculate or override prices
- Calculate delivery fees
- Calculate cashback
- Assume payment success from a client-side callback
- Invent order states not returned by the backend
- Reconstruct an order from cart data after payment
- Trust `customer_id` submitted by the client for ownership checks

---

## 5. API Contract Rules

For every endpoint touched, the AI must verify that:

| Dimension | Rule |
|---|---|
| URL | Frontend and backend use the same route |
| HTTP Method | Identical |
| Authentication | All protected endpoints derive customer from auth token, not from client-supplied ID |
| Request body field names | Exactly matching (no `delivery_fee` vs `shipping_cost` mismatch) |
| Response structure | Frontend model fields match backend serializer output exactly |
| Error format | Backend returns `{ "success": false, "code": "...", "message": "..." }` |
| Pagination | Same format on both sides |

**Any mismatch found must be resolved before the change is committed.**

---

## 6. Authentication Rules

- Every protected endpoint must derive the customer from the authenticated session.
- The client may **never** supply `customer_id` for ownership-sensitive operations.
- Token expiry, invalid token, disabled account, and unauthorized access must be handled on both sides.

---

## 7. Address & Geography Rules

- Customer addresses must use `country_id -> state_id -> lga_id -> address`.
- Backend must validate: state belongs to country, LGA belongs to state, all active, customer owns address.
- Any address submission with mismatched geography must be **rejected by the backend**.

---

## 8. Fulfillment Availability Rules

- Backend checks availability on every checkout intent — the prior availability response from the frontend is **not trusted**.
- Delivery availability is **directional**: `Origin LGA -> Destination LGA` is independent from `Destination LGA -> Origin LGA`.
- Checkout snapshots must capture all fulfillment parameters at the moment of creation and must be **immutable** to subsequent admin configuration changes.

---

## 9. Payment Rules

- Payment is **never** confirmed by a Flutter callback alone.
- The authoritative payment flow: `Paystack -> Backend webhook/verification -> PaymentRequest -> Settlement -> Order`.
- The double-execution guard (`$affected > 0`) must be present on every payment callback.
- Every payment failure scenario (cancel, timeout, crash, duplicate, webhook delay, amount mismatch) must be handled by querying backend state.

---

## 10. Stock Rules

- Stock is deducted **only** at successful payment settlement.
- Reservation alone does **not** deduct stock (unless the model explicitly specifies otherwise).
- Stock race conditions (two customers, stock = 1) must be handled with pessimistic locking (`lockForUpdate()`).

---

## 11. Pickup Security Rules

- Customer A cannot access, pay, or modify Customer B's reservation.
- Vendor Branch 1 cannot inspect Vendor Branch 2's reservations.
- OTP must be backend-generated and backend-verified.
- Pickup collection requires backend authorization — Flutter cannot unilaterally mark a pickup as collected.

---

## 12. Security Rules (IDOR & Injection)

Every customer endpoint must scope queries to the authenticated customer. The following attacks must all fail safely:

- Customer A accessing Customer B's order/address/reservation
- Fake shop ID / seller ID
- Fake delivery fee / origin LGA / destination
- Fake cashback amount
- Fake price
- Accessing another customer's payment

---

## 13. Order State Machine Rules

- Frontend renders backend-returned states only.
- Frontend does **not** invent its own state machine.
- Delivery states: `PENDING PAYMENT -> PAID -> PROCESSING -> READY FOR DELIVERY -> OUT FOR DELIVERY -> DELIVERED`
- Pickup states: `PENDING INSPECTION -> INSPECTION ACCEPTED -> AWAITING PAYMENT -> PAID -> READY FOR COLLECTION -> COLLECTED`
- Pickup rejection: `PENDING INSPECTION -> INSPECTION REJECTED`

---

## 14. Legacy Cleanup Rules

Before any change, search the Customer App for legacy calls:

```
digital-payment / CartShipping / shipping_cost / delivery_city / delivery_state / delivery_hub
```

Classify every legacy item as: `KEEP`, `MIGRATE`, `DEPRECATE`, or `REMOVE`.

**There must never be two simultaneously valid ways to perform checkout, shipping calculation, or payment.**

---

## 15. Mandatory Phase Completion Checklist

After **every** change to the Customer App or its backend contracts, the AI must produce a report covering all 12 items:

1. Files changed
2. APIs changed
3. Database changes
4. Frontend/backend contract changes
5. Tests added
6. Tests executed
7. Legacy code removed
8. Legacy code intentionally retained (with reason)
9. Security issues found
10. Remaining mismatches
11. Git diff reviewed
12. No unrelated changes confirmed

**A change is not complete without this report.**

---

## 16. The 34-Phase Alignment Plan

All work on the Customer App must follow the 34-phase alignment plan:

| Phase | Scope |
|---|---|
| 0 | Freeze target architecture |
| 1 | Repository-wide customer/backend inventory |
| 2 | API contract audit |
| 3 | Authentication lifecycle |
| 4 | Customer profile |
| 5 | Address system |
| 6 | Product browsing |
| 7 | Product detail |
| 8 | Cart |
| 9 | Multi-vendor cart |
| 10 | Fulfillment availability |
| 11 | Delivery scenario matrix |
| 12 | Pickup flow |
| 13 | Pickup security |
| 14 | Checkout intent |
| 15 | Checkout snapshot |
| 16 | Payment |
| 17 | Payment failure scenarios |
| 18 | Stock |
| 19 | Order creation |
| 20 | Order state machine |
| 21 | Order details |
| 22 | Delivery tracking |
| 23 | Pickup collection (OTP) |
| 24 | Cashback |
| 25 | Returns / refunds / exchanges |
| 26 | Notifications |
| 27 | Error contract |
| 28 | Offline/network behavior |
| 29 | Security testing |
| 30 | Legacy cleanup |
| 31 | Frontend model cleanup |
| 32 | Production test matrix |
| 33 | Golden end-to-end tests |
| 34 | Definition of "aligned" |

Full plan details are in `.agents/rules/CUSTOMER_APP_ALIGNMENT_PLAN.md`.

---

## 17. Recommended Execution Order

Do not give an AI a single giant instruction to "fix everything." Execute in controlled phases:

```
Phase 1: Repository deep scan
    -> Phase 2: Customer API inventory
    -> Phase 3: Backend API contract audit
    -> Phase 4: Auth / profile / address
    -> Phase 5: Catalog / product / cart
    -> Phase 6: Fulfillment
    -> Phase 7: Checkout
    -> Phase 8: Payment
    -> Phase 9: Orders
    -> Phase 10: Pickup
    -> Phase 11: Delivery tracking
    -> Phase 12: Cashback
    -> Phase 13: Returns / refunds / exchanges
    -> Phase 14: Security
    -> Phase 15: Legacy cleanup
    -> Phase 16: End-to-end tests
    -> Phase 17: Production readiness
```

---

## 18. The 10 Golden End-to-End Tests (Mandatory Before Production)

| # | Scenario | Required Result |
|---|---|---|
| 1 | Uyo customer + Uyo shop + Uyo->Uyo lane enabled | Delivery available |
| 2 | Uyo shop + Eket customer + Uyo->Eket enabled | Delivery available |
| 3 | Eket shop + Uyo customer + Eket->Uyo **disabled** | Delivery **unavailable** |
| 4 | Reserve -> Inspection accepted -> Paystack -> Verify -> Order -> Stock deduction -> OTP -> Collect | Full pickup flow |
| 5 | Shop A (Delivery) + Shop B (Pickup) — one checkout, two order groups | Mixed fulfillment works |
| 6 | Two payment attempts on same checkout | Only one valid settlement |
| 7 | Stock = 1, two simultaneous customers paying | Only one succeeds |
| 8 | Availability = YES -> admin disables lane -> checkout intent | Checkout rejected |
| 9 | Customer A attempts Customer B order/address/reservation | 403/404 |
| 10 | Paystack succeeds -> app crashes -> reopen -> fetch backend state | Correct order/payment shown |

---

## 19. The Final Gate

Before moving from Customer App to Vendor App, the following statement must be provably true:

> *For every customer journey supported by VMarket, the Customer App and backend have been traced from **screen -> API -> controller -> service -> database -> response -> screen**, tested for success/failure/security/concurrency, and there is **exactly one authoritative implementation**.*

No AI may declare the Customer App production-ready without satisfying this gate.

---

## 20. Git Commit Requirement

All Customer App changes must be committed per the standard in `AGENTS.md`:

```
<type>(user-app): <short description> [AI]

- Bullet point of what changed
- Another bullet point
```

The `AI_CHANGELOG.md` must be updated **before** the commit, so the changelog is part of the commit.

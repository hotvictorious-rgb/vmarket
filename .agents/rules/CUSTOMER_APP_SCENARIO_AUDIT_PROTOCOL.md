# Victorious MARKET Customer App ↔ Backend Scenario Audit Protocol

**Authoritative Governance Document — Branch: `v1`**
**Scope:** Customer Mobile App (`User app/`) ↔ Laravel Web Backend (`backend/vmarket-web/`)

---

## 1. Core Operating Principle: Backend Is Authoritative SSOT
The Customer App is strictly a presentation and interaction client. It MUST NEVER:
1. Calculate delivery fees, shipping rates, or taxes locally.
2. Determine fulfillment channel eligibility (Delivery vs Pickup) via client heuristics.
3. Treat client-side UI success as equivalent to backend state transitions.
4. Conflate `seller_id` with `shop_id` (a merchant can have multiple shops).
5. Expose raw inventory quantities to shoppers (only "In Stock" vs "Out of Stock").
6. Assume local cart quantity or price remains valid without real-time backend validation.
7. Depend on legacy shipping models (`CartShipping`, `shipping_method`, `CategoryShippingCostService`, `delivery_hub_id`, etc.) for marketplace checkout.

```
                    CUSTOMER APP
                         │
                         │ API Contracts
                         ↓
                  VMARKET BACKEND
                         │
              SOURCE OF TRUTH (SSOT)
                         │
       ┌─────────────────┼─────────────────┐
       ↓                 ↓                 ↓
   Database          Business Rules     Services
       │                 │                 │
       └─────────────────┼─────────────────┘
                         ↓
                  Authoritative Result
                         │
                         ↓
                    CUSTOMER APP
```

---

## 2. Phased Audit Roadmap (C1 → C9)

* **Phase C1 — Customer Foundation:** Authentication (Register, Login, 6-digit OTP, Session Storage), Geography (`Country → State → LGA`), Address Creation, Address Selection Scoping, Customer Profile.
* **Phase C2 — Discovery:** Catalog Browsing, Categories, Brand, Product Search, Merchant/Shop Display, Stock Privacy ("In Stock" / "Out of Stock").
* **Phase C3 — Cart:** Cart Storage, Add/Remove/Update Quantity, Stale Stock Revalidation, Multi-Vendor Grouping.
* **Phase C4 — Fulfillment:** Directional Delivery Lanes (`Uyo → Uyo`, `Uyo → Eket`, `Eket → Uyo`, Unsupported Lane), In-Shop Pickup Availability, Mixed-Fulfillment Cart Partitioning.
* **Phase C5 — Checkout & Payment:** Two-Phase Checkout Intent (`POST /checkout/intent`), Payment Initialization (`POST /checkout/intent/{id}/pay`), Paystack WebView/Redirect, Server Webhook/IPN Lock, Ambiguous Transport Recovery, Network Drop Resiliency.
* **Phase C6 — Orders:** Delivery Order Lifecycle, In-Shop Pickup Reservation Lifecycle (`pending_inspection` → `inspected_accepted` / `inspected_rejected` → Pay at store → Handover), Order History, Detail, Real-time Tracking, 6-digit Delivery/Pickup OTP Handover.
* **Phase C7 — Post-Order:** 5% Victorious Cashback Awarding (settled transactions only), Return/Refund/Exchange Eligibility Contract, Ticket/Support Lifecycle.
* **Phase C8 — Production Abuse Testing:** Zero-Trust IDOR Authorization Scoping, Tamper Detection (fake fee, fake price, fake origin LGA, fake shop), Duplicate Submission Idempotency, Concurrency Race Condition Guards.
* **Phase C9 — Legacy Cleanup:** Systematic identification, classification (`KEEP`, `MIGRATE`, `DEPRECATED`, `REMOVE`), and eradication of dead shipping models and legacy routes.

---

## 3. The 22-Step Scenario Audit Checklist
For EVERY scenario across Phases C1–C9, the AI MUST execute and document the following 22-point inspection:
1. **Identify Flutter Screen:** Exact file path and line numbers.
2. **Identify Flutter Controller & Provider:** State management binding.
3. **Identify Flutter Service & Repository:** DTO and remote data source.
4. **Identify Flutter Models:** Request payload and response serialization.
5. **Identify Backend API Route:** Exact URI and HTTP verb in `routes/rest_api/v1/api.php`.
6. **Identify Backend Controller:** Handler method in `app/Http/Controllers/RestAPI/v1/`.
7. **Identify Authoritative Backend Service:** Service class enforcing domain logic.
8. **Identify Authoritative Database Fields:** Tables, columns, and foreign keys.
9. **Compare Request Schema:** Field names, data types, validation rules.
10. **Compare Response Schema:** Field names, data types, nullability.
11. **Compare Authentication & Scope:** Token guard, principal scoping (`where customer_id = ?`).
12. **Compare Error Contracts:** HTTP status codes, structured error messages.
13. **Compare Loading & Shimmer States:** UX during latency and offline transitions.
14. **Compare Retry & Idempotency Behavior:** Idempotency keys, replay safety.
15. **Compare Financial Formulations:** Exact decimal-string formatting, zero floating-point drift ($\Delta = 0.00$).
16. **Compare Fulfillment Behavior:** Directional lanes, pickup slots.
17. **Compare Order State Transitions:** Allowed state machine transitions.
18. **Compare Security & Ownership:** IDOR protection, CSRF/anti-tamper tokens.
19. **Identify Stale / Legacy Implementations:** Old endpoints or legacy database columns.
20. **Identify Missing Implementations:** Unimplemented contract hooks or UI dead ends.
21. **Identify Contradictory Logic:** Code conflicts between frontend and backend.
22. **Execute Remediation & Verification:** Code edits, syntax check (`flutter analyze` / `php -l`), proof logging, and atomic commit.

---

## 4. Standard Scenario Alignment Matrix Format

```markdown
### SCENARIO [Number]: [Scenario Title]

#### CUSTOMER APP:
* **Screen:** `User app/lib/...`
* **Controller:** `User app/lib/...`
* **Service / Repository:** `User app/lib/...`
* **Request Payload:** `{ ... }`
* **Response Model:** `ModelClass`

#### BACKEND:
* **Route:** `[VERB] /api/v1/...`
* **Controller:** `App\Http\Controllers\...`
* **Service:** `App\Services\...`
* **Database Tables:** `table_name (column_a, column_b)`
* **Business Invariant:** ...

#### ALIGNMENT MATRIX:
| Dimension | Status | Verified Finding / Mismatch Note |
| :--- | :---: | :--- |
| **Authentication** | PASS / FAIL / NA | ... |
| **Request Schema** | PASS / FAIL | ... |
| **Response Schema** | PASS / FAIL | ... |
| **Price / Currency**| PASS / FAIL | ... |
| **Stock / Privacy** | PASS / FAIL | ... |
| **Geography** | PASS / FAIL | ... |
| **Fulfillment** | PASS / FAIL | ... |
| **Payment / Lock** | PASS / FAIL | ... |
| **Order Lifecycle** | PASS / FAIL | ... |
| **Error Handling** | PASS / FAIL | ... |
| **Idempotency** | PASS / FAIL | ... |
| **Security / IDOR** | PASS / FAIL | ... |

#### OVERALL STATUS: [ALIGNED / PARTIAL / BROKEN]
#### REMEDIATION ACTION:
- [ ] Task 1
- [ ] Task 2
```

---

## 5. Controlled Execution Guardrail
The AI is strictly prohibited from:
- Attempting to fix all scenarios in a single monolithic commit.
- Inventing new marketplace features outside this protocol.
- Leaving any verified mismatch undocumented.
Every phase MUST complete its audit matrix, execute necessary code fixes, verify zero syntax errors, log changes in `AI_CHANGELOG.md`, update `VICTORIOUS_MARKET_MATHEMATICAL_AND_SYSTEMIC_PROOF.md`, and commit atomically to Git.

# Victorious MARKET — 3-AI Communication Protocol (Backend → Frontend → Reviewer)

> **MANDATORY GOVERNANCE FOR ALL WORK. Exactly 3 independent AIs. No other roles exist.**
> 1. **BACKEND AI** (Stage 1) — Laravel PHP logic only. 2. **FRONTEND AI** (Stage 2) — all UI (Flutter + Blade + theme assets). 3. **REVIEWER AI** (Stage 3) — reviews everything, edits nothing.
> Every feature flows `BACKEND_DONE → FRONTEND_DONE → REVIEWER APPROVED → release`. Skipping a stage is forbidden.

---

## 1. Prime Invariants

1. **Backend is the Sole Authority (SSOT)**:
   - Laravel Backend is the **only** entity authorized to calculate money, taxes, delivery fees, commissions, inventory deductions, discount validations, cryptographic OTPs, and state machine transitions.
   - Frontend AI is **strictly prohibited** from client-side calculation engines or invented order states.

2. **Zero Hallucinated APIs**:
   - Frontend AI cannot invent endpoints, query parameters, or JSON keys.
   - Every endpoint consumed must be registered in `.agents/sync/API_CONTRACT_REGISTRY.md` or fulfilled via an inbox ticket.

3. **No Direct UI-to-UI Cross-Wiring**:
   - Customer App, Vendor App, Delivery App, Storefront, Admin never talk to each other directly.
   - All state flows through the central backend API.

4. **Strict Ownership Isolation**:
   - Backend AI: `backend/vmarket-web/app/**`, `routes/**`, `config/**`, `database/**` ONLY. Never `User app/`, `Vendor app/`, `Delivery Man App/`, `resources/views/**`, `public/assets/**`.
   - Frontend AI: `User app/**`, `Vendor app/**`, `Delivery Man App/**`, `backend/vmarket-web/resources/views/**`, `backend/vmarket-web/public/assets/**` ONLY. Never backend PHP logic.
   - Reviewer AI: `.ai/reviews/**` ONLY. Never implementation code or tests.
   - Control Zone (`.ai/*.md` rules, `.ai/agents/*`, templates, schemas, `scripts/**`, CI, `CODEOWNERS`): human only.

5. **Strict Commit Isolation (COMMIT ONLY YOUR OWN CHANGES, NEVER ALL FILES) ⚠️**:
   - Every AI MUST explicitly stage ONLY its own files (e.g. `git add path/to/my/file.php AI_CHANGELOG.md`).
   - `git add .`, `git add -A`, `git commit -a` are **STRICTLY FORBIDDEN**.
   - Leave the other AIs' files dirty. NEVER `git restore .`, `git checkout -- .`, or `git clean`.

6. **Backend Server Lifecycle & Local Testing Authority**:
   - Backend AI runs the central local server (`http://127.0.0.1:8000`).
   - Frontend AI does NOT launch PHP/server processes; Storefront (`/`) and Admin (`/admin`) are live via backend server.
   - Flutter apps point `baseUrl` to `http://127.0.0.1:8000` (or `http://10.0.2.2:8000` on Android emulator).

---

## 2. The 3-Stage Pipeline

```
[STAGE 1: BACKEND AI] ──BACKEND_DONE──> [STAGE 2: FRONTEND AI] ──FRONTEND_DONE──> [STAGE 3: REVIEWER AI] ──APPROVED──> release
       backend/VM-XXX branch                       frontend/VM-XXX branch                         reviewer report @ exact SHA
```

- Branch scheme: `backend/VM-<FEATURE>-NNN` (Backend AI), `frontend/VM-<FEATURE>-NNN` (Frontend AI), `reviewer/VM-<FEATURE>-NNN` (review metadata, `.ai/` files only).
- Ticket flow: `BACKLOG → READY → BACKEND_DOING → BACKEND_DONE → FRONTEND_DOING → FRONTEND_DONE → UNDER_REVIEW → APPROVED | CHANGES_REQUIRED`. A `CHANGES_REQUIRED` verdict returns to the owning stage; any new commit invalidates prior approvals/results.
- Reviewer verdict is exactly `APPROVED` or `CHANGES_REQUIRED`, bound to the exact commit SHA.
- Escalation: 3 review cycles, same finding twice, 2 failed integrations at same stage, or >5 days in state → `Blocked: yes (ESCALATED)` + DECISION_REQUEST → human decides.

---

## 3. Dedicated Communication Channels

All channels reside in `.agents/sync/`:

| Channel File | Target Surface (all owned by Frontend AI) | Primary Focus |
| :--- | :--- | :--- |
| `API_CONTRACT_REGISTRY.md` | **Global Reference** | Authoritative catalog of live backend routes + schemas. |
| `INBOX_USER_APP.md` | Customer Mobile App | Discovery, cart, checkout intent, pickup reservations, cashback, tracking. |
| `INBOX_STOREFRONT.md` | Web Storefront | SEO, theme assets, quick-view, search, cart AJAX, checkout, Paystack redirect. |
| `INBOX_VENDOR.md` | Vendor Web & App | Catalog, pickup inspection, handover OTP, settlements. |
| `INBOX_DELIVERY.md` | Delivery Rider App | Dispatch, routing, pickup verification, proof-of-delivery OTP. |
| `INBOX_ADMIN.md` | Admin Control Tower | Geography, `DeliveryLane` controls, dispatch batches, approvals, refunds. |

---

## 4. The RFC / Request Lifecycle

When Frontend AI needs an endpoint, field, or clarification:

```
[1. POST TICKET] ──> [2. BACKEND AUDIT] ──> [3. BACKEND IMPLEMENTATION] ──> [4. FULFILLMENT & REGISTRY] ──> [5. FRONTEND BIND] ──> [6. REVIEW]
```

### Step 1: Frontend AI Posts a Ticket
Append to the surface inbox (e.g., `INBOX_USER_APP.md`):
```markdown
### [REQ-YYYYMMDD-###] <Surface>: <Feature Title>
- **Status**: `PENDING_BACKEND_REVIEW`
- **Urgency**: `LOW` | `MEDIUM` | `HIGH` | `BLOCKER`
- **Context / User Story**: What the user accomplishes on this client.
- **Proposed Endpoint**: `METHOD /api/v1/...`
- **Required Request Payload**:
  ```json
  { ... }
  ```
- **Desired Response Fields**: Keys needed for the UI.
```

### Step 2: Backend AI Audits
Validates against `VMARKET_BACKEND_SPEC.md` + security invariants. Rejects duplicate engines / client-side state.

### Step 3: Backend AI Implements & Verifies
Controller + service + repository + route + FormRequest in `backend/vmarket-web/`. Runs `php -l` + regression tests. Confirms live HTTP response.

### Step 4: Fulfillment & Registry
Backend AI marks ticket `FULFILLED` with exact schema, appends contract to `API_CONTRACT_REGISTRY.md`, logs in `AI_CHANGELOG.md`, commits only its files.

### Step 5: Frontend AI Binds UI
Reads `FULFILLED` contract and binds UI with 100% confidence. No guessing.

### Step 6: Reviewer AI Gates
Reviews backend + frontend diff at exact SHA. `APPROVED` or `CHANGES_REQUIRED`. No release without `APPROVED`.

---

## 5. Dispute Resolution & Authority Hierarchy

1. Backend AI is final decider of marketplace truth, financial constraints, data models.
2. Canonical precedence: `VMARKET_BACKEND_SPEC.md` > all other platform specs; directional LGA routing > legacy shipping; Pay-After-Inspection Two-Code > direct pre-paid pickup.
3. Pessimistic concurrency: risky inventory/payout requests are rejected or forced into `lockForUpdate()`.
4. Backend ↔ Frontend deadlock: only the human resolves, via decision record. Reviewer never edits code to break the tie.

# Victorious MARKET — 3-AI Communication Protocol (Human → Reviewer → Workers → Reviewer pushes)

> **MANDATORY GOVERNANCE FOR ALL WORK. Exactly 3 independent AIs. No other roles exist.**
> 1. **REVIEWER AI** — sole coordinator, gatekeeper, and push authority. The ONLY AI the human talks to.
> 2. **BACKEND AI** — Laravel PHP logic only. Works ONLY from Reviewer work orders. Never pushes to `main`.
> 3. **FRONTEND AI** — all UI (Flutter + Blade + theme assets). Works ONLY from Reviewer work orders. Never pushes to `main`.
> Every feature flows `Human → REVIEWER AI → BACKEND AI → REVIEWER AI → FRONTEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes`. Skipping a stage is forbidden. Backend and Frontend never communicate directly.

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
   - Backend AI: `backend/vmarket-web/app/**`, `routes/**`, `config/**`, `database/**` ONLY. Never `User app/`, `Vendor app/`, `Delivery Man App/`, `resources/views/**`, `public/assets/**`. Pushes only its own `backend/` branches. NEVER merges, NEVER pushes to `main`.
   - Frontend AI: `User app/**`, `Vendor app/**`, `Delivery Man App/**`, `backend/vmarket-web/resources/views/**`, `backend/vmarket-web/public/assets/**` ONLY. Never backend PHP logic. Pushes only its own `frontend/` branches. NEVER merges, NEVER pushes to `main`.
   - Reviewer AI: `.ai/reviews/**`, `.ai/tickets/**`, `.ai/decisions/**` (drafts), `.ai/releases/**` (drafts), `.ai/incidents/**` (drafts). Never implementation code or tests. ONLY Reviewer merges to `main` and pushes — after `APPROVED` + gate PASS, via `scripts/release/merge-release` only.
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

## 2. The Reviewer-Led Pipeline

```
Human ──requirement──> [REVIEWER: ticket + exact-prompt work order] ──dispatch──> [BACKEND AI: backend/VM-XXX] ──BACKEND_DONE──>
[REVIEWER: verify handoff] ──dispatch──> [FRONTEND AI: frontend/VM-XXX] ──FRONTEND_DONE──>
[REVIEWER: review @ exact SHA ──APPROVED──> gate PASS ──> REVIEWER merges + pushes via merge-release] ──> release
```

- Branch scheme: `backend/VM-<FEATURE>-NNN` (Backend AI, pushes only here), `frontend/VM-<FEATURE>-NNN` (Frontend AI, pushes only here), `reviewer/VM-<FEATURE>-NNN` (review metadata, `.ai/` files only). ONLY Reviewer touches `main`, only via the release script.
- Dispatch: Reviewer writes a copy-paste-ready exact-prompt work order into each ticket (`.ai/templates/work-order-template.md`) — goal, branch, allowed files, forbidden paths, acceptance criteria + evidence, tests, DONE definition. Workers take orders ONLY from Reviewer work orders.
- Ticket flow: `BACKLOG → READY → BACKEND_DOING → BACKEND_DONE → FRONTEND_DOING → FRONTEND_DONE → UNDER_REVIEW → APPROVED | CHANGES_REQUIRED`. A `CHANGES_REQUIRED` verdict returns to the owning stage with a new exact-prompt fix order; any new commit invalidates prior approvals/results.
- Reviewer verdict is exactly `APPROVED` or `CHANGES_REQUIRED`, bound to the exact commit SHA.
- Push rule: no human approval sits in the release path. Reviewer `APPROVED` + `verify-release-gate` PASS = push authority, executed by Reviewer via `merge-release` (which refuses on gate failure).
- Escalation: 3 review cycles, same finding twice, 2 failed integrations at same stage, or >5 days in state → `Blocked: yes (ESCALATED)` + DECISION_REQUEST → human decides. Workers report blockers to Reviewer, never to the human, never sideways.

---

## 2b. Transport: git is the mailbox (no copy-paste)

Sessions cannot message each other, so all bulk content moves through the shared remote. The human sends only one-line triggers; everything else travels by fetch:

1. **Reviewer dispatches:** writes the work order in the ticket file, commits, and pushes its metadata branch (`reviewer_ai/workspace`). Rule: no work order exists until it is pushed.
2. **Worker starts:** on the human's one-line trigger, fetches Reviewer's branch FIRST and reads the work order from the file. Never works from a pasted copy.
3. **Worker delivers:** pushes its feature branch (`backend/VM-*` / `frontend/VM-*`) with ticket notes included in the commits. DONE = branch name + commit SHA, nothing else.
4. **Reviewer collects:** on the human's one-line trigger, fetches the feature branch and reads ticket notes, logs, and diff straight out of it (`git show <branch>:<path>`). Never asks for pasted logs.
5. **Reviewer releases:** verdict in ticket + review file, gate, merge via `merge-release`, push `main`/`v1`, verify the push landed.

Standing convention: push after every write (work order, verdict, DONE notes). Fetch before every read. Pasted content is never authoritative — the branch is.

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

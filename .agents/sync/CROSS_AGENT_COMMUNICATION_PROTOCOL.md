# Victorious MARKET — Cross-Agent Multi-Platform Communication Protocol

> **MANDATORY GOVERNANCE PROTOCOL FOR ALL AI AGENTS WORKING ON THE VMARKET MONOREPO.**  
> This protocol coordinates concurrent, asynchronous AI agents across all 6 platform client actors:
> 1. **User App AI** (Flutter Customer Mobile App)
> 2. **Storefront AI** (Blade / JavaScript Storefront Web UI)
> 3. **Vendor AI** (Vendor Web Panel & Vendor Flutter App)
> 4. **Delivery App AI** (Delivery Rider Flutter App / GetX)
> 5. **Admin Web AI** (Super Admin Command Center)
> 6. **Backend AI** (**Central Operating System & Single Source of Truth / SSOT**)

---

## 1. Prime Invariants

1. **Backend is the Sole Authority (SSOT)**:
   - The Laravel Backend is the **only** entity authorized to calculate money, taxes, delivery fees, commissions, inventory deductions, discount validations, cryptographic OTPs, and state machine transitions.
   - Frontend AIs are **strictly prohibited** from implementing client-side calculation engines or inventing arbitrary order states.

2. **Zero Hallucinated APIs**:
   - A Frontend AI cannot invent or guess API endpoints, query parameters, or JSON keys.
   - Every endpoint consumed by a frontend client must either be already registered in `.agents/sync/API_CONTRACT_REGISTRY.md` or requested via a formal ticket in the actor's dedicated inbox.

3. **No Direct Frontend-to-Frontend Cross-Wiring**:
   - The Customer App does not talk to the Vendor App or Delivery App directly.
   - All state, events, and handshakes flow exclusively through the central backend API.

4. **Component Isolation**:
   - Frontend AIs must never commit changes to the backend (`backend/vmarket-web/`).
   - The Backend AI must never modify or commit frontend UI/presentation code (`User app/`, `Vendor app/`, `Delivery Man App/`, or storefront theme Blade views) unless explicitly requested.

---

## 2. The Hub-and-Spoke Architecture

```
                        ┌─────────────────────────────────┐
                        │      BACKEND AI (SSOT HUB)      │
                        │    (Central Protocol Authority) │
                        └────────────────┬────────────────┘
                                         │
        ┌───────────────┬────────────────┼───────────────┬───────────────┐
        ▼               ▼                ▼               ▼               ▼
┌───────────────┐┌──────────────┐┌──────────────┐┌──────────────┐┌──────────────┐
│  USER APP AI  ││STOREFRONT AI ││  VENDOR AI   ││ DELIVERY AI  ││   ADMIN AI   │
│ (Flutter/Prov)││(Blade/Theme) ││(Web & Mobile)││(Flutter/GetX)││(Blade Admin) │
└───────┬───────┘└──────┬───────┘└──────┬───────┘└──────┬───────┘└──────┬───────┘
        │               │                │               │               │
        ▼               ▼                ▼               ▼               ▼
  INBOX_USER_APP.md  INBOX_STOREFRONT.md INBOX_VENDOR.md INBOX_DELIVERY.md INBOX_ADMIN.md
```

---

## 3. Dedicated Communication Channels

All communication channels reside in `.agents/sync/`:

| Channel File | Target Platform Actor | Primary Focus |
| :--- | :--- | :--- |
| [`API_CONTRACT_REGISTRY.md`](file:///c:/Users/SOOQ%20ELASER/Downloads/vmarket/.agents/sync/API_CONTRACT_REGISTRY.md) | **Global Reference** | Authoritative catalog of all live, verified backend routes, request schemas, and response contracts. |
| [`INBOX_USER_APP.md`](file:///c:/Users/SOOQ%20ELASER/Downloads/vmarket/.agents/sync/INBOX_USER_APP.md) | Customer Mobile App | Location discovery, cart, checkout intent, in-shop pickup reservations, cashback redemption, order tracking. |
| [`INBOX_STOREFRONT.md`](file:///c:/Users/SOOQ%20ELASER/Downloads/vmarket/.agents/sync/INBOX_STOREFRONT.md) | Web Storefront | SEO metadata, theme assets, quick-view, live search, cart AJAX, checkout shipping details, Paystack redirect. |
| [`INBOX_VENDOR.md`](file:///c:/Users/SOOQ%20ELASER/Downloads/vmarket/.agents/sync/INBOX_VENDOR.md) | Vendor Web & App | Product catalog, pickup inspection acceptance/rejection, counter handover OTP verification, financial settlements. |
| [`INBOX_DELIVERY.md`](file:///c:/Users/SOOQ%20ELASER/Downloads/vmarket/.agents/sync/INBOX_DELIVERY.md) | Delivery Rider App | Dispatch assignments, waypoint routing, merchant pickup verification, proof of delivery OTP handshake. |
| [`INBOX_ADMIN.md`](file:///c:/Users/SOOQ%20ELASER/Downloads/vmarket/.agents/sync/INBOX_ADMIN.md) | Admin Control Tower | Geography management, directional `DeliveryLane` controls, dispatch batches, merchant approvals, manual refunds. |

---

## 4. The 4-Step RFC / Request Lifecycle

When a Frontend AI requires a new API endpoint, an additional data field, or clarification on a workflow, it must follow this exact lifecycle:

```
[1. POST TICKET] ──> [2. BACKEND AUDIT] ──> [3. BACKEND IMPLEMENTATION] ──> [4. FULFILLMENT & REGISTRY]
```

### Step 1: Frontend AI Posts a Ticket
The Frontend AI appends a structured ticket to the bottom of its actor inbox (e.g., `INBOX_USER_APP.md`):
```markdown
### [REQ-YYYYMMDD-###] <Actor>: <Feature Title>
- **Status**: `PENDING_BACKEND_REVIEW`
- **Urgency**: `LOW` | `MEDIUM` | `HIGH` | `BLOCKER`
- **Context / User Story**: Explain what the user is trying to accomplish on this client.
- **Proposed Endpoint**: `METHOD /api/v1/...`
- **Required Request Payload**:
  ```json
  { ... }
  ```
- **Desired Response Fields**: List specific keys needed for the UI.
```

### Step 2: Backend AI Audits & Formulates
The Backend AI reads the inbox:
1. Validates the request against `.agents/rules/VMARKET_BACKEND_SPEC.md` and the 9 Security Invariants in `AGENTS.md`.
2. Confirms that no duplicate calculation engine or unauthorized client-side state is being introduced.
3. Formulates the response contract with exact mathematical and type boundaries.

### Step 3: Backend AI Implements & Verifies
1. Implements controller, application service, Eloquent repository, routing, and FormRequest validation in `backend/vmarket-web/`.
2. Runs syntax validation (`php -l`) and automated regression tests.
3. Updates `server.php` / routes if necessary and confirms live HTTP response (`200 OK` / `201 Created`).

### Step 4: Fulfillment & Contract Registry Update
The Backend AI:
1. Updates the ticket status to `FULFILLED` in the actor's inbox with the exact request/response schema.
2. Appends the authoritative contract to [`API_CONTRACT_REGISTRY.md`](file:///c:/Users/SOOQ%20ELASER/Downloads/vmarket/.agents/sync/API_CONTRACT_REGISTRY.md).
3. Logs the backend change in `AI_CHANGELOG.md` and commits.
4. The Frontend AI reads the fulfilled contract and binds the client UI with 100% confidence.

---

## 5. Dispute Resolution & Authority Hierarchy

If a conflict arises between what a Frontend AI believes it needs and what the Backend allows:
1. **Rule of Architectural Authority**: The Backend AI is the final decider of marketplace truth, financial constraints, and data models.
2. **Canonical Specs Precedence**:
   - `VMARKET_BACKEND_SPEC.md` > All other platform specs.
   - Directional LGA routing (`Origin LGA → Destination LGA`) takes precedence over legacy flat shipping or city assumptions.
   - Pay-After-Inspection Two-Code protocol takes precedence over direct pre-paid pickup orders.
3. **Pessimistic Concurrency**: Any request that risks inventory overselling or double-payouts will be rejected or forced into pessimistic database locks (`lockForUpdate()`).

# Sync Channel: Web Storefront ↔ Backend

> **Actor Focus**: Public Web Storefront (`resources/themes/theme_vmarket/` & `public/themes/theme_vmarket/`)  
> **Client Framework**: Laravel Blade / Vanilla JS (`vmarket.js`, `custom.js`) / Bootstrap 5.3  
> **Protocol Rules**: Strictly follow [`.agents/sync/CROSS_AGENT_COMMUNICATION_PROTOCOL.md`](file:///c:/Users/SOOQ%20ELASER/Downloads/vmarket/.agents/sync/CROSS_AGENT_COMMUNICATION_PROTOCOL.md) and [`.agents/sync/API_CONTRACT_REGISTRY.md`](file:///c:/Users/SOOQ%20ELASER/Downloads/vmarket/.agents/sync/API_CONTRACT_REGISTRY.md).

---

## 1. Client Invariants (Mandatory for Storefront AI)
1. **Asset Routing**: Always link theme assets via `{{ theme_asset('assets/...') }}`. The backend router automatically streams static files with appropriate MIME types.
2. **Zero Price/Discount Computation in Blade**: Subtotals, product prices, discounts, and taxes must be rendered directly from backend variables (`$product->unit_price`, `$cart['price']`). Never implement client-side tax or fee estimation in JavaScript.
3. **Location Modal**: When the user switches locations via the storefront modal (`#locationModal`), send the selected canonical `lga_id` to `POST /customer/choose-shipping-address` so the backend session retains the active LGA.
4. **Checkout Redirection**: Web checkout payment forms submit to `POST /customer/web-payment-request`, which delegates to `DeliveryCheckoutIntentService` and redirects directly to Paystack.

---

## 2. Active Communication & RFC Tickets

### [TICKET-STOREFRONT-001] Built-in Server Static Asset 404 Resolution
- **Status**: `FULFILLED`
- **Request**: Storefront CSS/JS/images returned 404 when testing under `php -S 127.0.0.1:8000 server.php` because document root was the project directory.
- **Backend Fulfillment**:
  - Patched `backend/vmarket-web/server.php` to stream public theme assets directly with accurate MIME types and cache headers.
  - Verified 200 OK on `vmarket.css`, `bootstrap.min.css`, `bootstrap-icons.min.css`, `vmarket.js`, and `vm_icon.jpg`.

### [TICKET-STOREFRONT-002] Live Search Debounce & Progressive Enhancement
- **Status**: `FULFILLED`
- **Request**: Storefront search input needs progressive live suggestions without breaking standard GET form submission.
- **Backend Fulfillment**:
  - Live query endpoint: `GET /searched-products?name={query}`.
  - Progressive fallback: Standard GET submission to `GET /products?name={query}` remains primary.

---

## 3. Template for New Request Tickets (Copy & Paste to Append Below)
```markdown
### [REQ-STOREFRONT-YYYYMMDD-###] <Feature / Endpoint Title>
- **Status**: `PENDING_BACKEND_REVIEW`
- **Urgency**: `LOW` | `MEDIUM` | `HIGH` | `BLOCKER`
- **Context**: <Explain the web storefront UI flow, modal, or template requirement>
- **Proposed Web Route / API**: `METHOD /...`
- **Required View Data / JSON Payload**:
  ```json
  { ... }
  ```
```

---

## 4. Open RFC Tickets from Storefront AI (2026-09-24)

### [REQ-STOREFRONT-20260924-001] `cashback_earned` in web order-placed payload
- **Status**: `PENDING_BACKEND_REVIEW`
- **Urgency**: `LOW`
- **Context**: Customer app shows a "You earned ₦X Victorious Cashback" badge post-payment; storefront `checkout/complete` + order confirmation have no earn badge. Requesting `cashback_earned {amount, percent}` (nullable) in the web order-placed data so both clients celebrate identically. Backend owns eligibility/math; generic display only, nothing personalized in indexable HTML.
- **Proposed Web Route / API**: extend existing web order-placed data — backend decides shape.
- **Required View Data / JSON Payload**:
  ```json
  { "cashback_earned": { "amount": "1000.00", "percent": 5 } }
  ```


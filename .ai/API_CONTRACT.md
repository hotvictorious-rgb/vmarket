# Victorious MARKET — Authoritative API Contract Registry

> **OWNED BY AI 1 (BACKEND AI) — REVIEWED BY AI 5, AI 6, AI 7**  
> Critical contract additions and breaking modifications require explicit human sign-off.

---

## 1. Contract-First Governance & Protocol

1. **Ordering Mandate (§7.3)**:
   - For any ticket flagged with `Contract impact: yes`, the lifecycle order is strictly enforced:
     1. AI 1 drafts the API contract change in OpenAPI format and updates this registry.
     2. Domain reviewers (AI 5, AI 6, AI 7) and AI 8 verify the contract against the business requirements.
     3. Only after the contract specification is reviewed and approved may frontend AIs (AI 2, 3, 4) begin implementation.
   - Frontend AIs must never build against guessed or unverified API behavior.
2. **Backward-Compatibility & Versioning**:
   - Fields added to responses must be strictly additive and backward-compatible.
   - Modifying existing field types, removing fields, or changing URL paths is classified as a breaking change.
   - Breaking changes require a new versioned prefix (e.g. `/api/v2/`) and a minimum 60-day deprecation bridge.
   - Contract test suite (`tests/contract/`) runs against past client snapshot payloads on every release candidate.

---

## 2. Standard Response Envelope Schemas

### A. Success Response Envelope
```json
{
  "status": true,
  "message": "Operation completed successfully.",
  "data": { ... }
}
```

### B. Paginated Collection Response Envelope
```json
{
  "total_size": 150,
  "limit": 25,
  "offset": 1,
  "data": [ ... ]
}
```

### C. Standard Error Response Envelope
```json
{
  "status": false,
  "errors": [
    {
      "code": "ERROR_MACHINE_NAME",
      "message": "Human-readable explanation for fallback display."
    }
  ]
}
```

---

## 3. Core Endpoint Catalog

### A. Core Configuration & Canonical Geography
- `GET /api/v1/config` — Global system configurations, currency, maintenance status, active modules.
- `GET /api/v1/shipping-method/countries` — List supported shipping countries (Nigeria).
- `GET /api/v1/shipping-method/states?country_id={id}` — List supported states.
- `GET /api/v1/shipping-method/lgas?state_id={id}` — List supported LGAs within a state.
- `POST /api/v1/shipping-method/calculate-lane-fee` — Calculate exact delivery fee based on Origin LGA and Destination LGA.

### B. Customer Authentication & Profile
- `POST /api/v1/auth/registration` — Register new customer account.
- `POST /api/v1/auth/login` — Authenticate customer via phone/email and password.
- `POST /api/v1/auth/check-phone` — Request 6-digit cryptographic verification OTP.
- `POST /api/v1/auth/verify-phone` — Verify 6-digit OTP with 15-minute expiration bound.
- `GET /api/v1/customer/info` — Fetch scoped authenticated customer profile.
- `PUT /api/v1/customer/update-profile` — Update scoped profile data.

### C. Catalog & Search
- `GET /api/v1/products/latest` — Paginated list of active marketplace products.
- `GET /api/v1/products/details/{slug}` — Product details, stock levels, vendor info, reviews.
- `GET /api/v1/categories` — Marketplace category taxonomy.
- `GET /api/v1/brands` — Active marketplace brands.

### D. Cart & Checkout Pipeline
- `GET /api/v1/cart` — Scoped customer shopping cart.
- `POST /api/v1/cart/add` — Add product variant to cart with atomic stock validation.
- `PUT /api/v1/cart/update` — Update item quantity in cart.
- `DELETE /api/v1/cart/remove` — Remove item from cart.
- `POST /api/v1/customer/order/place` — Submit finalized order, lock inventory, and generate payment session.

### E. Vendor (Seller) Operational Endpoints
- `POST /api/v2/seller/auth/login` — Authenticate merchant.
- `GET /api/v2/seller/shop/info` — Scoped merchant shop details and in-shop pickup configurations.
- `GET /api/v2/seller/orders/list` — Scoped merchant orders list.
- `PUT /api/v2/seller/orders/order-detail-status/{id}` — Update order processing state.
- `GET /api/v2/seller/products/list` — Scoped merchant inventory list.

### F. Delivery Logistics Endpoints
- `POST /api/v1/delivery-man/auth/login` — Authenticate delivery personnel.
- `GET /api/v1/delivery-man/orders/assigned` — Scoped assigned orders for rider pickup and delivery.
- `PUT /api/v1/delivery-man/orders/update-order-status` — Update order status to out_for_delivery or delivered with OTP proof.
- `GET /api/v1/delivery-man/cash-in-hand` — Scoped rider collected COD cash balance.

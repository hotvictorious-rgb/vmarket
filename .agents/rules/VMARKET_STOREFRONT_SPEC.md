# VMARKET PUBLIC STOREFRONT — PRODUCTION ALIGNMENT SPECIFICATION

## 0. Core Principle: Discovery Layer, Not a Second Commerce Engine

The VMarket Public Storefront is a **separate production-grade SEO/discovery web layer** whose job is very specific:

> Public, extremely fast, SEO-first commerce website → consumes verified VMarket backend data → sends customers into the Customer App/checkout when appropriate.

**It is NOT a second marketplace backend.**

```
                         VMARKET BACKEND
                      AUTHORITATIVE SOURCE
                              │
             ┌────────────────┼────────────────┐
             │                │                │
             ↓                ↓                ↓
       CUSTOMER APP       ADMIN / WEB      PUBLIC STOREFRONT
             │                                 │
             │                              SEO
             │                              Search
             │                              Product pages
             │                              Categories
             │                              Merchant pages
             │                              Content
             │                              Fast browsing
             │
             └────────────── Checkout ─────────┘
```

---

## 1. The Non-Negotiable Architecture Invariant

> **The public storefront MUST NOT become a second source of truth.**

**WRONG:**
```
Storefront DB → Product price = ₦20,000
Backend DB    → Product price = ₦22,000
```

**CORRECT:**
```
Backend ──► Product API ──► Storefront ──► renders ₦22,000
```

This applies without exception to:
- price
- stock
- product status
- merchant status
- pickup availability
- delivery availability
- reviews
- categories
- brands
- variants
- availability

The backend is always the authority.

---

## 2. Six Responsibilities of the Public Storefront

### 2.1 Discovery
Captures organic search traffic for queries like:
- `mattress in Uyo`
- `power bank in Uyo`
- `smartwatch Nigeria`
- `speakers in Akwa Ibom`
- `Victorious MARKET mattresses`

### 2.2 Product Presentation
Every public product page exposes authoritative data:
- product name, description, images, SKU
- price, availability
- merchant/shop (verified status only)
- brand, variants
- reviews
- pickup availability notice
- delivery availability notice (non-personalized)

**Never invented. Always backend-sourced.**

### 2.3 SEO
Generates crawlable, canonical, server-rendered URLs:
```
/
/products
/product/{slug}
/category/{slug}
/brand/{slug}
/shop/{slug}
/state/{slug}
/lga/{slug}
/blog/{slug}
```
Avoids generating millions of useless geography/product combinations.

### 2.4 Fast Browsing (Core Web Vitals Acceptance Criteria)
| Metric | Target |
|---|---|
| HTML Response | Fast TTFB |
| LCP (Largest Contentful Paint) | ≤ 2.5s |
| INP (Interaction to Next Paint) | ≤ 200ms |
| CLS (Cumulative Layout Shift) | ≤ 0.1 |
| Mobile Experience | Excellent |
| JavaScript Payload | Minimal |
| Images | Optimized + Lazy loaded below fold |
| Render-blocking Resources | None |
| Caching | Aggressive where safe |
| CDN | Required |

> **Caveat**: These are production engineering targets, not a guarantee of Google ranking position. No tool can guarantee a perfect score or first-place ranking. Google Core Web Vitals are one signal among many.

### 2.5 Backend-Verified Commerce Display
The storefront renders `₦25,000` because the backend returned `₦25,000`. It does not maintain its own product database.

### 2.6 Conversion into the Authenticated Commerce System
```
Storefront → Product → [Buy Now] → Customer App / Authenticated Checkout → Backend → Paystack
```

---

## 3. SEO Product Structured Data Rules

### Google Product/Offer JSON-LD (Required on Every Product Page)
```json
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "Naked Foam 54x10",
  "image": ["..."],
  "sku": "...",
  "brand": { "@type": "Brand", "name": "Naked" },
  "offers": {
    "@type": "Offer",
    "price": "45000",
    "priceCurrency": "NGN",
    "availability": "https://schema.org/InStock",
    "itemCondition": "https://schema.org/NewCondition"
  }
}
```

- Structured data **must be present in server-rendered HTML**, not generated after JavaScript runs.
- Structured data must **exactly match** what the customer sees on the page.
- Product structured data values must **originate from the backend** — not manually maintained.

### Critical SEO Separation: Delivery Availability
**Do NOT** put customer-specific delivery availability into indexable product structured data.

```
❌ WRONG:
Product page: "Delivery unavailable to Eket"
(indexed by Google with customer-specific data)

✅ CORRECT:
Product page: "Delivery available — check your location"
After address selection → Backend FulfillmentAvailabilityService → ₦1,500 to Eket
```

Google Merchant Center guidance explicitly states product landing-page content and structured data should **not dynamically change based on customer information such as IP address or browser type**.

---

## 4. Four Distinct Product States (Storefront Must Represent All Correctly)

```
Product exists
      ≠
Product is purchasable
      ≠
Product is deliverable to this destination
      ≠
Product is available for pickup
```

**Example:**
| Attribute | Value |
|---|---|
| Samsung Speaker — Published | YES |
| Stock Available | YES |
| Delivery Enabled | YES |
| Uyo → Uyo | YES |
| Uyo → Eket | NO |
| In-Shop Pickup | YES |

The storefront renders this accurately without inventing any of these states.

---

## 5. Sitemap Architecture

Backend exposes authoritative data. Storefront generates:
```
/sitemap.xml
/sitemap-products.xml
/sitemap-categories.xml
/sitemap-brands.xml
/sitemap-shops.xml
/sitemap-content.xml
```

**Only include pages that are:**
- public and canonical
- indexable and meaningful
- not duplicate, not private
- not temporary, not checkout/session URLs

---

## 6. Google Merchant Center Synchronization

Eventually, VMarket must achieve:
```
                    BACKEND
                       │
              Product / Inventory
                       │
            ┌──────────┴──────────┐
            ↓                     ↓
       STOREFRONT             FEED/API
            │                     │
     Structured Data        Merchant Center
            │                     │
            └──────────┬──────────┘
                       ↓
                    GOOGLE
```

**Values MUST agree. These are forbidden mismatches:**
- `Website: ₦45,000 / Merchant Center: ₦39,000` ❌
- `Website: In stock / Merchant Center: Out of stock` ❌
- `Website: Published / Backend: Suspended` ❌

Google explicitly states that mismatches can lead to product disapprovals.

---

## 7. Customer App vs Public Storefront: Different Jobs

| Dimension | Customer App (`User app`) | Public Storefront |
|---|---|---|
| **Primary User** | Logged-in customer | First-time visitor / Google |
| **Optimized For** | Cart, checkout, orders, account | Google, speed, discovery |
| **Authentication** | Required for commerce | Not required for browsing |
| **JavaScript** | Rich Flutter/App UI | Minimal; SSR-first |
| **Personalization** | Deep (addresses, OTPs, wallet) | None (non-personalized public pages) |
| **Checkout** | Native in app | Handoff → Customer App |
| **Backend Consumption** | Same canonical APIs | Same canonical APIs |

They share the same backend contracts, **not** the same frontend code.

---

## 8. What to Do with the Old Storefront

**Do NOT delete it yet.** Perform a Migration Audit first.

Classify every existing storefront component:

| Component | Action |
|---|---|
| Routes | `KEEP / REWRITE / MIGRATE / DEPRECATE / DELETE` |
| Product rendering | ↑ |
| Category rendering | ↑ |
| SEO/Metadata | ↑ |
| Sitemap | ↑ |
| Images | ↑ |
| Authentication | ↑ |
| Cart/Checkout | ↑ |
| Payment | ↑ |
| Analytics | ↑ |

Old storefront retires **only after** the new public storefront has passed all production verification tests.

---

## 9. Migration & Production Specification Checklist (Pre-Build Gate)

Before any coding begins, the AI must produce a Storefront Migration & Production Specification containing:

1. Exhaustive audit of what the current storefront does.
2. Which backend endpoints already exist and are authoritative.
3. Which old APIs must be deprecated.
4. Every public page VMarket needs (canonical URL map).
5. SEO metadata rules per page type.
6. Product/Offer JSON-LD structured data rules.
7. Sitemap architecture rules.
8. Canonical URL and duplicate-content rules.
9. SSR/server-rendered HTML requirements.
10. Caching strategy (product pages, category pages, search).
11. Image optimization and CDN rules.
12. Core Web Vitals acceptance test matrix.
13. Google Merchant Center synchronization plan.
14. Frontend/backend verification test suite.
15. Authentication boundary between storefront and Customer App.
16. Cart/checkout handoff protocol.
17. 404/410/redirect handling rules.
18. Launch procedure and old storefront retirement procedure.

> **The central architectural invariant of the storefront is: everything displayed originates from backend-verified data.**

---

## 10. Final Target Architecture

```
                         VMARKET BACKEND
                         SOURCE OF TRUTH
                                │
       ┌────────────────────────┼───────────────────────┐
       │                        │                       │
       ↓                        ↓                       ↓
 CUSTOMER APP             PUBLIC STOREFRONT         ADMIN
       │                        │                       │
       │                    SEO / FAST                  │
       │                    DISCOVERY                   │
       │                    PRODUCTS                    │
       │                    CATEGORIES                  │
       │                    SHOPS                       │
       │                        │                       │
       └───────────────┬────────┘                       │
                       ↓                                │
                   CHECKOUT                             │
                       ↓                                │
                    PAYSTACK                            │
                       ↓                                │
                   BACKEND                              │
                       ↓                                │
                 ORDER / STOCK                          │
                       ↓                                │
             DELIVERY / PICKUP                          │
```

Separately:
```
BACKEND
   │
   ├── Merchant Center feed
   ├── Sitemap
   ├── Structured product data
   ├── Search engine discovery
   └── Analytics
```

---

## 11. Directive for Coding AI

> **Do not start coding the public storefront yet.**
>
> First, deep-scan the current v1 branch and produce the Storefront Migration & Production Specification above. Identify exactly what the current storefront does, which backend endpoints are authoritative, which must be deprecated, and what every public page requires. Only after the specification is approved should implementation begin.
>
> The public storefront is the SEO/discovery web layer of the existing VMarket marketplace — not a replacement marketplace. Backend remains the sole authority for all product, pricing, inventory, merchant, fulfillment, and payment data.

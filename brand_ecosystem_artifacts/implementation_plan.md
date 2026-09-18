# Victorious Ecosystem UI/UX Blueprint (Refined Staged Plan)

## 0. Architectural & Governance Safeguards
- **Immutable Security Baseline:** Commit `70649aee` remains frozen and untouched.
- **Active Working Branch:** `feature/ecosystem-ui-branding`.
- **POS Separation:** The external POS repository (`pos/`) remains 100% untouched.
- **Regression Invariant:** The 75/75 automated unit assertion suite must maintain 100% green pass rate with $\Delta = 0.00$.

---

## 1. Ground Truth Brand Asset Analysis
We discovered the two active brand assets currently in the codebase:
1. **Master 3D Brand Logo** (`backend/vmarket-web/public/assets/back-end/img/logo.png`):
   - **Core DNA:** A 3D Royal Purple (`#5e17eb`) to Imperial Gold (`#ffd700`) soaring arrow forming the signature 'V'.
   - **Symbolism:** An integrated purple basket/cart with an ascending gold growth graph and twin victory stars.
   - **Typography:** Royal purple "VICTORIOUS MARKET".
2. **Mobile App Asset** (`User app/assets/images/logo.png`):
   - A 2D horizontal purple pill with a white cart ("VM"), yellow "VICTORIOUS", and white "MARKET", currently duplicated across all 3 mobile apps.

### Ecosystem Brand Cohesion Rule:
The master 3D soaring arrow 'V' and royal coronet/stars from `logo.png` serve as the **authoritative visual DNA** across all 3 apps and the web platform.
- **Universal Palette:** Royal Purple (`#5e17eb`), Imperial Gold (`#ffd700`), Crisp White (`#ffffff`), Pure Black (`#000000`).
- **Universal Slogan:** *"Victorious MARKET — Your Trusted Online Market For Quality Products"*.
- **Product Badges:**
  - **VM:** `ONLINE MARKETPLACE`
  - **VV:** `MERCHANT COMMAND CENTER`
  - **VD:** `DISPATCH & COURIER PARTNER`

---

## 2. Critical Rule & Invariant Corrections (Enforced)

| Rule # | Requirement | Implementation Enforcement |
| :--- | :--- | :--- |
| **1** | **Customer ↔ Vendor/Rider Chat Disabled** | Remove all direct vendor/rider chat buttons from VM PDP, VV, and VD. All customer support routes exclusively through **Victorious MARKET Central Support**. Operational rider phone calls remain strictly controlled at physical arrival. |
| **2** | **Vendor Private Address Privacy** | Vendor residential/private addresses are never exposed on the public storefront. VM only displays an approved pickup point / map pin when an order has explicitly qualified for **In-Store Pickup**. |
| **3** | **Strict OTP Boundary Separation** | `pickup_verification_code` (In-Store Pickup Secret OTP) is verified exclusively by the merchant at physical collection.<br>`verification_code` (Doorstep Secret Handover OTP) is provided strictly at physical delivery handover to the rider. The UI never conflates them. |
| **4** | **No Unstaged Split Checkout Claims** | Remove promotional mentions of "instant split checkout" from the UI onboarding and banners until explicitly implemented and verified in staging. |
| **5** | **No Unverified Customer Wallet** | Do not display an unverified digital wallet balance in VM until backend balance ledger, reconciliation, and authorization are confirmed. |
| **6** | **Internal Ledger Terminology in VV** | Replace "escrow clearance" with accurate marketplace terms:<br>• `Pending Settlement`<br>• `Available Balance`<br>• `Completed Payouts` |
| **7** | **Preserve Backend Logistics State Machine** | VD strictly transitions:<br>`ready → out_for_delivery` (Merchant Pickup)<br>`out_for_delivery → delivered` (Doorstep Handover).<br>No phantom states like `In Custody`. |
| **8** | **Controlled Platform Notifications Only** | Merchant "Notify Rider / Customer" triggers platform notifications only; no unrestricted private chat channels. |
| **9** | **Binary Inventory Display in VM** | VM storefront strictly displays binary `In Stock` or `Out of Stock`. POS warehouse quantities remain completely hidden. |
| **10** | **7-Day Listing Freshness in VV** | VV displays three freshness states:<br>• `Confirmed`<br>• `Confirmation Due`<br>• `Expired / Unlisted`<br>Expiration unlists the item from the marketplace without deleting it. |

---

## 3. Staged Implementation Plan

### Stage 1: Design Foundation & Visual Tokens
- Define unified Royal Purple (`#5e17eb`) and Imperial Gold (`#ffd700`) color tokens, typography scales, elevated card styles, and high-contrast OTP containers across all 3 mobile apps and the web theme.

### Stage 2: Icons & Favicon Evolution
- **Web Favicon:** Generate multi-resolution `favicon.ico` and scalable `favicon.svg` using the authentic 3D Gold 'V' monogram crest, replacing the current 0-byte file in `backend/vmarket-web/public/favicon.ico`.
- **App Icons:** Evolve the three product icons from the master brand DNA on a Royal Purple squircle:
  - **VM:** Master Gold Soaring Arrow 'V' + Shopping Bag motif.
  - **VV:** Master Gold Soaring Arrow 'V' + Neoclassical Storefront motif.
  - **VD:** Master Gold Soaring Arrow 'V' + Courier Parcel & Speed Wings motif.

### Stage 3: Cinematic Splash Screens
- Implement the 1.8-second restrained scale & ambient glow sequence across all 3 apps:
  - **VM:** Master Logo $\rightarrow$ "Victorious MARKET" $\rightarrow$ Universal Slogan $\rightarrow$ `ONLINE MARKETPLACE` badge.
  - **VV:** Merchant Logo $\rightarrow$ "Victorious MARKET" $\rightarrow$ `MERCHANT COMMAND CENTER` badge.
  - **VD:** Logistics Logo $\rightarrow$ "Victorious MARKET" $\rightarrow$ `DISPATCH & COURIER PARTNER` badge.

### Stage 4: VM (Victorious MARKET) UX Polish
- Clean discovery feed, search-first header, binary stock tags (`In Stock` / `Out of Stock`), central support integration, and distinct golden **In-Store Pickup Secret OTP** vs **Doorstep Secret Handover OTP** display cards.

### Stage 5: VV (Victorious Vendor) Command Center UX
- "What do I need to do today?" operational pulse dashboard, orders awaiting action, in-shop pickup OTP verification modal, 7-day listing freshness lifecycle management (`Confirmed`, `Confirmation Due`, `Expired / Unlisted`), and internal settlement tracking (`Pending Settlement`, `Available Balance`, `Completed Payouts`).

### Stage 6: VD (Victorious Delivery) Task-Oriented UX
- Persistent `ACTIVE DELIVERY` card, clear two-stage task execution (`ready → out_for_delivery` and `out_for_delivery → delivered`), high-contrast 6-digit doorstep OTP pad, and COD cash collection ledger.

### Stage 7: Automated Security Regression
- Execute the full 75/75 unit assertion suite (`PaymentFulfillmentBoundarySecurityTest.php`, `MarketplaceListingFreshnessTest.php`, `ProductFeedExportIsolationTest.php`) to confirm $\Delta = 0.00$.

### Stage 8: Live Staging Journey Verification
- Validate the 3 core flows in staging:
  1. Paystack $\rightarrow$ Delivery $\rightarrow$ Settlement.
  2. Self-Pickup $\rightarrow$ Canonical `pickup_verification_code`.
  3. Rider Delivery $\rightarrow$ Merchant Handshake $\rightarrow$ Customer Doorstep Handshake.

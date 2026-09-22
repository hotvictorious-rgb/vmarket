# 📱 Victorious MARKET — Customer Mobile App (`User app`)

Official Flutter native mobile application for Victorious MARKET online shoppers (Android & iOS).

---

## 🏛️ Prime Architectural Directive
The Customer App is an **untrusted presentation client**. It must strictly represent backend decisions and never invent or calculate marketplace business rules:
- **No Client-Side Delivery Calculations:** Delivery availability and delivery fees are strictly determined by the backend's directional lane engine (`FulfillmentAvailabilityService`).
- **No Client-Side Price / Tax Calculations:** Unit prices, taxes, and grand totals are authoritative snapshots returned by `DeliveryCheckoutIntentService`.
- **No Speculative Order Creation:** An order is created only after verified payment settlement.
- **Single Source of Truth Specification:** All features and screens must align 100% with the 77-section specification in [`.agents/rules/VMARKET_CUSTOMER_APP_SPEC.md`](file:///c:/Users/SOOQ%20ELASER/Downloads/vmarket/.agents/rules/VMARKET_CUSTOMER_APP_SPEC.md).

---

## 🛠️ Technology Stack & State Management
- **Framework:** Flutter 3.x (Dart 3.x)
- **Target OS:** Android (SDK 34+) and iOS (15+)
- **State Management:** **Provider** (GetX, BLoC, and Riverpod are strictly forbidden here)
- **Dependency Injection:** **GetIt** (`lib/di_container.dart`)
- **Secure Token Storage:** `StorageService` (`lib/services/storage_service.dart`) backed by `flutter_secure_storage`
- **Network Client:** `DioClient` with centralized error handling and automatic bearer token injection
- **Design System:** Victorious MARKET Purple (`#6A1B9A`) & Gold (`#FFD700`) brand palette

---

## 🛒 Core Supported Journeys

### 1. Doorstep Delivery via Directional Lane
```text
Browse -> Product Details -> Cart -> Shipping Address (Country -> State -> LGA)
-> Fulfillment Availability Check -> Two-Phase Checkout Intent -> Paystack Gateway
-> Server Payment Verification -> Order Settlement -> Rider Dispatch -> Contactless Delivery OTP
```

### 2. In-Shop Inspection & Pickup (Akwa Ibom Trust Model)
```text
Browse -> Product Details -> Cart -> In-Shop Pickup -> 24-hr Stock Reservation (₦0.00 Paid)
-> Visit Merchant Shop -> Physical Item Inspection -> Inspected Accepted -> Pay at Store (Paystack)
-> Backend Verification & Stock Deduction -> 6-Digit Secret Pickup OTP -> Handover & Completion
```

### 3. Victorious Cashback 5% Reward Ledger
- Earns 5% on all settled orders.
- Redeemed as an order reduction at checkout via `use_cashback: true`.
- Zero client points manipulation; points balances verified under backend row lock.

---

## 📂 Feature-First Directory Structure
```
lib/
├── common/             # Shared reusable widgets (buttons, text fields, dialogs)
├── data/               # Network clients, API response mappers, error handlers
├── di_container.dart   # Service locator (GetIt) registrations
├── features/
│   ├── address/        # Canonical Geography (Country -> State -> LGA) & Address book
│   ├── auth/           # Login, registration, 6-digit cryptographic OTP, secure token
│   ├── cart/           # Cart management & quantity updates
│   ├── cashback/       # Victorious Cashback 5% summary & transaction ledger
│   ├── checkout/       # CheckoutIntent, fulfillment availability, pickup reservations
│   ├── order/          # Order history & status tracking
│   ├── order_details/  # Order details snapshot & secret handover OTP
│   ├── product/        # Catalog, search, filters, details
│   ├── profile/        # Customer profile management
│   └── tracking/       # Live order delivery timeline
├── helper/             # Formatters, route helpers, validators
├── localization/       # Multi-language string dictionaries
├── services/           # StorageService (secure storage wrapper)
└── utill/              # AppConstants, theme colors, dimensions, asset images
```

---

## 🔒 Mandatory Governance Rules
Every developer and AI working on this application must strictly adhere to:
1. [`.agents/rules/VMARKET_CUSTOMER_APP_SPEC.md`](file:///c:/Users/SOOQ%20ELASER/Downloads/vmarket/.agents/rules/VMARKET_CUSTOMER_APP_SPEC.md) (77-Section Canonical Production Contract)
2. [`.agents/rules/CUSTOMER_APP_ALIGNMENT.md`](file:///c:/Users/SOOQ%20ELASER/Downloads/vmarket/.agents/rules/CUSTOMER_APP_ALIGNMENT.md) (20 Enforcing Rules)
3. [`.agents/rules/CUSTOMER_APP_ALIGNMENT_PLAN.md`](file:///c:/Users/SOOQ%20ELASER/Downloads/vmarket/.agents/rules/CUSTOMER_APP_ALIGNMENT_PLAN.md) (34-Phase Alignment Plan)
4. [`AI_CHANGELOG.md`](file:///c:/Users/SOOQ%20ELASER/Downloads/vmarket/AI_CHANGELOG.md) (Mandatory change logging before every commit)

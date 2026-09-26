# Victorious MARKET — Authoritative Design & UI/UX Rules

> **CONTROL ZONE FILE — HUMAN OWNER ONLY**  
> AI agents are STRICTLY FORBIDDEN from editing this file. Any proposed modifications must be submitted via a ticket accompanied by an approved Decision Record (`.ai/decisions/DECISION-XXXX.md`).

---

## 1. Brand Identity & Color System

Victorious MARKET uses an authoritative Purple & Gold luxury marketplace palette:

| Token | Name | HEX Code | Usage |
|-------|------|----------|-------|
| `color-primary` | Royal Purple | `#4A154B` | App bars, primary action buttons, header fills, brand accents |
| `color-primary-dark` | Deep Purple | `#2E0A30` | Footers, dark overlays, high-contrast text |
| `color-accent` | Imperial Gold | `#D4AF37` | Badges, discount tags, rating stars, luxury accents |
| `color-accent-light` | Soft Gold | `#F5E8C7` | Highlight chips, banner backgrounds, active tab pills |
| `color-surface` | Pure White | `#FFFFFF` | Card backgrounds, dialogs, main canvas |
| `color-background` | Cool Light Gray | `#F8F9FA` | Screen background canvas |
| `color-text-primary`| Charcoal Dark | `#1E1E24` | Body text, headings, list item titles |
| `color-text-secondary`| Muted Gray | `#6C757D` | Captions, secondary timestamps, placeholder text |
| `color-success` | Emerald Green | `#28A745` | Delivered status, wallet credit, successful transaction |
| `color-warning` | Amber Orange | `#FFC107` | Pending status, payment confirmation alerts |
| `color-danger` | Crimson Red | `#DC3545` | Canceled status, validation errors, destructive actions |

---

## 2. Typography & Spatial Grid

1. **Spatial Scale**:
   - Baseline spacing unit is $8\text{px}$.
   - Standard padding and margins: $4\text{px}$ (xs), $8\text{px}$ (sm), $16\text{px}$ (md), $24\text{px}$ (lg), $32\text{px}$ (xl).
2. **Typography Hierarchy**:
   - Primary Font Family: System Sans (Roboto / Inter for Web, SF Pro on iOS, Roboto on Android).
   - Display/Hero: $24\text{px}$ to $28\text{px}$, Bold.
   - Section Titles: $18\text{px}$ to $20\text{px}$, SemiBold.
   - Body Text: $14\text{px}$ to $15\text{px}$, Regular.
   - Captions & Metadata: $12\text{px}$, Medium.

---

## 3. Accessibility & Usability Standards

1. **Touch Targets**:
   - Every interactive element (buttons, icons, menu items, toggles) MUST provide a minimum touch target size of **$48 \times 48\text{dp}$**.
2. **Contrast Standards (WCAG 2.1 AA)**:
   - Normal text ($< 18\text{pt}$) must maintain a minimum contrast ratio of **4.5:1** against its background.
   - Large text ($\ge 18\text{pt}$) and active UI icons must maintain a minimum contrast ratio of **3.0:1**.
3. **Screen Reader Semantics**:
   - All icon-only buttons (`IconButton`, `InkWell`) must declare an explicit `tooltip` or `Semantics(label: ...)` description.

---

## 4. Mobile & Web Frontend Invariants

1. **Cached Network Images**:
   - All remote image loading in Flutter apps must use `cached_network_image`.
   - Raw `Image.network` is strictly prohibited.
   - Every network image must provide a shimmer/placeholder widget and a graceful error fallback widget.
2. **Multi-Theme Home Header Synchronicity**:
   - Any modification to the Customer App home screen header (app bar, brand logo, wordmark, call-to-order pill, or notifications badge) MUST be implemented identically across all 3 theme screens:
     - `lib/features/home/screens/home_screens.dart` (Default)
     - `lib/features/home/screens/aster_theme_home_screen.dart` (Aster)
     - `lib/features/home/screens/fashion_theme_home_screen.dart` (Fashion)
3. **Screenshot Mandate for Frontend Tickets**:
   - Frontend tickets altering screens or widgets must attach screenshot proofs in the ticket before transitioning to `SELF_CHECKED`:
     - Small phone viewport ($\sim 360\text{px}$ width)
     - Large phone / tablet viewport ($\sim 600\text{px}+$ width)
     - Web desktop viewport (for web interfaces)

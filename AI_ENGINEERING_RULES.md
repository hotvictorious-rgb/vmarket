# 🏛️ Vmarket AI Engineering Rules & Governance

**Authoritative Mandate for all AI Coding Agents working on Victorious MARKET (Vmarket)**

---

## 1. The Core Principle: Vmarket is ONE Unified Platform

Vmarket is **NOT** a collection of independent repositories or isolated apps. It is **ONE unified multi-client software platform** powered by a single authoritative backend:

```
                          ┌──────────────────────────┐
                          │     LARAVEL BACKEND      │
                          │ (Single Source of Truth) │
                          └─────────────┬────────────┘
                                        │
         ┌──────────────┬───────────────┼───────────────┬──────────────┐
         │              │               │               │              │
    ┌────┴────┐   ┌─────┴─────┐   ┌─────┴─────┐   ┌─────┴─────┐   ┌────┴────┐
    │ Customer│   │   Admin   │   │  Vendor   │   │ Customer  │   │ Vendor  │ ... Delivery
    │ Website │   │   Panel   │   │   Panel   │   │Flutter App│   │Flutter  │     App
    └─────────┘   └───────────┘   └───────────┘   └───────────┘   └─────────┘
```

The platform consists of:
1. **Laravel 10 Backend & REST API** (`backend/Admin and web new install V16.1`)
2. **Super Admin Web Portal** (Blade + AJAX)
3. **Vendor Web Dashboard** (Blade + AJAX)
4. **Customer Web Storefront** (Blade Views: Default & Aster Theme)
5. **Customer Mobile Application** (`User app/` - Flutter + Provider)
6. **Vendor Mobile Application** (`Vendor app/` - Flutter + Provider)
7. **Delivery Rider Mobile Application** (`Delivery Man App/` - Flutter + GetX)

Every AI must treat these clients as **views and interfaces of the central backend**, never as separate systems.

---

## 2. Backend is the Single Source of Truth

**All authoritative business logic MUST reside in Laravel.**

Clients (Flutter apps and Web UI) are responsible strictly for:
* UI rendering and animations
* User experience and local navigation
* Device functionality (Camera, Geolocation, Audio Recording)
* Push notification handling (FCM)
* Temporary local presentation state

**Clients MUST NEVER duplicate or invent authoritative business logic**, including:
* ❌ Pricing calculations, taxes, or discounts
* ❌ Commission splits (e.g. 90/10 vendor/platform)
* ❌ Vendor earnings or wallet balance calculations
* ❌ Order lifecycle and state transitions
* ❌ Payment gateway verification or status overrides
* ❌ Delivery fee formulas and rider dispatch rules
* ❌ Identity verification (KYC) match algorithms and approvals
* ❌ Bank account cooldown timers and OTP validation

---

## 3. Mandatory Change Impact Analysis (Hard Rule)

Before making any non-trivial change, every AI **MUST** inspect the repository across all dimensions:
1. **Backend Impact:** Models, Repositories, Controllers, Services, Policies, Middleware, Notifications, Events, Migrations.
2. **API Contract Impact:** Endpoint routes, request payloads, response schemas, authentication headers, backward compatibility.
3. **Database Impact:** Schema changes, table relationships, foreign keys, default values, backward data integrity.
4. **Client Impact:** Which of the 6 clients (Web Storefront, Admin Panel, Vendor Panel, Customer App, Vendor App, Delivery App) consume this logic.

The AI must explicitly classify every subsystem as:
* `[AFFECTED]`
* `[NOT AFFECTED]`
* `[POTENTIALLY AFFECTED]`

---

## 4. Two-Phase Implementation Process

### Phase 1 — Analysis & Plan
1. Search the entire repository for related keywords, models, endpoints, and UI references.
2. Formulate the root cause and impact analysis.
3. Formulate the implementation plan.
4. Obtain approval or confirm scope.

### Phase 2 — Implementation
1. **Backend First:** Implement changes in Laravel when business logic or APIs are involved.
2. **Contract Update:** Update API documentation and data models.
3. **Clients Next:** Update every affected client app/panel systematically.
4. **Verification:** Run static analysis (`flutter analyze`, syntax checks, unit tests).
5. **Documentation & Commit:** Document changes in `AI_CHANGELOG.md` and commit to Git using conventional commit standards.

---

## 5. Feature-First, Not App-First Development

Vmarket must be developed **feature-by-feature across the stack**, not app-by-app in silos.

```
CORRECT WORKFLOW:
Feature Requirement ➔ Laravel Backend ➔ Admin/Vendor Panels ➔ Flutter Apps ➔ Verification ➔ Docs

INCORRECT WORKFLOW:
Finish User App ➔ Finish Vendor App ➔ Finish Delivery App ➔ Finish Admin
```

---

## 6. Root-Cause-First Debugging

When a bug or crash is reported:
1. Trace from Symptom ➔ Client UI ➔ API Payload ➔ Controller/Service ➔ Eloquent Model ➔ Database.
2. Fix the **root cause** in the authoritative layer.
3. **NEVER apply client-side band-aids** that mask backend inconsistencies.

---

## 7. Change Classification Matrix

| Level | Type | Scope | Governance Requirement |
| :--- | :--- | :--- | :--- |
| **Level 1** | Local UI / Cosmetic | Single widget, color, padding, icon | Local testing; component commit. |
| **Level 2** | Client Feature | Single app screen or local navigation | Check API consumption; test affected app. |
| **Level 3** | Shared API Change | Modified endpoint, payload, or response | Mandatory cross-client search & contract update. |
| **Level 4** | Domain / Business Rule | Orders, commissions, KYC, payouts, delivery | Full Impact Analysis across all 6 clients + ADR update. |
| **Level 5** | Architecture / DB | Schema restructure, auth overhaul, security | Comprehensive migration plan, testing, and documentation. |

---

## 8. Git & Changelog Mandate

* **Atomic Commits:** Every AI modification must be committed atomically by component (`feat(user-app)`, `fix(backend)`, etc.).
* **Include Tag:** Include `[AI]` in the commit message.
* **Changelog:** Always record changes in `AI_CHANGELOG.md` before committing so the log is part of the commit.
* **Clean Tree:** Verify `git status` is clean at the conclusion of every turn.

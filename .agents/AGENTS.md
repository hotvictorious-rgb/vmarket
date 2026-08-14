# Victorious MARKET AI Development Rules

Welcome to the Victorious MARKET (Vmarket) ecosystem! This file enforces strict rules and patterns that **ALL AIs** must adhere to when working on this repository. **All AIs must follow all the rules in AGENTS.md strictly; no AI is allowed to bypass them under any circumstances.**

## 0. Prime Directive: Read Governance Documents First
Before taking ANY action, every AI **MUST** read:
1. `AI_ENGINEERING_RULES.md` — The foundational engineering principles of Vmarket as ONE unified platform.
2. `CHANGE_IMPACT_PROTOCOL.md` — The mandatory 6-point pre-change impact analysis checklist.
3. `ARCHITECTURE.md` — The system topology, service layers, and state boundaries.
4. `AI_CHANGELOG.md` — The chronological log of recent AI modifications.

## 1. Golden Rule: Read Before Writing
Before making ANY changes to this codebase, you MUST:
- Analyze the existing structure.
- Understand how your requested change integrates with the existing architecture.
- Do NOT introduce new architectural patterns (e.g., do not install Redux if the app uses Provider, do not use raw SQL if the backend uses Eloquent Repositories).
- You must always read `AI_CHANGELOG.md` in the root directory to understand recent modifications made by other AIs.

## 2. Mandatory Change Logging
Any time you make a functional change, fix a bug, or complete a feature, you **MUST** document it in `AI_CHANGELOG.md` located in the root of the workspace. Always include the exact timestamp in the header: `### [YYYY-MM-DD HH:MM UTC] <Title> [<Scope>]`. This ensures all AIs remain synchronized on the project's state.

## 3. Strict Architectural Patterns

### A. The Laravel Backend (`backend/Admin and web new install V16.1`)
- **Queries:** Avoid N+1 queries at all costs. You MUST use Eager Loading (`->with()`) inside the `app/Repositories` classes. Note that the Repository pattern and eager loading rules apply to newly written or refactored features. Legacy direct queries inside controllers must be preserved to minimize regression risk, unless that specific endpoint is being overhauled.
- **Caching & Invalidation:** The storefront relies heavily on caching. If you add a new configuration or storefront setting, you must cache it using `Cache::remember()` in the `app/Utils/settings.php` file or equivalent utility. Crucially, any settings creation/update logic in controllers or repositories must explicitly invalidate the corresponding cache key using `clearWebConfigCacheKeys()` or `cacheRemoveByType()`.
- **Data Integrity:** All Eloquent Models must explicitly define a `$fillable` or `$guarded` array to prevent Mass Assignment.
- **Cross-App & Multi-Platform Verification:** Whenever any AI wants to make functional modifications to the backend, they MUST first verify and prove feature-by-feature, one-by-one, that the changes do not break operations across:
  1. Admin Web Panel
  2. Customer Web Storefront
  3. Seller Web Panel
  4. Customer Mobile App
  5. Seller Mobile App
  6. Delivery Man Mobile App
  This verification and proof is mandatory before making any change to the backend.

### B. User App & Vendor App (Flutter)
- **State Management:** These apps use **Provider**. Do NOT introduce GetX, BLoC, or Riverpod.
- **Dependency Injection:** All services and providers must be registered using **GetIt** in `lib/di_container.dart`.
- **Security:** API tokens must ONLY be stored using `flutter_secure_storage`. Do not use `shared_preferences` for sensitive keys.
- **Architecture:** Follow the Feature-First directory structure (`lib/features/{feature_name}`).

### C. Delivery Man App (Flutter)
- **State Management:** This specific app uses **GetX** for state and routing. Do NOT use Provider here.
- **Security Notice:** API tokens and credentials must ONLY be stored using `flutter_secure_storage`. Any legacy fallback in `shared_preferences` must be migrated.
- **Performance:** When dealing with maps and geolocation, ensure UI repaints are minimized via GetX reactive variables (`.obs`).

## 4. UI / UX Standards
- The platform uses a specific color scheme (Purple & Gold). Use the predefined theme colors.
- Maintain smooth 60fps performance on mobile apps. Use `cached_network_image` for all network images.
- **Multi-Theme Home Headers:** Any modification to the Customer App home screen header (app bar, brand logo, wordmark, call-to-order pill, or notifications badge) MUST be implemented identically across all 3 theme screens: `lib/features/home/screens/home_screens.dart` (Default), `lib/features/home/screens/aster_theme_home_screen.dart` (Aster), and `lib/features/home/screens/fashion_theme_home_screen.dart` (Fashion) to prevent visual discrepancies when the active theme is toggled from the admin panel.

## 5. Mandatory Git Commit Rule ⚠️
**This is non-negotiable.** Every AI MUST commit all changes to Git upon completing any task, feature, fix, or audit. Leaving changes uncommitted is STRICTLY FORBIDDEN.

### Commit Format
Use descriptive, atomic commits grouped by component. Follow this convention:

```
<type>(<scope>): <short description> [AI]

- Bullet point of what changed
- Another bullet point
```

**Types:** `feat`, `fix`, `security`, `perf`, `refactor`, `chore`
**Scopes:** `user-app`, `vendor-app`, `delivery-man`, `backend`, `ai-governance`

### Commit Procedure
After completing any change:
1. `git add <specific files>` — Stage only the files you changed (do NOT use `git add .` blindly).
2. `git commit -m "<message> [AI]"` — Include `[AI]` tag so human developers know it was AI-authored.
3. Log the changes in `AI_CHANGELOG.md` **before** committing (so the changelog itself is part of the commit).
4. Verify with `git status` that the working tree is clean before ending your session.

### Grouping Strategy
- Group commits by **component** (one commit per app, one for backend).
- Do NOT mix Flutter app changes with Laravel backend changes in a single commit.
- New untracked files (widgets, screens) must be explicitly staged with `git add <path>`.

## 6. Code Commenting Standards
- **AI Prefix:** All comments introduced by an AI must be prefixed with `[AI]` so human developers can easily identify AI-authored notes.
- **Client Context:** When writing or modifying API/controller methods, add comments detailing which client applications (e.g., Customer Web, Vendor App) consume it.
- **Preservation:** Never delete, strip, or replace existing developer comments or docstrings unless the corresponding code is completely removed.

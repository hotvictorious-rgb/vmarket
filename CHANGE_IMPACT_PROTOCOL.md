# 🛡️ Vmarket Change Impact Protocol

**Standard Operating Procedure for Evaluating and Executing Changes across Vmarket**

---

## 1. Objective
To prevent undocumented regressions, broken API contracts, state divergence, or unintended side effects when modifying any part of the Vmarket ecosystem.

---

## 2. The 6-Point Inspection Checklist

Before modifying any file, the AI must evaluate:

1. **Domain Logic:** Does this change business rules (pricing, commission, orders, payouts, KYC)?
   - *If Yes:* Check Laravel backend services and repositories first.
2. **API Contracts:** Does this alter any endpoint URL, query parameter, JSON field name, or data type?
   - *If Yes:* Check all consumers in Web, Customer App, Vendor App, Delivery App.
3. **Database Schema:** Does this modify columns, nullability, or relationships in MySQL?
   - *If Yes:* Write a timestamped migration with default fallbacks; ensure `$fillable` on models.
4. **State Management:** Which state containers are affected (`Provider` in User/Vendor, `GetX` in Delivery)?
5. **Mobile Permissions & Native Bridges:** Are camera, storage, microphone, or GPS permissions involved?
6. **Security & Tokens:** Does this affect authentication or sensitive secrets (`flutter_secure_storage`)?

---

## 3. Mandatory Template: Pre-Change Impact Report

Before executing any non-trivial change, output the following structured report:

```text
=====================================================
VMARKET CHANGE IMPACT ANALYSIS
=====================================================

REQUEST:
[Description of requested change]

ROOT CAUSE / ARCHITECTURAL CONTEXT:
[Current implementation and why the change is needed]

AFFECTED SYSTEMS:
- [e.g., Laravel Backend, Vendor App]

NOT AFFECTED:
- [e.g., Delivery Man App, Storefront Aster Theme]

BACKEND IMPACT:
- Models: [e.g., Seller.php, WithdrawRequest.php]
- Controllers: [e.g., VendorController.php]
- Services: [e.g., NigerianKycService.php]
- Migrations: [e.g., 2026_08_11_..._add_kyc.php]

API CONTRACT IMPACT:
- Endpoints: [e.g., /api/v1/seller/bank-info/update]
- Breaking Changes: [None / Backward-Compatible]

CLIENT IMPACT:
- Customer App: [None]
- Vendor App: [BankEditingScreen.dart]
- Delivery Man App: [None]
- Admin/Vendor Web: [admin-views/vendor/view.blade.php]

BUSINESS RULES / RISKS:
- [e.g., 48-hour bank cooldown rule applies]

TESTING & VERIFICATION PLAN:
1. [e.g., Compile Flutter Vendor App with assembleRelease]
2. [e.g., Verify Laravel endpoint with unit/cURL check]
=====================================================
```

---

## 4. Mandatory Template: Post-Change Implementation Report

Upon completion, output the following structured summary:

```text
=====================================================
VMARKET IMPLEMENTATION REPORT
=====================================================

COMPLETED:
- [Summary of completed deliverables]

FILES MODIFIED:
- [List of modified files with clickable markdown links]

CLIENTS & SERVICES UPDATED:
- [List of updated components]

VERIFICATION RESULTS:
- [Flutter compile output, analyzer checks, build status]

DOCUMENTATION & CHANGELOG:
- [AI_CHANGELOG.md updated and committed]

GIT COMMIT HASH & MESSAGE:
- [e.g., fix(vendor-app): update bank info [AI]]
=====================================================
```

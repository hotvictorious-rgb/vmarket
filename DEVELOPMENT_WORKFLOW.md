# 🚀 Vmarket Development Workflow

**End-to-End Development & Verification Lifecycle**

---

## 1. Feature Implementation Flowchart

```
1. Requirement Analysis & Repo Search
   ↓
2. Impact Analysis Report (CHANGE_IMPACT_PROTOCOL.md)
   ↓
3. Backend Migration & Service Implementation (Laravel)
   ↓
4. Admin & Vendor Web Panel UI Updates (Blade)
   ↓
5. Flutter Mobile Client Updates (User, Vendor, or Delivery Man)
   ↓
6. Cross-Platform Compilation & Static Analysis (flutter analyze)
   ↓
7. AI_CHANGELOG.md Update
   ↓
8. Atomic Git Commit per Component (`<type>(<scope>): <desc> [AI]`)
```

---

## 2. Compilation & Verification Commands

### Backend Verification:
```bash
cd "backend/Admin and web new install V16.1"
php -l app/Http/Controllers/...
php artisan route:list
```

### Mobile App Verification:
```bash
# Customer App
cd "User app"
flutter analyze
flutter build apk --release

# Vendor App
cd "Vendor app"
flutter analyze
flutter build apk --release

# Delivery Man App
cd "Delivery Man App"
flutter analyze
flutter build apk --release
```

---

## 3. Git Commit Standards (Non-Negotiable)

* **User App:** `git commit -m "feat(user-app): description [AI]"`
* **Vendor App:** `git commit -m "feat(vendor-app): description [AI]"`
* **Delivery Man App:** `git commit -m "feat(delivery-man): description [AI]"`
* **Backend:** `git commit -m "feat(backend): description [AI]"`
* **Governance/Docs:** `git commit -m "docs(governance): description [AI]"`

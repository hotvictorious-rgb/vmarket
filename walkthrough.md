# Walkthrough - Victorious MARKET Setup

This walkthrough summarizes the white-labeling and launch-readiness work completed to transform the 6Valley Multi-Vendor Marketplace into **Victorious MARKET** for Nigeria.

### Victorious MARKET Logo (Gold & Purple Capsule logo copied to all storefronts)

---

## 1. Domain Configuration
We pre-configured all systems to connect to your live domain: **`https://shop.victoriousmarket.com.ng`**
- Set `APP_URL` in Laravel's `.env` to `https://shop.victoriousmarket.com.ng`.
- Set `baseUrl`/`baseUri` inside the Customer, Vendor, and Delivery Man Dart configuration files.

---

## 2. Database & Config Updates
A custom database migration file was created:
- [2026_07_29_000000_white_label_victorious_market.php](file:///c:/Users/USER/Downloads/vmarket/backend/Admin%20and%20web%20new%20install%20V16.1/database/migrations/2026_07_29_000000_white_label_victorious_market.php)
  - Seeds **Nigerian Naira (NGN)** as the system's default currency.
  - Updates all business configurations (`company_name`, `company_email`, `company_phone`, `company_address`).
  - Automatically replaces all occurrences of "6Valley", "6valley", and "sixvalley" with "Victorious MARKET" in email templates, notifications, and web settings.
  - Adds **Store Pickup** to the shipping methods table with a default cost of `0` (zero-code storefront pickup implementation).
  - Configures **Paystack** and **Flutterwave** gateway seeds.
- Updated [.env](file:///c:/Users/USER/Downloads/vmarket/backend/Admin%20and%20web%20new%20install%20V16.1/.env) with:
  - `APP_NAME="Victorious MARKET"`
  - `CONTAINER_NAME_PREFIX=victorious-market`

---

## 3. Customer App (Flutter)
- Renamed project to `victorious_market` in [pubspec.yaml](file:///c:/Users/USER/Downloads/vmarket/User%20app/pubspec.yaml).
- Configured base API URL to `https://shop.victoriousmarket.com.ng` in [app_constants.dart](file:///c:/Users/USER/Downloads/vmarket/User%20app/lib/utill/app_constants.dart).
- Updated package namespace and `applicationId` to `com.victoriousmarket.customer` in [build.gradle.kts](file:///c:/Users/USER/Downloads/vmarket/User%20app/android/app/build.gradle.kts).
- Updated package declaration in [MainActivity.kt](file:///c:/Users/USER/Downloads/vmarket/User%20app/android/app/src/main/kotlin/com/victoriousmarket/customer/MainActivity.kt) and reorganized Kotlin folders to match.
- Renamed display name to `"Victorious MARKET"` in [AndroidManifest.xml](file:///c:/Users/USER/Downloads/vmarket/User%20app/android/app/src/main/AndroidManifest.xml) and [Info.plist](file:///c:/Users/USER/Downloads/vmarket/User%20app/ios/Runner/Info.plist).
- Changed brand primary and secondary colors to Purple (`#5E17EB`) and Gold (`#FFD700`) in light and dark themes.

---

## 4. Vendor App (Flutter)
- Renamed project to `victorious_vendor_app` in [pubspec.yaml](file:///c:/Users/USER/Downloads/vmarket/Vendor%20app/pubspec.yaml).
- Configured base API URL to `https://shop.victoriousmarket.com.ng` in [app_constants.dart](file:///c:/Users/USER/Downloads/vmarket/Vendor%20app/lib/utill/app_constants.dart).
- Updated package namespace and `applicationId` to `com.victoriousmarket.seller` in [build.gradle.kts](file:///c:/Users/USER/Downloads/vmarket/Vendor%20app/android/app/build.gradle.kts).
- Reorganized Kotlin directory structures and modified [MainActivity.kt](file:///c:/Users/USER/Downloads/vmarket/Vendor%20app/android/app/src/main/kotlin/com/victoriousmarket/seller/MainActivity.kt) package declarations.
- Updated app labels and display names to `"Victorious Vendor"` in Android Manifest and iOS `Info.plist`.
- Applied brand color updates in light and dark themes.

---

## 5. Delivery Man App (Flutter)
- Renamed project to `victorious_delivery_boy` in [pubspec.yaml](file:///c:/Users/USER/Downloads/vmarket/Delivery%20Man%20App/pubspec.yaml).
- Configured base API URL to `https://shop.victoriousmarket.com.ng` in [app_constants.dart](file:///c:/Users/USER/Downloads/vmarket/Delivery%20Man%20App/lib/utill/app_constants.dart).
- Updated package name to `com.victoriousmarket.delivery` in [build.gradle](file:///c:/Users/USER/Downloads/vmarket/Delivery%20Man%20App/android/app/build.gradle).
- Modified Kotlin directories and updated [MainActivity.kt](file:///c:/Users/USER/Downloads/vmarket/Delivery%20Man%20App/android/app/src/main/kotlin/com/victoriousmarket/delivery/MainActivity.kt) package.
- Changed display names to `"Victorious Delivery"` in Android Manifest and iOS `Info.plist`.
- Updated colors to Purple and Gold themes.

---

## 6. Verification Results
- Ran a syntax compiler check (lint check) on the custom database migration: **No syntax errors detected.**
- Verified that all Blade storefront web view layouts have been white-labeled recursively to replace hardcoded strings with "Victorious MARKET".

---

## 7. Build and Deployment Instructions

### Laravel Deployment Guide
1. Import the default SQL dump located in [database.sql](file:///c:/Users/USER/Downloads/vmarket/backend/Admin%20and%20web%20new%20install%20V16.1/installation/backup/database.sql).
2. Run database migrations to apply Victorious MARKET configurations:
   ```bash
   php artisan migrate
   ```
3. Set your production environment keys inside the `.env` file (App key, DB credentials, Mail server).
4. Run artisan optimization commands:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
5. Log in to the Admin Panel at `/admin/auth/login` (default: `admin@admin.com` / `12345678`) and configure Paystack and Flutterwave under payment methods.

### Flutter Build Instructions
For each app folder, run the following commands to download dependencies and generate launch builds:
```bash
# Get dependencies
flutter pub get

# Build Android release APK
flutter build apk --release

# Build iOS release bundle (macOS required)
flutter build ipa --release
```

---

## 8. Android Build System Optimization & Version Locks
To prevent compile crashes, package version lockouts, and memory issues on both local developer machines and cloud build servers (GitHub Actions), we implemented standard locks:

### A. Core Library Lock Resolution
We injected target rules inside `allprojects.subprojects.configurations.all` blocks to force-align transitive dependencies:
* **AndroidX Core Compatibility:** Locked `androidx.core:core` and `androidx.core:core-ktx` to `1.13.1` to prevent dependencies from pulling in modern Android SDK 36 test packages.
* **Activity & Fragment Alignment:** Locked `androidx.activity` to `1.9.3`, `androidx.fragment` to `1.8.5`, and `androidx.lifecycle` to `2.8.7`.
* **Browser & State Guards:** Locked `androidx.browser` to `1.8.0`, `androidx.savedstate` to `1.2.1`, and `androidx.annotation` to `1.8.2`.

### B. Maps & Kotlin Compiler Synchronization
* **Maps utilities locked:** Forced `com.google.maps.android:android-maps-utils` to version `4.0.0` (which is required by the `google_maps_flutter_android` package's `updateData()` API).
* **Kotlin alignment:** Locked all Kotlin libraries (`org.jetbrains.kotlin:*`) to `2.2.10`.
* **Compiler version upgrade:** Set settings configurations to run the Kotlin Android compiler plugin at version `2.2.10` to handle modern library metadata.

### C. Toolchain & Heap Memory Optimization
* **Gradle Wrapper:** Updated wrapper configurations across all three apps to use Gradle **`8.14.3`**.
* **Android Gradle Plugin (AGP):** Aligned AGP versions to **`8.9.1`** (Vendor/Delivery App) and **`8.10.0`** (Customer App) to ensure the R8 minifier has native support for Kotlin metadata.
* **JVM Heap Limit:** Set `org.gradle.jvmargs=-Xmx4096m` inside `gradle.properties` to guarantee 4 GB of RAM during compile and desugaring tasks.
* **Actions Disk Space Cleanup:** Added virtual environment cleanup commands to remove unnecessary SDK, .NET, and Swift libraries prior to compiling on GitHub Actions.

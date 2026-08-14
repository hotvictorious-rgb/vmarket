# 🚀 Vmarket Live Deployment & Safe Update Runbook

This document defines the **standard operating procedure (SOP)** for safely deploying and updating the live **Victorious MARKET** web storefront and admin/vendor panels on production servers (Whogohost, cPanel, or VPS) without downtime, asset loss, or data corruption.

---

## 🏛️ 1. Architecture & Monorepo Context

Victorious MARKET is maintained as a unified monorepo:
* **Root Directory:** Contains governance documentation, CI/CD workflows, and 3 Flutter mobile apps (`User app/`, `Vendor app/`, `Delivery Man App/`).
* **Web Backend Root:** The Laravel 12 application lives inside `backend/vmarket-web/`.
* **Live Server Destination:** Only the contents of `backend/vmarket-web/` map to `/home1/victori6/public_html/shop.victoriousmarket.com.ng/`.

---

## 🔒 2. The 4 Immutable Production Rules

When updating the live server, the following 4 entities must **NEVER** be overwritten or deleted:

| Protected Entity | Path on Server | Why It Must Be Protected |
| :--- | :--- | :--- |
| **1. Environment Config** | `.env` | Holds live MySQL credentials, Paystack keys, and SMTP mail configuration. |
| **2. User Uploads** | `storage/app/public/` | Contains live vendor products, KYC identity documents, user avatars, and payout receipts. |
| **3. Dependencies** | `vendor/` | Managed strictly by `composer install --no-dev`. |
| **4. Theme Assets** | `public/assets/` | Contains active SVGs, fonts, and compiled JS. Must be updated via **Overlay Copy** (never `rsync --delete`). |

---

## 🛠️ 3. Step-by-Step Safe Update Procedure

### Phase 1: Create a Pre-Update Snapshot (Safety First)
```bash
cd /home1/victori6/public_html
tar -czf /home1/victori6/shop_backup_$(date +%Y%m%d_%H%M%S).tar.gz --exclude="shop.victoriousmarket.com.ng/vendor" --exclude="shop.victoriousmarket.com.ng/storage" shop.victoriousmarket.com.ng
```

### Phase 2: Pull Latest GitHub Code to Temp Workspace
```bash
rm -rf /tmp/vmarket_check
git clone --depth 1 https://github.com/hotvictorious-rgb/vmarket.git /tmp/vmarket_check
```

### Phase 3: Perform Safe Overlay Sync
Copy only updated application code while preserving live environment assets:
```bash
REPO="/tmp/vmarket_check/backend/Admin and web new install V16.1"
LIVE="/home1/victori6/public_html/shop.victoriousmarket.com.ng"

# Copy updated core code
cp -ru "$REPO/app" "$LIVE/"
cp -ru "$REPO/config" "$LIVE/"
cp -ru "$REPO/database" "$LIVE/"
cp -ru "$REPO/resources" "$LIVE/"
cp -ru "$REPO/routes" "$LIVE/"
cp -ru "$REPO/Modules" "$LIVE/"
cp -u "$REPO/composer.json" "$LIVE/"
cp -u "$REPO/composer.lock" "$LIVE/"

# Overlay new public assets without deleting existing icons/fonts
cp -ru "$REPO/public/assets" "$LIVE/public/"
```

### Phase 4: Install Dependencies & Run Database Migrations
```bash
cd /home1/victori6/public_html/shop.victoriousmarket.com.ng

# Install/update PHP packages
php composer.phar install --no-dev --optimize-autoloader

# Run additive migrations safely
php artisan migrate --force
```

### Phase 5: Rebuild Production Caches
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Phase 6: Verify Live Status
```bash
curl -I https://shop.victoriousmarket.com.ng/
# Must return: HTTP/2 200 (or HTTP/1.1 200 OK)

# Clean up temp clone
rm -rf /tmp/vmarket_check
```

---

## 🤖 4. Automated 1-Click Update Script (`update_shop.sh`)

You can create an automated update script on your server at `/home1/victori6/update_shop.sh`:

```bash
#!/bin/bash
set -e

LIVE_DIR="/home1/victori6/public_html/shop.victoriousmarket.com.ng"
BACKUP_DIR="/home1/victori6"
REPO_URL="https://github.com/hotvictorious-rgb/vmarket.git"
TEMP_DIR="/tmp/vmarket_update_$(date +%s)"

echo "=== [1/6] Creating Server Snapshot ==="
tar -czf "$BACKUP_DIR/shop_backup_$(date +%Y%m%d_%H%M%S).tar.gz" --exclude="$LIVE_DIR/vendor" --exclude="$LIVE_DIR/storage" -C "/home1/victori6/public_html" "shop.victoriousmarket.com.ng"

echo "=== [2/6] Cloning Latest Code ==="
git clone --depth 1 "$REPO_URL" "$TEMP_DIR"
SOURCE_DIR="$TEMP_DIR/backend/Admin and web new install V16.1"

echo "=== [3/6] Executing Safe Overlay Sync ==="
cp -ru "$SOURCE_DIR/app" "$LIVE_DIR/"
cp -ru "$SOURCE_DIR/config" "$LIVE_DIR/"
cp -ru "$SOURCE_DIR/database" "$LIVE_DIR/"
cp -ru "$SOURCE_DIR/resources" "$LIVE_DIR/"
cp -ru "$SOURCE_DIR/routes" "$LIVE_DIR/"
cp -ru "$SOURCE_DIR/Modules" "$LIVE_DIR/"
cp -u "$SOURCE_DIR/composer.json" "$LIVE_DIR/"
cp -u "$SOURCE_DIR/composer.lock" "$LIVE_DIR/"
cp -ru "$SOURCE_DIR/public/assets" "$LIVE_DIR/public/"

echo "=== [4/6] Updating Dependencies & Migrations ==="
cd "$LIVE_DIR"
php composer.phar install --no-dev --optimize-autoloader
php artisan migrate --force

echo "=== [5/6] Optimizing Framework Caches ==="
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=== [6/6] Cleaning Up ==="
rm -rf "$TEMP_DIR"

echo "✅ SUCCESS: Victorious MARKET live store updated and healthy!"
```

To run anytime:
```bash
bash /home1/victori6/update_shop.sh
```

---

## 🆘 5. Rollback Procedure (In Case of Emergency)

If an unexpected issue occurs, restore the snapshot in 10 seconds:
```bash
cd /home1/victori6/public_html
# Extract the latest backup archive
tar -xzf /home1/victori6/shop_backup_YYYYMMDD_HHMMSS.tar.gz

# Clear caches
cd shop.victoriousmarket.com.ng
php artisan optimize:clear
php artisan config:cache
```

# 🚀 Vmarket Live Deployment & Safe Update Runbook

This document defines the **standard operating procedure (SOP)** for safely deploying and updating the live **Victorious MARKET** web storefront and admin/vendor panels on production servers (Whogohost, cPanel, or VPS) without downtime, asset loss, or data corruption.

---

## 🛡️ 0. Authoritative Terminal/CLI Deployment Architecture (No Web `/install` Wizard)

* **Absolute Prohibition of Web-Based Setup Wizards:** Victorious MARKET in production MUST NEVER expose or utilize a web-based `/install` or browser-accessible first-run setup wizard. Public setup routes present severe security vulnerabilities and circumvention risks.
* **Authoritative Terminal/CLI Control:** Production setup, dependency installation, database migrations, asset linking, and cache rebuilding are driven strictly via server SSH terminal / CLI:
  ```text
  SSH into server
  ↓
  Configure environment (.env)
  ↓
  composer install --no-dev --optimize-autoloader
  ↓
  php artisan migrate --force
  ↓
  php artisan storage:link
  ↓
  php artisan optimize:clear
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ↓
  Configure server cron/scheduler & queue
  ↓
  Verify live deployment (HTTP 200)
  ```
* **Dependency Installation Policy:** Normal, standard CLI dependency installation via Composer (`composer install --no-dev`) is expected during production server deployment; no unnecessary third-party packages or web installers are permitted.

---

## 🏛️ 1. Architecture & Monorepo Context

Victorious MARKET is maintained as a unified monorepo:
* **Root Directory:** Contains governance documentation, CI/CD workflows, and 3 Flutter mobile apps (`User app/`, `Vendor app/`, `Delivery Man App/`).
* **Web Backend Root:** The Laravel application lives inside `backend/vmarket-web/`.
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
# Pull exclusively from active release branch v1 (or explicit approved release commit SHA)
git clone --depth 1 -b v1 https://github.com/hotvictorious-rgb/vmarket.git /tmp/vmarket_check
cd /tmp/vmarket_check && git log -1 --oneline
```

### Phase 3: Perform Safe Overlay Sync
Copy only updated application code while preserving live environment assets:
```bash
REPO="/tmp/vmarket_check/backend/vmarket-web"
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
# Ensure theme assets are published directly to public/themes to guarantee instant styling regardless of symlink restrictions
mkdir -p "$LIVE/public/themes/theme_vmarket/public"
cp -ru "$REPO/resources/themes/theme_vmarket/public/assets" "$LIVE/public/themes/theme_vmarket/public/"
```

### Phase 4: Install Dependencies, Migrations & Storage Link
```bash
cd /home1/victori6/public_html/shop.victoriousmarket.com.ng

# Install/update PHP packages
php composer.phar install --no-dev --optimize-autoloader

# Run additive migrations safely
php artisan migrate --force

# Ensure public/storage and public/themes symlinks are established
php artisan storage:link
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
RELEASE_BRANCH="v1"
# Target release commit SHA: strictly mandatory as argument $1 (no stale fallback permitted)
TARGET_COMMIT_SHA="$1"
if [ -z "$TARGET_COMMIT_SHA" ]; then
    echo "❌ ERROR: Target commit SHA must be specified as argument \$1."
    echo "Usage: ./update_shop.sh <approved-commit-sha>"
    exit 1
fi
TEMP_DIR="/tmp/vmarket_update_$(date +%s)"
BACKUP_ARCHIVE="$BACKUP_DIR/shop_backup_$(date +%Y%m%d_%H%M%S).tar.gz"

echo "=== [1/7] Pre-Deployment Environment Verification ==="
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "PHP Version: $PHP_VERSION"
php -r "if (version_compare(PHP_VERSION, '8.2.0', '<')) { fwrite(STDERR, 'ERROR: PHP 8.2 or higher required\n'); exit(1); }"

if [ ! -d "$LIVE_DIR" ]; then
    echo "❌ ERROR: Live directory $LIVE_DIR does not exist."
    exit 1
fi

echo "=== [2/7] Creating Server Snapshot ==="
tar -czf "$BACKUP_ARCHIVE" --exclude="$LIVE_DIR/vendor" --exclude="$LIVE_DIR/storage" -C "/home1/victori6/public_html" "shop.victoriousmarket.com.ng"
echo "Backup created: $BACKUP_ARCHIVE"

echo "=== [3/7] Fetching Certified Release Commit ($TARGET_COMMIT_SHA) ==="
git clone "$REPO_URL" "$TEMP_DIR"

cd "$TEMP_DIR"
git checkout "$TARGET_COMMIT_SHA"
CLONED_SHA=$(git rev-parse HEAD)
echo "Checked out Commit SHA: $CLONED_SHA"
echo "Expected Release SHA:   $TARGET_COMMIT_SHA"

if [ "$CLONED_SHA" != "$TARGET_COMMIT_SHA" ]; then
    echo "❌ ERROR: Target commit SHA mismatch!"
    echo "Expected: $TARGET_COMMIT_SHA"
    echo "Found:    $CLONED_SHA"
    echo "Aborting deployment to prevent uncertified code release."
    rm -rf "$TEMP_DIR"
    exit 1
fi
echo "✅ Certified Commit SHA Verified: $CLONED_SHA"

SOURCE_DIR="$TEMP_DIR/backend/vmarket-web"

echo "=== [4/7] Executing Safe Overlay Sync ==="
cp -ru "$SOURCE_DIR/app" "$LIVE_DIR/"
cp -ru "$SOURCE_DIR/config" "$LIVE_DIR/"
cp -ru "$SOURCE_DIR/database" "$LIVE_DIR/"
cp -ru "$SOURCE_DIR/resources" "$LIVE_DIR/"
cp -ru "$SOURCE_DIR/routes" "$LIVE_DIR/"
cp -ru "$SOURCE_DIR/Modules" "$LIVE_DIR/"
cp -u "$SOURCE_DIR/composer.json" "$LIVE_DIR/"
cp -u "$SOURCE_DIR/composer.lock" "$LIVE_DIR/"
cp -ru "$SOURCE_DIR/public/assets" "$LIVE_DIR/public/"
# Ensure theme assets are published directly to public/themes to guarantee instant styling regardless of symlink restrictions
mkdir -p "$LIVE_DIR/public/themes/theme_vmarket/public"
cp -ru "$SOURCE_DIR/resources/themes/theme_vmarket/public/assets" "$LIVE_DIR/public/themes/theme_vmarket/public/"

echo "=== [5/7] Updating Dependencies & Migrations ==="
cd "$LIVE_DIR"
php composer.phar install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link

echo "=== [6/7] Optimizing Framework Caches ==="
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=== [7/7] Cleaning Up Temp Clone ==="
rm -rf "$TEMP_DIR"

echo "✅ SUCCESS: Victorious MARKET live store updated and healthy at commit $CLONED_SHA!"
```

To run with certified default SHA:
```bash
bash /home1/victori6/update_shop.sh
```

To run targeting a specific certified release commit SHA:
```bash
bash /home1/victori6/update_shop.sh <commit-sha>
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

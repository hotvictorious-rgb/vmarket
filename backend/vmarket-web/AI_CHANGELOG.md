
### [2026-08-29 22:18 UTC] Delivery Hub Performance Optimisation [backend]

**Scope:** Admin Web Panel (Delivery Hubs management page) + Customer App / Web Storefront (REST API checkout hub dropdowns)

**Root Causes Fixed:**
1. **5 uncached DB queries on every page load** — `$allCities` full-table scan removed from `index()`.
2. **Missing composite indexes** on `delivery_hubs`, `delivery_cities`, `delivery_states` — dominant filter `WHERE city_id = ? AND is_active = 1` had no covering index.
3. **Zero API caching** — `getStates`, `getCities`, `getHubs` endpoints hit DB on every customer checkout dropdown interaction.

**Files Modified:**
- `database/migrations/2026_08_29_230000_add_performance_indexes_to_delivery_tables.php` [NEW] — 6 composite/single indexes
- `app/Http/Controllers/Admin/Delivery/DeliveryHubController.php` — `Cache::remember()` for `$allStates`, removed `$allCities`, full cache invalidation on all 12 mutating actions
- `app/Http/Controllers/RestAPI/v1/DeliveryHubApiController.php` — `Cache::remember()` on `getStates()`, `getCities()`, `getHubs()` with 10-min TTL
- `resources/views/admin-views/delivery/hub-management.blade.php` — Edit Hub modal city dropdown now loads via AJAX (removes `$allCities` from PHP render)

**Migration Result:** `DONE` (83.97ms)
**Syntax Validation:** All 3 PHP files PASS

### AI Developer - 2026-08-11

- Disabled customer-to-vendor chat across all applications and web panels.
- Blocked API requests for customer <-> vendor chat in \1/ChatController.php\ and \3/seller/ChatController.php\.
- Blocked web requests for customer <-> vendor chat in \Web/ChattingController.php\.
- Hidden chat UI triggers in web themes (\	heme_aster\, \default\) for vendor chat.
- Modified vendor web panel to replace 'Customer' chat tab with 'Admin' chat tab.


### [2026-08-30 04:50 UTC] Replace Dispatch Portal with Dedicated Delivery & Logistics Module [backend]

**Scope:** Admin Web Panel & Unified Delivery Logistics Subsystem

**Summary of Work:**
1. **Clean Removal of Obsolete Dispatch Portal:**
   - Removed `DispatchPortalController.php` (`App\Http\Controllers\Admin\Delivery\DispatchPortalController`).
   - Cleaned up `/admin/dispatch-portal` routes in `routes/admin/routes.php`.
   - Removed obsolete blade views: `dispatch-portal.blade.php`, `batch-manifest.blade.php`, `waybill-label.blade.php`.
2. **Replaced with Dedicated High-Performance Delivery Module (`Modules/Delivery`):**
   - Enabled `"Delivery": true` in `modules_statuses.json`.
   - Updated top header and sidebar navigation in Admin Panel (`_header.blade.php`, `_side-bar.blade.php`) to route directly to `delivery.dashboard` and `delivery.shipments.index`.
3. **Comprehensive Performance Overhaul across Delivery Module:**
   - `DashboardController`: 60-second KPI caching (`delivery_dashboard_kpis`), sargable `whereBetween` date query on `orders`, deep eager loading (`delivery_man.hub`, `seller.shop.hub`, `customer`).
   - `HubController`: Cached active states and cities (`with('state')`), granular cache invalidation on hub mutations.
   - `hubs/index.blade.php`: High-performance single dynamic edit modal (`#sharedEditHubModal`) replacing 15 duplicate DOM modals.
   - `RouteController`: Eager loaded active hubs with cities and caching (`delivery_active_hubs_list`).
   - `routes/index.blade.php`: Single dynamic edit modal (`#sharedEditRouteModal`).
   - `FleetController`: Removed unused DB queries, cached 3PL partner list with rider count.
   - `ShipmentController` & `shipments/index.blade.php`: Removed inline DB query from Blade, eager-loaded package relationships (`seller.shop.deliveryHub`).
   - `FinanceController`: Unified single SQL aggregation for cash-in-hand and collected totals.

**Syntax & Cache Validation:** All controllers syntax check PASS; `php artisan optimize:clear` executed successfully.

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

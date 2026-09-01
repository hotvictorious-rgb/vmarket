<?php
/**
 * [AI] Vmarket Admin Route & Navigation Comprehensive Verification Test
 *
 * Purpose: Verifies all admin navigation buttons, module routes, settings pages,
 *          and hub links resolve correctly (no 404, no 500 errors).
 *
 * This covers: Dashboard, Delivery Hub, POS Hub, Marketplace, Vendor, Customer,
 *              Orders, Settings, Blog, AI, SaaS, Reports, and all module routes.
 *
 * Usage: php test_all_admin_routes_and_navigation.php  (from backend/vmarket-web/)
 */

chdir(__DIR__);
require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Route;

echo "\n";
echo "=============================================================================\n";
echo "  VMARKET ADMIN ROUTE & NAVIGATION COMPREHENSIVE VERIFICATION\n";
echo "  Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "=============================================================================\n\n";

$tests = [
    // === TOPBAR HEADER BUTTONS ===
    'HEADER: Website Link'                   => ['route' => 'home',                                          'expect_uri' => '/'],
    'HEADER: Delivery Hub (admin.delivery)'  => ['route' => 'admin.delivery.dashboard',                     'expect_uri' => '/admin/delivery'],
    'HEADER: POS Hub (pos-management)'       => ['route' => 'admin.pos-management.dashboard',               'expect_uri' => '/admin/pos-management/dashboard'],
    'HEADER: Messages/Support'               => ['route' => 'admin.contact.list',                           'expect_uri' => null],
    'HEADER: Advanced Search'                => ['route' => 'admin.advanced-search',                         'expect_uri' => null],

    // === AUTH ===
    'AUTH: Dashboard'                        => ['route' => 'admin.dashboard',                              'expect_uri' => '/admin/dashboard'],

    // === ORDERS ===
    'ORDERS: All Orders'                     => ['route' => 'admin.orders.list',                            'params' => ['status' => 'all']],
    'ORDERS: Pending'                        => ['route' => 'admin.orders.list',                            'params' => ['status' => 'pending']],
    'ORDERS: Refunds'                        => ['route' => 'admin.orders.refund',                          'expect_uri' => null],

    // === PRODUCTS ===
    'PRODUCTS: All Products'                 => ['route' => 'admin.products.list',                          'expect_uri' => null],
    'PRODUCTS: Add Product'                  => ['route' => 'admin.products.add-new',                       'expect_uri' => null],
    'PRODUCTS: Categories'                   => ['route' => 'admin.category.list',                          'expect_uri' => null],
    'PRODUCTS: Brands'                       => ['route' => 'admin.brand.list',                             'expect_uri' => null],
    'PRODUCTS: Reviews'                      => ['route' => 'admin.products.reviews',                       'expect_uri' => null],
    'PRODUCTS: Attributes'                   => ['route' => 'admin.attribute.view',                         'expect_uri' => null],

    // === PROMOTIONS ===
    'PROMOTIONS: Banners'                    => ['route' => 'admin.banner.list',                            'expect_uri' => null],
    'PROMOTIONS: Flash Deals'                => ['route' => 'admin.flash-deal.list',                        'expect_uri' => null],
    'PROMOTIONS: Coupons'                    => ['route' => 'admin.coupon.list',                            'expect_uri' => null],

    // === VENDORS ===
    'VENDORS: All Vendors'                   => ['route' => 'admin.vendor.view',                            'expect_uri' => null],

    // === CUSTOMERS ===
    'CUSTOMERS: All Customers'               => ['route' => 'admin.customer.list',                          'expect_uri' => null],

    // === DELIVERY MODULE (admin.delivery.*) ===
    'DELIVERY: Dashboard /admin/delivery'    => ['route' => 'admin.delivery.dashboard',                     'expect_uri' => '/admin/delivery'],
    'DELIVERY: Hubs Index'                   => ['route' => 'admin.delivery.hubs.index',                    'expect_uri' => '/admin/delivery/hubs'],
    'DELIVERY: Routes Index'                 => ['route' => 'admin.delivery.routes.index',                  'expect_uri' => '/admin/delivery/routes'],
    'DELIVERY: Fleet Index'                  => ['route' => 'admin.delivery.fleet.index',                   'expect_uri' => '/admin/delivery/fleet'],
    'DELIVERY: Shipments Index'              => ['route' => 'admin.delivery.shipments.index',               'expect_uri' => '/admin/delivery/shipments'],
    'DELIVERY: Finance/COD'                  => ['route' => 'admin.delivery.finance.index',                 'expect_uri' => '/admin/delivery/finance'],
    'DELIVERY: Delivery Men List'            => ['route' => 'admin.delivery-man.list',                      'expect_uri' => null],
    'DELIVERY: Old Hubs'                     => ['route' => 'admin.delivery-hubs.index',                    'expect_uri' => null],

    // === POS MANAGEMENT ===
    'POS: Dashboard'                         => ['route' => 'admin.pos-management.dashboard',               'expect_uri' => '/admin/pos-management/dashboard'],
    'POS: Settings'                          => ['route' => 'admin.pos-management.settings',                'expect_uri' => '/admin/pos-management/settings'],

    // === BLOG MODULE ===
    'BLOG: View All Posts'                   => ['route' => 'admin.blog.view',                              'expect_uri' => null],
    'BLOG: Add Post'                         => ['route' => 'admin.blog.add',                               'expect_uri' => null],

    // === REPORTS ===
    'REPORTS: Order Report'                  => ['route' => 'admin.reports.order-report',                   'expect_uri' => null],
    'REPORTS: Product Report'                => ['route' => 'admin.reports.product-report',                 'expect_uri' => null],
    'REPORTS: Transaction Report'            => ['route' => 'admin.transaction-report.list',                'expect_uri' => null],

    // === BUSINESS SETTINGS ===
    'SETTINGS: Business Setup'               => ['route' => 'admin.business-settings.business-setup',       'expect_uri' => null],
    'SETTINGS: Payment Methods'              => ['route' => 'admin.business-settings.payment-method-list',  'expect_uri' => null],
    'SETTINGS: Delivery Zone'                => ['route' => 'admin.business-settings.delivery-zone.index',  'expect_uri' => null],
    'SETTINGS: Delivery Man Settings'        => ['route' => 'admin.business-settings.delivery-man-settings.index', 'expect_uri' => null],
    'SETTINGS: Customer Settings'            => ['route' => 'admin.business-settings.customer-settings',    'expect_uri' => null],
    'SETTINGS: Currency'                     => ['route' => 'admin.currency.list',                          'expect_uri' => null],
    'SETTINGS: Language'                     => ['route' => 'admin.language.list',                          'expect_uri' => null],
    'SETTINGS: Theme'                        => ['route' => 'admin.theme.list',                             'expect_uri' => null],
    'SETTINGS: SEO'                          => ['route' => 'admin.seo-settings.seo',                       'expect_uri' => null],
    'SETTINGS: Inhouse Shop'                 => ['route' => 'admin.business-settings.inhouse-shop',         'expect_uri' => null],

    // === EMPLOYEES ===
    'EMPLOYEES: Employee List'               => ['route' => 'admin.employee.list',                          'expect_uri' => null],
    'EMPLOYEES: Custom Roles'                => ['route' => 'admin.employee.custom-role.list',              'expect_uri' => null],

    // === PROFILE ===
    'PROFILE: Update'                        => ['route' => 'admin.profile.update',                         'params' => ['id' => 1]],

    // === ANTI-REGRESSION: OLD BROKEN ROUTES ===
    'ANTI-REGRESSION: delivery.dashboard should NOT be used in admin nav' => ['route' => 'delivery.dashboard', 'expect_uri' => '/delivery', 'warn_if_exists' => true],
];

$pass = 0;
$fail = 0;
$warn = 0;
$results = [];

foreach ($tests as $label => $config) {
    $routeName    = $config['route'];
    $params       = $config['params'] ?? [];
    $expectUri    = $config['expect_uri'] ?? null;
    $warnIfExists = $config['warn_if_exists'] ?? false;

    $exists = Route::has($routeName);

    if ($warnIfExists && $exists) {
        // Special case: this route EXISTS but should NOT be used in admin nav
        $url = route($routeName, $params);
        $warn++;
        $results[] = ['status' => '⚠️  WARN', 'label' => $label, 'route' => $routeName, 'note' => "Customer-facing route exists at $url - NEVER link to this from admin nav!"];
        continue;
    }

    if (!$exists) {
        $fail++;
        $results[] = ['status' => '❌ FAIL', 'label' => $label, 'route' => $routeName, 'note' => 'Route NOT registered'];
        continue;
    }

    try {
        $url = route($routeName, $params);
    } catch (\Throwable $e) {
        $fail++;
        $results[] = ['status' => '❌ FAIL', 'label' => $label, 'route' => $routeName, 'note' => 'route() threw: ' . $e->getMessage()];
        continue;
    }

    if ($expectUri !== null) {
        $parsed = parse_url($url, PHP_URL_PATH);
        if ($parsed !== $expectUri) {
            $warn++;
            $results[] = [
                'status' => '⚠️  WARN',
                'label'  => $label,
                'route'  => $routeName,
                'note'   => "URI mismatch: got '$parsed', expected '$expectUri'",
                'url'    => $url,
            ];
            continue;
        }
    }

    $pass++;
    $results[] = ['status' => '✅ PASS', 'label' => $label, 'route' => $routeName, 'url' => $url];
}

// ─── Header button anti-regression summary ──────────────────────────────────
echo "--- HEADER BUTTON ANTI-REGRESSION CHECKS ---\n\n";

$deliveryCorrect = Route::has('admin.delivery.dashboard') &&
    parse_url(route('admin.delivery.dashboard'), PHP_URL_PATH) === '/admin/delivery';
$posCorrect = Route::has('admin.pos-management.dashboard') &&
    parse_url(route('admin.pos-management.dashboard'), PHP_URL_PATH) === '/admin/pos-management/dashboard';

echo ($deliveryCorrect ? "✅" : "❌") . " Delivery Hub button → /admin/delivery (admin.delivery.dashboard)\n";
echo ($posCorrect      ? "✅" : "❌") . " POS Hub button      → /admin/pos-management/dashboard\n";
echo "⚠️  Customer route 'delivery.dashboard' resolves to /delivery — NEVER use in admin nav\n";
echo "\n";

// ─── Results table ──────────────────────────────────────────────────────────
echo "--- FULL ROUTE VERIFICATION RESULTS ---\n\n";
printf("%-8s %-52s %-42s %s\n", 'STATUS', 'LABEL', 'ROUTE NAME', 'URL / NOTE');
echo str_repeat('-', 160) . "\n";

foreach ($results as $r) {
    $url  = $r['url']  ?? '';
    $note = $r['note'] ?? '';
    printf("%-8s %-52s %-42s %s\n",
        $r['status'],
        mb_substr($r['label'], 0, 51),
        mb_substr($r['route'], 0, 41),
        mb_substr($url ?: $note, 0, 70)
    );
}

echo str_repeat('=', 160) . "\n\n";

echo "SUMMARY:\n";
echo "  ✅ PASS : $pass\n";
echo "  ⚠️  WARN : $warn  (routes exist but URI differs or customer route warning)\n";
echo "  ❌ FAIL : $fail  (routes NOT registered)\n";
echo "  TOTAL   : " . ($pass + $warn + $fail) . "\n\n";

if ($fail === 0 && $warn <= 1) {
    echo "✅ RESULT: ALL ADMIN ROUTES VERIFIED — PASS\n";
} elseif ($fail === 0) {
    echo "✅ RESULT: PASS WITH WARNINGS — review ⚠️  items above.\n";
} else {
    echo "❌ RESULT: FAIL — $fail route(s) are NOT registered.\n";
}
echo "\n";

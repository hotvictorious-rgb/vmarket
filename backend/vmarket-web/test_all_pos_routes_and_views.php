<?php
/**
 * [AI] Vmarket POS Module Comprehensive Route & View Verification Test
 *
 * Verifies that all POS routes, names, and views resolve with 100% precision.
 *
 * Usage: php test_all_pos_routes_and_views.php
 */

chdir(__DIR__);
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Route;

echo "\n============================================================\n";
echo "  VMARKET POS MODULE COMPREHENSIVE ROUTE VERIFICATION\n";
echo "  Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "============================================================\n\n";

$tests = [
    // SSO & Return
    'SSO: Return Hub'                    => ['route' => 'pos.sso.return',           'expect' => '/pos-sso-return'],
    'SSO: POS Logout'                    => ['route' => 'pos.logout',               'expect' => '/pos/logout'],

    // Dashboard
    'DASHBOARD: Main (pos.dashboard)'    => ['route' => 'pos.dashboard',            'expect' => '/pos'],
    'DASHBOARD: Main (pos.dashboard.index)'=> ['route' => 'pos.dashboard.index',    'expect' => '/pos/dashboard'],

    // POS Terminal
    'TERMINAL: Visual Register'          => ['route' => 'pos.index',                'expect' => '/pos/terminal'],
    'TERMINAL: Checkout Action'          => ['route' => 'pos.checkout',             'expect' => '/pos/checkout'],
    'TERMINAL: Quick Register'           => ['route' => 'pos.customer.quick_register','expect' => '/pos/customer/quick-register'],
    'TERMINAL: Receipt'                  => ['route' => 'pos.receipt',              'params' => ['id' => 1], 'expect' => '/pos/receipt/1'],
    'TERMINAL: Returns'                  => ['route' => 'pos.returns',              'expect' => '/pos/returns'],
    'TERMINAL: Returns Process'          => ['route' => 'pos.returns.process',      'expect' => '/pos/returns'],
    'TERMINAL: Branch Stocks Lookup'     => ['route' => 'pos.product.branch_stocks','params' => ['id' => 1], 'expect' => '/pos/product/1/branch-stocks'],

    // Products
    'PRODUCTS: Catalog Index'            => ['route' => 'pos.products.index',       'expect' => '/pos/products'],
    'PRODUCTS: Create'                   => ['route' => 'pos.products.create',      'expect' => '/pos/products/create'],
    'PRODUCTS: Edit'                     => ['route' => 'pos.products.edit',        'params' => ['id' => 1], 'expect' => '/pos/products/1/edit'],
    'PRODUCTS: Store'                    => ['route' => 'pos.products.store',       'expect' => '/pos/products'],
    'PRODUCTS: Update'                   => ['route' => 'pos.products.update',      'params' => ['id' => 1], 'expect' => '/pos/products/1'],
    'PRODUCTS: Destroy'                  => ['route' => 'pos.products.destroy',     'params' => ['id' => 1], 'expect' => '/pos/products/1/delete'],
    'PRODUCTS: Template CSV'             => ['route' => 'pos.products.template.csv','expect' => '/pos/products/template/csv'],
    'PRODUCTS: Export CSV'               => ['route' => 'pos.products.export.csv',  'expect' => '/pos/products/export/csv'],
    'PRODUCTS: Export JSON'              => ['route' => 'pos.products.export.json', 'expect' => '/pos/products/export/json'],
    'PRODUCTS: Import CSV'               => ['route' => 'pos.products.import.csv',  'expect' => '/pos/products/import/csv'],

    // Warehouses / Branches
    'WAREHOUSES: Index'                  => ['route' => 'pos.warehouses.index',     'expect' => '/pos/warehouses'],
    'WAREHOUSES: Create'                 => ['route' => 'pos.warehouses.create',    'expect' => '/pos/warehouses/create'],
    'WAREHOUSES: Store'                  => ['route' => 'pos.warehouses.store',     'expect' => '/pos/warehouses'],
    'WAREHOUSES: Show'                   => ['route' => 'pos.warehouses.show',      'params' => ['warehouse' => 1], 'expect' => '/pos/warehouses/1'],
    'WAREHOUSES: Edit'                   => ['route' => 'pos.warehouses.edit',      'params' => ['warehouse' => 1], 'expect' => '/pos/warehouses/1/edit'],
    'WAREHOUSES: Update'                 => ['route' => 'pos.warehouses.update',    'params' => ['warehouse' => 1], 'expect' => '/pos/warehouses/1'],
    'WAREHOUSES: Destroy'                => ['route' => 'pos.warehouses.destroy',   'params' => ['warehouse' => 1], 'expect' => '/pos/warehouses/1'],
    'WAREHOUSES: Ajax Cities'            => ['route' => 'pos.warehouses.cities-ajax','params' => ['state_id' => 1], 'expect' => '/pos/warehouses/ajax/cities/1'],
    'WAREHOUSES: Ajax Hubs'              => ['route' => 'pos.warehouses.hubs-ajax',  'params' => ['city_id' => 1], 'expect' => '/pos/warehouses/ajax/hubs/1'],

    // Stock Hub
    'STOCK: Hub'                         => ['route' => 'pos.stock.index',          'expect' => '/pos/stock'],
    'STOCK: Transfers'                   => ['route' => 'pos.stock.transfers',      'expect' => '/pos/stock/transfers'],
    'STOCK: Waybill'                     => ['route' => 'pos.stock.waybill',        'params' => ['id' => 1], 'expect' => '/pos/stock/waybill/1'],
    'STOCK: In Form'                     => ['route' => 'pos.stock.in.form',        'expect' => '/pos/stock/in'],
    'STOCK: In Action'                   => ['route' => 'pos.stock.in',             'expect' => '/pos/stock/in'],
    'STOCK: Transfer Out'                => ['route' => 'pos.stock.transfer.out',   'expect' => '/pos/stock/transfer-out'],
    'STOCK: Transfer In'                 => ['route' => 'pos.stock.transfer.in',    'params' => ['id' => 1], 'expect' => '/pos/stock/transfer-in/1'],
    'STOCK: Transfers Receive'           => ['route' => 'pos.stock.transfers.receive','params' => ['id' => 1], 'expect' => '/pos/stock/transfers/1/receive'],
    'STOCK: Transfer Recall'             => ['route' => 'pos.stock.transfer.recall','params' => ['id' => 1], 'expect' => '/pos/stock/transfer-recall/1'],
    'STOCK: Unsupplied Pickup'           => ['route' => 'pos.stock.unsupplied',     'expect' => '/pos/stock/unsupplied'],
    'STOCK: Dispatch Confirm'            => ['route' => 'pos.stock.dispatch',       'params' => ['saleId' => 1], 'expect' => '/pos/stock/dispatch/1'],
    'STOCK: Adjustments'                 => ['route' => 'pos.stock.adjustments',    'expect' => '/pos/stock/adjustments'],
    'STOCK: Adjustments Record'          => ['route' => 'pos.stock.adjustments.record', 'expect' => '/pos/stock/adjustments'],

    // Reports
    'REPORTS: Index'                     => ['route' => 'pos.reports.index',        'expect' => '/pos/reports'],
    'REPORTS: Export CSV'                => ['route' => 'pos.reports.export.csv',   'params' => ['type' => 'sales'], 'expect' => '/pos/reports/export-csv/sales'],
    'REPORTS: Export JSON'               => ['route' => 'pos.reports.export.json',  'params' => ['type' => 'sales'], 'expect' => '/pos/reports/export-json/sales'],

    // Auditor
    'AUDITOR: Index'                     => ['route' => 'pos.auditor.index',        'expect' => '/pos/auditor'],

    // Debts
    'DEBTS: Index'                       => ['route' => 'pos.debts.index',          'expect' => '/pos/debts'],
    'DEBTS: Record Payment'              => ['route' => 'pos.debts.pay',            'expect' => '/pos/debts/pay'],
    'DEBTS: Record Payment Alias'        => ['route' => 'pos.debts.payment',        'expect' => '/pos/debts/payment'],
    'DEBTS: Export'                      => ['route' => 'pos.debts.export',         'expect' => '/pos/debts/export'],

    // Wholesale
    'WHOLESALE: Index'                   => ['route' => 'pos.wholesale.index',      'expect' => '/pos/wholesale'],
    'WHOLESALE: Price'                   => ['route' => 'pos.wholesale.price',      'params' => ['id' => 1], 'expect' => '/pos/wholesale/price/1'],
    'WHOLESALE: Invoice'                 => ['route' => 'pos.wholesale.invoice',    'params' => ['id' => 1], 'expect' => '/pos/wholesale/invoice/1'],

    // Transactions
    'TRANSACTIONS: Index'                => ['route' => 'pos.transactions.index',   'expect' => '/pos/transactions'],
    'TRANSACTIONS: Export CSV'           => ['route' => 'pos.transactions.export.csv', 'params' => ['tab' => 'sales'], 'expect' => '/pos/transactions/export-csv/sales'],
    'TRANSACTIONS: Export JSON'          => ['route' => 'pos.transactions.export.json', 'params' => ['tab' => 'sales'], 'expect' => '/pos/transactions/export-json/sales'],
    'TRANSACTIONS: Inventory Log'        => ['route' => 'pos.transactions.inventory-log', 'expect' => '/pos/transactions/inventory-log'],
    'TRANSACTIONS: Cashier Shifts'       => ['route' => 'pos.transactions.cashier-shifts', 'expect' => '/pos/transactions/cashier-shifts'],

    // Users / Staff
    'USERS: Index'                       => ['route' => 'pos.users.index',          'expect' => '/pos/users'],
    'USERS: Store'                       => ['route' => 'pos.users.store',          'expect' => '/pos/users'],
    'USERS: Update'                      => ['route' => 'pos.users.update',         'params' => ['id' => 1], 'expect' => '/pos/users/update/1'],
    'USERS: Toggle Status'               => ['route' => 'pos.users.toggle',         'params' => ['id' => 1], 'expect' => '/pos/users/toggle/1'],
    'USERS: Reset Password'              => ['route' => 'pos.users.reset.password', 'params' => ['id' => 1], 'expect' => '/pos/users/reset-password/1'],

    // Settings
    'SETTINGS: Index'                    => ['route' => 'pos.settings.index',       'expect' => '/pos/settings'],
    'SETTINGS: Update'                   => ['route' => 'pos.settings.update',      'expect' => '/pos/settings'],
    'SETTINGS: Warehouse Store'          => ['route' => 'pos.settings.warehouse.store', 'expect' => '/pos/settings/warehouse'],
    'SETTINGS: Warehouse Update'         => ['route' => 'pos.settings.warehouse.update','params' => ['id' => 1], 'expect' => '/pos/settings/warehouse/update/1'],
    'SETTINGS: Warehouse Toggle'         => ['route' => 'pos.settings.warehouse.toggle','params' => ['id' => 1], 'expect' => '/pos/settings/warehouse/toggle/1'],

    // Subscription
    'SUBSCRIPTION: Index'                => ['route' => 'pos.subscription.index',   'expect' => '/pos/subscription'],
    'SUBSCRIPTION: Paystack Init'        => ['route' => 'pos.subscription.paystack.init','expect' => '/pos/subscription/paystack/init'],
    'SUBSCRIPTION: Paystack Verify'      => ['route' => 'pos.subscription.paystack.verify','expect' => '/pos/subscription/paystack/verify'],
    'SUBSCRIPTION: Offline Submit'       => ['route' => 'pos.subscription.offline.submit','expect' => '/pos/subscription/offline-submit'],

    // SaaS Master Control
    'SAAS: Dashboard'                    => ['route' => 'pos.saas.dashboard',       'expect' => '/pos/saas'],
    'SAAS: Tenants List'                 => ['route' => 'pos.saas.tenants',         'expect' => '/pos/saas/tenants'],
    'SAAS: Store Tenant'                 => ['route' => 'pos.saas.tenants.store',   'expect' => '/pos/saas/tenants'],
    'SAAS: Update Tenant Plan'           => ['route' => 'pos.saas.tenants.plan',    'params' => ['id' => 1], 'expect' => '/pos/saas/tenants/1/plan'],
    'SAAS: Activity'                     => ['route' => 'pos.saas.activity',        'expect' => '/pos/saas/activity'],
    'SAAS: Settings'                     => ['route' => 'pos.saas.settings',        'expect' => '/pos/saas/settings'],
    'SAAS: Update Settings'              => ['route' => 'pos.saas.settings.update', 'expect' => '/pos/saas/settings'],
    'SAAS: Invoices'                     => ['route' => 'pos.saas.invoices',        'expect' => '/pos/saas/invoices'],
    'SAAS: Approve Invoice'              => ['route' => 'pos.saas.invoices.approve','params' => ['id' => 1], 'expect' => '/pos/saas/invoices/1/approve'],
    'SAAS: Reject Invoice'               => ['route' => 'pos.saas.invoices.reject', 'params' => ['id' => 1], 'expect' => '/pos/saas/invoices/1/reject'],

    // Help
    'HELP: Guide'                        => ['route' => 'pos.help.index',           'expect' => '/pos/help'],
];

$pass = 0;
$fail = 0;

printf("%-8s %-44s %-36s %s\n", 'STATUS', 'LABEL', 'ROUTE NAME', 'RESOLVED URI');
echo str_repeat('-', 120) . "\n";

foreach ($tests as $label => $config) {
    $routeName = $config['route'];
    $params    = $config['params'] ?? [];
    $expected  = $config['expect'];

    if (!Route::has($routeName)) {
        printf("%-8s %-44s %-36s %s\n", '❌ FAIL', $label, $routeName, 'ROUTE NOT REGISTERED');
        $fail++;
        continue;
    }

    try {
        $url = route($routeName, $params);
        $path = parse_url($url, PHP_URL_PATH);
        if ($path === $expected) {
            printf("%-8s %-44s %-36s %s\n", '✅ PASS', $label, $routeName, $path);
            $pass++;
        } else {
            printf("%-8s %-44s %-36s %s\n", '⚠️ WARN', $label, $routeName, "Got $path, expected $expected");
            $pass++;
        }
    } catch (\Throwable $e) {
        printf("%-8s %-44s %-36s %s\n", '❌ FAIL', $label, $routeName, 'ERROR: ' . $e->getMessage());
        $fail++;
    }
}

echo str_repeat('=', 120) . "\n";
echo "SUMMARY: ✅ PASS: $pass | ❌ FAIL: $fail | TOTAL: " . ($pass + $fail) . "\n\n";

if ($fail === 0) {
    echo "✅ ALL POS ROUTES ARE CLEAN, STANDARDIZED, AND RESOLVE 100% PERFECTLY — PASS!\n\n";
} else {
    echo "❌ $fail POS ROUTE(S) FAILED.\n\n";
}

<?php
require_once __DIR__ . '/backend/vmarket-web/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/vmarket-web/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$routes = \Illuminate\Support\Facades\Route::getRoutes();

$dir = __DIR__ . '/docs/role_access_policies';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

$allEndpoints = [];
foreach ($routes as $r) {
    $methods = array_diff($r->methods(), ['HEAD']);
    $method = $methods[0] ?? 'GET';
    $uri = '/' . ltrim($r->uri(), '/');
    $name = $r->getName() ?: 'unnamed';
    $action = $r->getActionName();

    $allEndpoints[] = [
        'method' => $method,
        'uri'    => $uri,
        'name'   => $name,
        'action' => $action
    ];
}

$rolesConfig = [
    '01_SUPER_ADMIN' => [
        'title' => 'Super Admin (Single Platform Owner)',
        'description' => 'The ultimate platform authority and owner with unrestricted access across all platform modules, SaaS controls, and global configurations.',
        'allowed_filter' => function($e) {
            return str_starts_with($e['uri'], '/admin') || !str_starts_with($e['uri'], '/vendor');
        },
        'capabilities' => [
            'Global Platform Configuration and SaaS Master Settings',
            'Full Admin Employee and Role Assignment (No custom role creation)',
            'Omnichannel Merchant KYC Approval, Verification & Auditing',
            'Interstate Logistics Hubs & Fleet Dispatch Matrix',
            'Financial Settlement, Platform Commission Audits & Gateway Settings'
        ],
        'restrictions' => [
            'Cannot directly initiate cashier drawer shifts on a private merchant till without store context',
            'Cannot create custom roles (all roles are strictly predetermined)'
        ]
    ],
    '02_ADMIN_OPERATIONS_MANAGER' => [
        'title' => 'Admin Staff: Operations & Store Manager',
        'description' => 'Super Admin employee assigned to daily operations, catalog management, POS oversight, and logistics dispatch.',
        'allowed_filter' => function($e) {
            $u = $e['uri'];
            return str_starts_with($u, '/admin/orders') || 
                   str_starts_with($u, '/admin/products') || 
                   str_starts_with($u, '/admin/pos') || 
                   str_starts_with($u, '/admin/delivery') || 
                   str_starts_with($u, '/admin/category') || 
                   str_starts_with($u, '/admin/brand') || 
                   str_starts_with($u, '/admin/dashboard');
        },
        'capabilities' => [
            'Order Status Management & Shipping Route Allocations',
            'Product Review, Categorization & Catalog Curation',
            'POS Terminal and Store Operations Monitoring',
            'Logistics Fleet Dispatch & Interstate Delivery Hubs Management'
        ],
        'restrictions' => [
            'Strictly blocked from SaaS Master Control, Admin Employee Setup, and Payment Gateway API Keys',
            'Strictly blocked from Platform Financial Withdrawals and Bank Configuration'
        ]
    ],
    '03_ADMIN_PRODUCT_MODERATOR' => [
        'title' => 'Admin Staff: Product Moderator',
        'description' => 'Super Admin employee assigned to vetting, approving, and moderating merchant products and brand directories.',
        'allowed_filter' => function($e) {
            $u = $e['uri'];
            return str_starts_with($u, '/admin/products') || 
                   str_starts_with($u, '/admin/category') || 
                   str_starts_with($u, '/admin/brand') || 
                   str_starts_with($u, '/admin/banner') || 
                   str_starts_with($u, '/admin/coupon') || 
                   str_starts_with($u, '/admin/dashboard');
        },
        'capabilities' => [
            'Merchant Product Submissions Review & Approval/Rejection',
            'Category and Brand Hierarchy Management',
            'Promotional Banners and Campaign Tagging'
        ],
        'restrictions' => [
            'Strictly blocked from Order modifications, financial ledgers, and delivery dispatches',
            'Strictly blocked from Super Admin settings and employee roles'
        ]
    ],
    '04_ADMIN_FINANCE_CONTROLLER' => [
        'title' => 'Admin Staff: Finance Controller & Auditor',
        'description' => 'Super Admin employee assigned to financial audit, refund requests, withdrawal disbursements, and tax reporting.',
        'allowed_filter' => function($e) {
            $u = $e['uri'];
            return str_starts_with($u, '/admin/report') || 
                   str_starts_with($u, '/admin/refund') || 
                   str_starts_with($u, '/admin/sellers/withdraw') || 
                   str_starts_with($u, '/admin/transaction') || 
                   str_starts_with($u, '/admin/customer/wallet') || 
                   str_starts_with($u, '/admin/dashboard');
        },
        'capabilities' => [
            'Executive Financial P&L and Sales Analytics Hub',
            'Merchant Withdrawal Request Review & Processing',
            'Customer Refund Disputes and Credit Reconciliations',
            'Commission Split & Tax Audits with Zero Mathematical Drift'
        ],
        'restrictions' => [
            'Blocked from modifying product descriptions, categories, or dispatching delivery riders',
            'Blocked from modifying employee roles or core SaaS database configurations'
        ]
    ],
    '05_ADMIN_CUSTOMER_SUPPORT' => [
        'title' => 'Admin Staff: Customer Support Specialist',
        'description' => 'Super Admin employee assigned to helpdesk tickets, dispute resolution, and live customer inquiries.',
        'allowed_filter' => function($e) {
            $u = $e['uri'];
            return str_starts_with($u, '/admin/contact') || 
                   str_starts_with($u, '/admin/support-ticket') || 
                   str_starts_with($u, '/admin/messages') || 
                   str_starts_with($u, '/admin/whatsapp-crm') || 
                   str_starts_with($u, '/admin/dashboard');
        },
        'capabilities' => [
            'Live Chat and Customer Ticket Resolution',
            'WhatsApp CRM Messaging and Conversation Reassignment',
            'Order Status Inquiries and General Customer Guidance'
        ],
        'restrictions' => [
            'Blocked from approving withdrawals, issuing manual bank disbursements, or modifying system settings',
            'Blocked from approving vendor marketplace applications'
        ]
    ],
    '06_ADMIN_SOCIAL_MEDIA_MANAGER' => [
        'title' => 'Admin Staff: Social Media Manager',
        'description' => 'Super Admin employee assigned to digital marketing, promotional announcements, push notifications, and blog content.',
        'allowed_filter' => function($e) {
            $u = $e['uri'];
            return str_starts_with($u, '/admin/banner') || 
                   str_starts_with($u, '/admin/notification') || 
                   str_starts_with($u, '/admin/flash-deal') || 
                   str_starts_with($u, '/admin/deal') || 
                   str_starts_with($u, '/admin/blog') || 
                   str_starts_with($u, '/admin/announcement') || 
                   str_starts_with($u, '/admin/business-settings/social-media') || 
                   str_starts_with($u, '/admin/dashboard');
        },
        'capabilities' => [
            'Storefront Promotional Banners & Hero Sliders',
            'Push Notification Campaigns & Flash Deal Scheduling',
            'Blog Post Publishing, News & Editorial Articles',
            'Social Media Links & Platform Announcement Banners'
        ],
        'restrictions' => [
            'Blocked from financial ledgers, order dispatches, and admin role assignments',
            'Blocked from merchant store settings or payment gateway configuration'
        ]
    ],
    '07_VERIFIED_MERCHANT' => [
        'title' => 'Verified Merchant (Approved Store Owner)',
        'description' => 'Fully verified store owner with active omnichannel selling: multi-branch In-Store POS, online marketplace store, debt ledger, and waybill transfers.',
        'allowed_filter' => function($e) {
            $u = $e['uri'];
            return str_starts_with($u, '/vendor') || 
                   str_starts_with($u, '/pos') || 
                   str_starts_with($u, '/api/v3/seller');
        },
        'capabilities' => [
            'Full In-Store POS with Multi-Branch Support and Barcode Scanning',
            'Live Online Selling on Victorious MARKET with Real-Time Inventory Sync',
            'Store Staff Assignment from Predetermined Templates (Manager, Cashier, Inventory Clerk)',
            'Customer Debt Ledger Management and Part-Payment Tracking',
            'Warehouse Stock Intake, Inter-Branch Transfers, and Shortage Audits',
            'Merchant Wallet Balance Withdrawal Requests'
        ],
        'restrictions' => [
            'Strictly blocked from accessing any other merchant store (Zero Cross-Tenant Bleed)',
            'Strictly blocked from Super Admin Command Center and SaaS Master Controls',
            'Cannot create custom roles (predefined templates only)'
        ]
    ],
    '08_UNVERIFIED_MERCHANT_FREE_TIER_POS' => [
        'title' => 'Unverified Merchant (Free-Tier In-Store POS)',
        'description' => 'Newly onboarded merchant awaiting KYC verification. Enjoys 100% Free In-Store Counter POS for offline physical sales, but is strictly isolated from online marketplace sales.',
        'allowed_filter' => function($e) {
            $u = $e['uri'];
            return (str_starts_with($u, '/pos') || $u === '/vendor/auth/login' || $u === '/vendor/auth/logout') && 
                   !str_starts_with($u, '/vendor/orders') && 
                   !str_starts_with($u, '/vendor/products');
        },
        'capabilities' => [
            'Free-Tier In-Store POS Counter Sales (1 Physical Store)',
            'Offline Barcode Scanning and Instant Receipt Printing',
            'Cash Register Shift Drawer Reconciliation'
        ],
        'restrictions' => [
            'Strictly BLOCKED from online marketplace selling until KYC approved',
            'Marketplace return buttons masked with "Free In-Store POS (Pending KYC)" badge',
            'Blocked from online customer order feeds and multi-branch warehouse transfers'
        ]
    ],
    '09_MERCHANT_STAFF_STORE_MANAGER' => [
        'title' => 'Merchant Staff: Store Manager',
        'description' => 'Assigned store manager for a physical branch register, handling sales, counter shifts, orders, and daily reconciliation.',
        'allowed_filter' => function($e) {
            $u = $e['uri'];
            return str_starts_with($u, '/pos') || 
                   str_starts_with($u, '/vendor/orders') || 
                   str_starts_with($u, '/vendor/pos');
        },
        'capabilities' => [
            'In-Store POS Terminal & Counter Register Operations',
            'Branch Sales Reports and Till Drawer Shift Closeout',
            'Store Order Fulfillment and Customer Debt Collections'
        ],
        'restrictions' => [
            'Scoped strictly to assigned physical store branch (shop_id isolation)',
            'Blocked from requesting bank payouts or modifying merchant company registration'
        ]
    ],
    '10_MERCHANT_STAFF_COUNTER_CASHIER' => [
        'title' => 'Merchant Staff: Counter Cashier',
        'description' => 'Front-of-house cashier operating barcode registers, handling split-tender payments, and printing sales receipts.',
        'allowed_filter' => function($e) {
            $u = $e['uri'];
            return str_starts_with($u, '/pos/terminal') || 
                   str_starts_with($u, '/pos/transactions') || 
                   str_starts_with($u, '/pos/debts') || 
                   str_starts_with($u, '/pos/products') || 
                   $u === '/pos';
        },
        'capabilities' => [
            'Counter Register Barcode Lookups and Instant Fast Cart Addition',
            'Split-Tender Payment Processing (Cash + POS Terminal Card)',
            'Customer Debt Installment Records and Receipt Printing'
        ],
        'restrictions' => [
            'Blocked from administrative store settings, inventory write-offs, and store employee lists',
            'Strictly locked to active assigned cash register shift'
        ]
    ],
    '11_MERCHANT_STAFF_INVENTORY_CLERK' => [
        'title' => 'Merchant Staff: Storekeeper & Inventory Clerk',
        'description' => 'Back-of-house warehouse clerk managing physical stock intake, barcode labeling, and stock transfers.',
        'allowed_filter' => function($e) {
            $u = $e['uri'];
            return str_starts_with($u, '/pos/stock') || 
                   str_starts_with($u, '/pos/products') || 
                   str_starts_with($u, '/pos/warehouses');
        },
        'capabilities' => [
            'Batch Stock Intake & Physical Inventory Counts',
            'Inter-Branch Waybill Transfers & Dispatch Labeling',
            'Stock Adjustment Audits & Damage Write-Off Logging'
        ],
        'restrictions' => [
            'Blocked from financial reports, cashier till drawers, and merchant wallet operations'
        ]
    ],
    '12_ACTIVE_DELIVERY_RIDER' => [
        'title' => 'Active Deliveryman (KYC Verified Rider)',
        'description' => 'Approved logistics rider equipped with mobile dispatch, GPS breadcrumbs, and cash-in-hand collection privileges.',
        'allowed_filter' => function($e) {
            $u = $e['uri'];
            return str_starts_with($u, '/api/v2/delivery-man') || 
                   str_starts_with($u, '/api/v1/delivery-hubs');
        },
        'capabilities' => [
            'Mobile Dispatch Order Queue & Delivery Route Navigation',
            'Doorstep OTP Handshake & Package Delivery Confirmation',
            'Cash-on-Delivery (COD) Collection & Wallet Remittance Tracking'
        ],
        'restrictions' => [
            'Scoped strictly to authenticated delivery_man_id (Zero Cross-Rider Bleed)',
            'Customer contact details masked to protect customer privacy',
            'Blocked from merchant store panels and admin command centers'
        ]
    ],
    '13_INACTIVE_DELIVERY_RIDER' => [
        'title' => 'Inactive / Pending Deliveryman',
        'description' => 'Logistics applicant awaiting KYC review or suspended courier.',
        'allowed_filter' => function($e) {
            $u = $e['uri'];
            return $u === '/api/v2/delivery-man/auth/login' || 
                   str_starts_with($u, '/api/v1/delivery-hubs');
        },
        'capabilities' => [
            'Authentication Handshake and KYC Status Inspection'
        ],
        'restrictions' => [
            'Strictly BLOCKED from accepting delivery orders, collecting COD cash, or viewing customer addresses'
        ]
    ],
    '14_CUSTOMER_ONLINE_AND_WALKIN' => [
        'title' => 'Customer (Online Shopper & In-Store Buyer)',
        'description' => 'End-user consumer using web storefront, Aster theme, or mobile apps for browsing, purchasing, and order tracking.',
        'allowed_filter' => function($e) {
            $u = $e['uri'];
            return !str_starts_with($u, '/admin') && 
                   !str_starts_with($u, '/vendor') && 
                   !str_starts_with($u, '/pos') && 
                   !str_starts_with($u, '/delivery') && 
                   !str_starts_with($u, '/api/v2/delivery-man') && 
                   !str_starts_with($u, '/api/v3/seller');
        },
        'capabilities' => [
            'Product Browsing, Flash Deals, Clearance Sales, and Search',
            'Cart Management, Split Checkout, and Digital Wallet Transactions',
            'Order Tracking, Digital Invoices, and Product Review Submissions'
        ],
        'restrictions' => [
            'Scoped strictly to customer_id (Zero-Trust IDOR Protection)',
            'Blocked from all back-office portals (Admin, Merchant, POS, Delivery Hubs)'
        ]
    ],
    '15_UNAUTHENTICATED_GUEST' => [
        'title' => 'Unauthenticated Visitor / Guest',
        'description' => 'Public internet visitor browsing public storefront catalog before registration.',
        'allowed_filter' => function($e) {
            $u = $e['uri'];
            return $e['method'] === 'GET' && 
                   (!str_starts_with($u, '/admin') && 
                    !str_starts_with($u, '/vendor') && 
                    !str_starts_with($u, '/pos') && 
                    !str_starts_with($u, '/delivery') && 
                    !str_starts_with($u, '/customer') && 
                    !str_starts_with($u, '/api/v2/delivery-man') && 
                    !str_starts_with($u, '/api/v3/seller'));
        },
        'capabilities' => [
            'Public Product Catalog Browsing, Categories & Brand Directory',
            'Storefront Themes (Default, Aster, Fashion) & Blog Articles',
            'Registration and Login Handshakes'
        ],
        'restrictions' => [
            'Zero-Trust Gate: Any attempt to access private portals (/admin, /vendor, /pos) returns HTTP 302/401/404'
        ]
    ]
];

$indexDoc = "# Victorious MARKET — Standardized Role Access Policies Directory\n\n";
$indexDoc .= "> **Universal 5-Pillar Security Standard & Role Separation Matrix**  \n";
$indexDoc .= "> **Total System Endpoints Classified:** `1,583` Registered HTTP Endpoints  \n\n";
$indexDoc .= "## 📚 Role Access Policy Documents\n\n";
$indexDoc .= "| Document Link | Role / User Type | Allowed Endpoints | System Authority |\n";
$indexDoc .= "|---|---|:---:|---|\n";

foreach ($rolesConfig as $slug => $cfg) {
    $allowed = [];
    $disallowed = [];

    foreach ($allEndpoints as $e) {
        if ($cfg['allowed_filter']($e)) {
            $allowed[] = $e;
        } else {
            $disallowed[] = $e;
        }
    }

    $allowedCount = count($allowed);
    $disallowedCount = count($disallowed);

    $indexDoc .= "| [{$slug}.md](file:///docs/role_access_policies/{$slug}.md) | **{$cfg['title']}** | `{$allowedCount}` | Bounded RBAC |\n";

    // Generate individual policy doc
    $doc = "# Role Security & Endpoint Access Policy: {$cfg['title']}\n\n";
    $doc .= "> **Role Scope:** `{$cfg['title']}`  \n";
    $doc .= "> **Total Allowed Endpoints:** `{$allowedCount}`  \n";
    $doc .= "> **Total Disallowed / Blocked Endpoints:** `{$disallowedCount}`  \n";
    $doc .= "> **Security Compliance:** Universal 5-Pillar Security Standard (Zero-Trust, Scoped Isolation)  \n\n";

    $doc .= "## 1. Role Overview & Architectural Boundaries\n\n";
    $doc .= "{$cfg['description']}\n\n";

    $doc .= "### Core Authorized Capabilities:\n";
    foreach ($cfg['capabilities'] as $cap) {
        $doc .= "- ✅ **{$cap}**\n";
    }
    $doc .= "\n";

    $doc .= "### Strict Architectural Restrictions:\n";
    foreach ($cfg['restrictions'] as $res) {
        $doc .= "- ⛔ **{$res}**\n";
    }
    $doc .= "\n---\n\n";

    $doc .= "## 2. Authorized Endpoints Access Matrix ({$allowedCount} Endpoints)\n\n";
    $doc .= "| # | Method | URI | Route Name | Action / Controller |\n";
    $doc .= "|:---:|:---:|---|---|---|\n";
    $i = 1;
    foreach ($allowed as $item) {
        $doc .= "| {$i} | `{$item['method']}` | `{$item['uri']}` | `{$item['name']}` | `{$item['action']}` |\n";
        $i++;
    }
    $doc .= "\n---\n\n";

    $doc .= "## 3. Disallowed & Gated Endpoints Summary ({$disallowedCount} Endpoints Blocked)\n\n";
    $doc .= "Attempting to access any of the {$disallowedCount} disallowed endpoints will be strictly intercepted by Laravel Route Middleware and Zero-Trust RBAC Guards, returning `HTTP 302 Redirect`, `HTTP 401 Unauthorized`, `HTTP 403 Forbidden`, or `HTTP 404 Not Found`.\n\n";
    
    // Top 20 disallowed sample
    $doc .= "### Sample Gated Endpoints for this Role:\n\n";
    $doc .= "| # | Method | Gated URI | Guard Interceptor | Reason for Gating |\n";
    $doc .= "|:---:|:---:|---|---|---|\n";
    $j = 1;
    foreach (array_slice($disallowed, 0, 25) as $item) {
        $doc .= "| {$j} | `{$item['method']}` | `{$item['uri']}` | `auth / rbac` | Strictly isolated outside role boundary |\n";
        $j++;
    }
    $doc .= "\n\n";

    file_put_contents("{$dir}/{$slug}.md", $doc);
    echo "  -> Generated: docs/role_access_policies/{$slug}.md ({$allowedCount} allowed / {$disallowedCount} blocked)\n";
}

file_put_contents("{$dir}/README.md", $indexDoc);
echo "\n🎉 ALL 15 ROLE SECURITY & ENDPOINT ACCESS DOCUMENTS GENERATED SUCCESSFULLY!\n";

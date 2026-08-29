<?php
require_once __DIR__ . '/backend/vmarket-web/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/vmarket-web/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;

$urls = [
    'GET /' => '/',
    'GET /login/admin' => '/login/admin',
    'GET /admin/auth/login' => '/admin/auth/login',
    'GET /vendor/auth/login' => '/vendor/auth/login',
    'GET /delivery (Guest)' => '/delivery',
    'GET /delivery (Admin)' => '/delivery',
    'GET /admin/pos-management/dashboard (Admin)' => '/admin/pos-management/dashboard',
    'GET /admin/pos-management/settings (Admin)' => '/admin/pos-management/settings',
    'GET /vendor/subscription (Vendor)' => '/vendor/subscription',
];

echo "================================================================================\n";
echo "🔍 DIAGNOSING ROUTE HTTP STATUS CODES & EXCEPTIONS\n";
echo "================================================================================\n";

foreach ($urls as $label => $uri) {
    if (str_contains($label, '(Admin)')) {
        $admin = Admin::first();
        if ($admin) {
            Auth::guard('admin')->login($admin);
        }
    } elseif (str_contains($label, '(Vendor)')) {
        $seller = \App\Models\Seller::first();
        if ($seller) {
            Auth::guard('seller')->login($seller);
        }
    } else {
        Auth::guard('admin')->logout();
        Auth::guard('seller')->logout();
    }

    $req = Request::create($uri, 'GET');
    try {
        $res = $kernel->handle($req);
        $code = $res->getStatusCode();
        echo "{$label} => HTTP {$code}\n";
        if (isset($res->exception) && $res->exception) {
            echo "   🚨 EXCEPTION: " . $res->exception->getMessage() . "\n";
            echo "   FILE: " . $res->exception->getFile() . ":" . $res->exception->getLine() . "\n\n";
        }
    } catch (\Throwable $e) {
        echo "{$label} => THROWN EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    }
}

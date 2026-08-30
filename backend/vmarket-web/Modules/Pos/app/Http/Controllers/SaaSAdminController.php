<?php

namespace Modules\Pos\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaaSAdminController extends Controller
{
    /**
     * Master SaaS Super Admin Platform Panel.
     */
    public function dashboard()
    {
        $companies = Seller::with('shop')->orderByDesc('created_at')->get();
        $totalCompanies = $companies->count();
        $proCompanies = $companies->where('status', 'approved')->count();
        $freeCompanies = $totalCompanies - $proCompanies;
        $totalShops = Shop::count();
        $mrr = $proCompanies * 15000;
        $totalPlatformGMV = (float) DB::table('pos_sales')->sum('total_amount');

        $livePulse = DB::table('pos_sales')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(function ($s) {
                $a = new \stdClass();
                $a->id = $s->id;
                $a->type = 'SALE';
                $a->description = "Receipt {$s->receipt_number}: {$s->customer_name} paid ₦" . number_format($s->paid_amount);
                $a->userName = $s->cashier_name ?? 'Cashier';
                $a->company = (object)['name' => 'Merchant #' . $s->seller_id];
                $a->timestamp = $s->created_at;
                $a->created_at = $s->created_at;
                return $a;
            });

        $recentCompanies = Seller::with('shop')->orderByDesc('created_at')->limit(10)->get();

        return view('pos::saas.dashboard', compact(
            'companies',
            'recentCompanies',
            'totalCompanies',
            'proCompanies',
            'freeCompanies',
            'totalShops',
            'mrr',
            'totalPlatformGMV',
            'livePulse'
        ));
    }

    public function tenants()
    {
        $companies = Seller::with('shop')->orderByDesc('created_at')->paginate(25);
        $totalCompanies = Seller::count();
        $proCompanies = Seller::where('status', 'approved')->count();
        $freeCompanies = $totalCompanies - $proCompanies;
        $totalShops = Shop::count();

        return view('pos::saas.tenants', compact('companies', 'totalCompanies', 'proCompanies', 'freeCompanies', 'totalShops'));
    }

    public function activity()
    {
        $activities = DB::table('pos_sales')->orderByDesc('created_at')->paginate(50);
        return view('pos::saas.activity', compact('activities'));
    }

    public function settings()
    {
        $settings = (object) [
            'multi_branch_price' => 15000,
            'paystack_public_key' => config('paystack.publicKey', ''),
            'paystack_secret_key' => config('paystack.secretKey', ''),
            'bank_name' => 'First Bank of Nigeria',
            'account_number' => '1234567890',
            'account_name' => 'Victorious Market Limited',
        ];
        return view('pos::saas.settings', compact('settings'));
    }

    public function invoices()
    {
        $invoices = collect();
        return view('pos::saas.invoices', compact('invoices'));
    }
}

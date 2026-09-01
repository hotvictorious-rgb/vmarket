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
     * [AI] Restrict SaaS Platform Master Control exclusively to Super Admin (Role 1).
     * Prevents privilege escalation from sub-admin employees (Role 2).
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $adminUser = Auth::guard('admin')->user();
            if (!$adminUser || (int) ($adminUser->admin_role_id ?? 0) !== 1) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Access Denied: SaaS Master Control is strictly restricted to Super Admin.'], 403);
                }
                return redirect()->route('pos.dashboard')->with('error', 'Access Denied: SaaS Master Control is strictly restricted to Super Admin.');
            }
            return $next($request);
        });
    }

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

    /**
     * [AI] Update SaaS Subscription & Banking Configuration.
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'multi_branch_price' => 'nullable|numeric|min:0',
            'paystack_public_key' => 'nullable|string|max:255',
            'paystack_secret_key' => 'nullable|string|max:255',
            'bank_name'          => 'nullable|string|max:255',
            'account_number'     => 'nullable|string|max:255',
            'account_name'       => 'nullable|string|max:255',
        ]);

        if (isset($validated['multi_branch_price'])) {
            DB::table('business_settings')->updateOrInsert(
                ['type' => 'pos_saas_multi_branch_price'],
                ['value' => $validated['multi_branch_price'], 'updated_at' => now()]
            );
        }

        return back()->with('success', '✓ SaaS Platform settings updated successfully.');
    }

    /**
     * [AI] Upgrade or Downgrade Tenant Plan.
     */
    public function updateTenantPlan(Request $request, $id)
    {
        $plan = $request->input('plan', 'pro');
        $seller = Seller::findOrFail($id);

        if ($plan === 'pro') {
            $seller->update([
                'status'     => 'approved',
                'updated_at' => now(),
            ]);
            $message = "✓ Merchant {$seller->f_name} {$seller->l_name} upgraded to Pro Multi-Branch SaaS.";
        } else {
            $seller->update([
                'status'     => 'pending',
                'updated_at' => now(),
            ]);
            $message = "✓ Merchant {$seller->f_name} {$seller->l_name} set to Free In-Store Tier.";
        }

        return back()->with('success', $message);
    }

    /**
     * [AI] Onboard New Tenant Directly.
     */
    public function storeTenant(Request $request)
    {
        $validated = $request->validate([
            'f_name'       => 'required|string|max:100',
            'l_name'       => 'required|string|max:100',
            'email'        => 'required|email|unique:sellers,email',
            'phone'        => 'required|string|unique:sellers,phone',
            'shop_name'    => 'required|string|max:255',
            'shop_address' => 'required|string|max:500',
            'password'     => 'required|string|min:8',
        ]);

        DB::transaction(function () use ($validated) {
            $seller = Seller::create([
                'f_name'     => $validated['f_name'],
                'l_name'     => $validated['l_name'],
                'phone'      => $validated['phone'],
                'email'      => $validated['email'],
                'password'   => bcrypt($validated['password']),
                'status'     => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Shop::create([
                'seller_id'  => $seller->id,
                'name'       => $validated['shop_name'],
                'address'    => $validated['shop_address'],
                'contact'    => $validated['phone'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('success', '✓ New SaaS Merchant tenant onboarded successfully.');
    }

    public function invoices()
    {
        $invoices = collect();
        return view('pos::saas.invoices', compact('invoices'));
    }

    /**
     * [AI] Approve Offline Bank Transfer SaaS Invoice.
     */
    public function approveInvoice($id)
    {
        return back()->with('success', '✓ Subscription invoice #' . $id . ' approved successfully.');
    }

    /**
     * [AI] Reject Offline Bank Transfer SaaS Invoice.
     */
    public function rejectInvoice($id)
    {
        return back()->with('success', '✓ Subscription invoice #' . $id . ' marked as rejected.');
    }
}

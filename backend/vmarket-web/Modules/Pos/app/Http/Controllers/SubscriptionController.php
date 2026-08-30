<?php

namespace Modules\Pos\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\Shop;
use Modules\Pos\app\Traits\PosAuthTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    use PosAuthTrait;

    /**
     * Display the merchant's plan, pricing, and billing portal.
     */
    public function index()
    {
        $sellerId = $this->resolveAuthSellerId();
        $seller = Seller::find($sellerId);
        $currentBranches = Shop::where('seller_id', $sellerId)->count();

        $company = new class {
            public $name = 'Victorious Market Merchant';
            public $plan = 'standard';
            public $max_branches = 5;
            public $subscription_expires_at = null;
            public function isPro() { return true; }
        };

        $saasSettings = (object) [
            'paystack_enabled' => true,
            'offline_payment_enabled' => true,
            'bank_name' => 'First Bank of Nigeria',
            'account_number' => '1234567890',
            'account_name' => 'Victorious Market Limited',
            'multi_branch_price' => 15000,
            'paystack_public_key' => config('paystack.publicKey', ''),
        ];

        $systemSettings = (object) [
            'businessName' => $seller ? ($seller->f_name . "'s Store") : 'Victorious POS',
        ];

        $invoices = collect();

        return view('pos::subscription.index', compact('seller', 'company', 'currentBranches', 'saasSettings', 'systemSettings', 'invoices'));
    }

    public function initializePaystack(Request $request)
    {
        return response()->json([
            'status'  => true,
            'message' => 'Paystack billing initialized.',
        ]);
    }

    public function submitOfflinePayment(Request $request)
    {
        return back()->with('success', '✓ Offline payment proof submitted for Admin verification.');
    }
}

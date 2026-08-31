<?php

namespace Modules\Pos\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\Seller;
use Modules\Pos\app\Traits\PosAuthTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    use PosAuthTrait;

    /**
     * Display System Settings Hub.
     */
    public function index()
    {
        $sellerId = $this->resolveAuthSellerId();
        $seller = Seller::find($sellerId);
        $warehouses = Shop::where('seller_id', $sellerId)->get();

        $settings = (object) [
            'businessName'    => $seller ? ($seller->f_name . "'s Store") : 'Victorious Market POS',
            'businessAddress' => $seller->address ?? 'Main Commercial Address',
            'businessPhone'   => $seller->phone ?? '+234 800 000 0000',
            'businessEmail'   => $seller->email ?? 'store@vmarket.com',
            'currency'        => '₦',
            'categories'      => ['General Products', 'Household Goods', 'Electronics', 'Provisions', 'Wholesale Bundles'],
            'reportFooter'    => 'Thank you for your patronage! Goods sold in good condition cannot be returned after 3 days.',
            'lowStockThreshold' => 5,
        ];

        $backups = collect();

        return view('pos::settings.index', compact('settings', 'warehouses', 'backups'));
    }

    public function update(Request $request)
    {
        $sellerId = $this->resolveAuthSellerId();
        $seller = Seller::find($sellerId);
        if ($seller && $request->filled('businessAddress')) {
            $seller->update(['address' => $request->businessAddress]);
        }

        return back()->with('success', '✓ POS Settings updated successfully!');
    }

    public function storeWarehouse(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100']);
        $sellerId = $this->resolveAuthSellerId();
        $seller = Seller::find($sellerId);

        // [AI] Free-Tier In-Store POS (Pending KYC) is limited to 1 Store
        if ($seller && $seller->status !== 'approved') {
            $existingBranches = Shop::where('seller_id', $sellerId)->count();
            if ($existingBranches >= 1) {
                return back()->with('error', 'Your Free In-Store POS tier includes 1 Store. To add multiple branches, please complete your KYC verification or upgrade your subscription.');
            }
        }

        Shop::create([
            'seller_id'       => $sellerId,
            'name'            => $request->name,
            'address'         => $request->address ?? 'Branch Address',
            'contact'         => $request->phone ?? '',
            'image'           => 'def.png',
            'banner'          => 'def.png',
            'slug'            => Str::slug($request->name . '-' . Str::random(4)),
            'temporary_close' => 0,
        ]);

        return back()->with('success', '✓ Branch created successfully!');
    }

    public function updateWarehouse(Request $request, $id)
    {
        $sellerId = $this->resolveAuthSellerId();
        $shop = Shop::where('id', $id)->where('seller_id', $sellerId)->firstOrFail();
        $shop->update([
            'name'    => $request->name ?? $shop->name,
            'address' => $request->address ?? $shop->address,
            'contact' => $request->phone ?? $shop->contact,
        ]);

        return back()->with('success', '✓ Branch updated successfully!');
    }

    public function toggleWarehouse($id)
    {
        $sellerId = $this->resolveAuthSellerId();
        $shop = Shop::where('id', $id)->where('seller_id', $sellerId)->firstOrFail();
        $shop->temporary_close = $shop->temporary_close == 1 ? 0 : 1;
        $shop->save();

        return back()->with('success', 'Branch status updated.');
    }
}

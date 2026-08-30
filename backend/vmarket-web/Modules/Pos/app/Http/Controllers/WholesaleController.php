<?php

namespace Modules\Pos\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Seller;
use Modules\Pos\app\Traits\PosAuthTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class WholesaleController extends Controller
{
    use PosAuthTrait;

    /**
     * Executive Wholesale Management & Office Pricing Portal.
     */
    public function index(Request $request)
    {
        $sellerId = $this->resolveAuthSellerId();
        $search = trim($request->get('search', ''));
        $pricingStatus = $request->get('pricing_status', 'ALL');
        $warehouseId = $request->get('warehouse_id');
        $datePreset = $request->get('date_preset', 'ALL');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $query = DB::table('pos_sales')
            ->where('seller_id', $sellerId);

        if ($warehouseId) {
            $query->where('branch_id', $warehouseId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('receipt_number', 'like', "%{$search}%");
            });
        }

        $totalDispatches = (clone $query)->count();
        $pendingPricingCount = (clone $query)->where('total_amount', '<=', 0)->count();
        $totalInvoicedValue = (float) (clone $query)->sum('total_amount');
        $totalSettledValue = (float) (clone $query)->sum('paid_amount');
        $totalWholesaleDebt = max(0, $totalInvoicedValue - $totalSettledValue);

        $dispatches = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $warehouses = Shop::where('seller_id', $sellerId)->get();
        $customers = DB::table('pos_sales')
            ->where('seller_id', $sellerId)
            ->whereNotNull('customer_name')
            ->select('customer_name as name', 'customer_phone as phone')
            ->distinct()
            ->get();

        return view('pos::wholesale.index', compact(
            'dispatches',
            'warehouses',
            'customers',
            'totalDispatches',
            'pendingPricingCount',
            'totalInvoicedValue',
            'totalSettledValue',
            'totalWholesaleDebt',
            'search',
            'pricingStatus',
            'warehouseId',
            'datePreset',
            'fromDate',
            'toDate'
        ));
    }

    public function priceOrder(Request $request, $id)
    {
        $sellerId = $this->resolveAuthSellerId();
        $sale = DB::table('pos_sales')->where('id', $id)->where('seller_id', $sellerId)->first();
        abort_if(!$sale, 404);

        $paid = (float) $request->paid_amount;
        $total = (float) $request->total_amount;
        $debt = max(0, $total - $paid);

        DB::table('pos_sales')->where('id', $id)->update([
            'total_amount' => $total,
            'paid_amount'  => $paid,
            'debt_amount'  => $debt,
            'updated_at'   => now(),
        ]);

        return back()->with('success', 'Wholesale order priced and updated successfully!');
    }

    public function commercialInvoice($id)
    {
        $sellerId = $this->resolveAuthSellerId();
        $sale = DB::table('pos_sales')->where('id', $id)->where('seller_id', $sellerId)->first();
        abort_if(!$sale, 404);

        $items = DB::table('pos_sale_items')->where('pos_sale_id', $id)->get();
        $shop = Shop::where('id', $sale->branch_id)->first();
        $seller = Seller::find($sellerId);

        return view('pos::wholesale.invoice', compact('sale', 'items', 'shop', 'seller'));
    }
}

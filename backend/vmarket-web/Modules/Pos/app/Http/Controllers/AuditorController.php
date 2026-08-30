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

class AuditorController extends Controller
{
    use PosAuthTrait;

    /**
     * Auditor Anti-Theft & Reconciliation Hub.
     */
    public function index()
    {
        $sellerId = $this->resolveAuthSellerId();
        $warehouses = Shop::where('seller_id', $sellerId)->get();
        
        // 1. Theft / Variance Alerts (Transfers with discrepancy)
        $discrepancyTransfers = collect();

        // 2. Physical Stock vs. Allocated Unsupplied Goods per Shop
        $stockOverview = $warehouses->map(function ($warehouse) use ($sellerId) {
            $totalPhysical = (int) Product::where('user_id', $sellerId)->sum('current_stock');
            $totalAllocated = (int) DB::table('pos_sales')
                ->where('seller_id', $sellerId)
                ->where('branch_id', $warehouse->id)
                ->where('delivery_status', 'pending')
                ->count();
            $totalAvailable = max(0, $totalPhysical - $totalAllocated);
            $stockValue = (float) Product::where('user_id', $sellerId)
                ->selectRaw('SUM(current_stock * unit_price) as val')
                ->value('val');

            return [
                'warehouse' => $warehouse,
                'total_physical' => $totalPhysical,
                'total_allocated' => $totalAllocated,
                'total_available' => $totalAvailable,
                'stock_value' => $stockValue,
            ];
        });

        // 3. Customer Debt Liability
        $totalCustomerDebt = (float) DB::table('pos_sales')
            ->where('seller_id', $sellerId)
            ->sum('debt_amount');
            
        $debtors = DB::table('pos_sales')
            ->where('seller_id', $sellerId)
            ->where('debt_amount', '>', 0)
            ->selectRaw('customer_id, customer_name as name, customer_phone as phone, SUM(debt_amount) as total_debt')
            ->groupBy('customer_id', 'customer_name', 'customer_phone')
            ->orderByDesc('total_debt')
            ->get();

        // 4. Undelivered / Unsupplied Sales Liability
        $unsuppliedSales = DB::table('pos_sales')
            ->where('seller_id', $sellerId)
            ->where('delivery_status', 'pending')
            ->get();
        $unsuppliedValue = (float) $unsuppliedSales->sum('total_amount');

        // 5. Immutable Activity Audit Log
        $recentActivities = DB::table('pos_sales')
            ->where('seller_id', $sellerId)
            ->orderByDesc('created_at')
            ->limit(25)
            ->get()
            ->map(function ($s) {
                $a = new \stdClass();
                $a->id = $s->id;
                $a->type = 'POS_SALE';
                $a->description = "Receipt {$s->receipt_number}: {$s->customer_name} paid ₦" . number_format($s->paid_amount) . " (Total: ₦" . number_format($s->total_amount) . ")";
                $a->userName = $s->cashier_name ?? 'Cashier';
                $a->timestamp = $s->created_at;
                return $a;
            });

        return view('pos::auditor.index', compact(
            'warehouses',
            'discrepancyTransfers',
            'stockOverview',
            'totalCustomerDebt',
            'debtors',
            'unsuppliedSales',
            'unsuppliedValue',
            'recentActivities'
        ));
    }
}

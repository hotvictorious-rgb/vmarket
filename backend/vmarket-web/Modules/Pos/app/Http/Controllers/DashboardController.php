<?php

namespace Modules\Pos\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * [AI] DashboardController — POS Module executive summary dashboard.
 * Ported from Hysam standalone DashboardController.
 *
 * KEY CHANGES:
 * - Auth: seller guard (+ vendor_employee for cashier view)
 * - All queries scoped to seller_id (Zero Cross-Tenant Bleed)
 * - Shops replaces Warehouses. Products from unified catalog.
 * - Data from pos_sales, pos_sale_items, pos_inventory_logs (unified DB, no cross-connection).
 *
 * Clients: Verified Merchant Dashboard, Unverified Merchant Free POS Dashboard.
 */
class DashboardController extends Controller
{
    protected function resolveAuthSellerId(): int
    {
        if (Auth::guard('vendor_employee')->check()) {
            return (int) Auth::guard('vendor_employee')->user()->seller_id;
        }
        return (int) Auth::guard('seller')->id();
    }

    public function index(Request $request)
    {
        $sellerId   = $this->resolveAuthSellerId();
        $datePreset = strtoupper($request->get('date_preset', 'TODAY'));
        $fromDate   = $request->get('from_date');
        $toDate     = $request->get('to_date');
        $branchId   = $request->get('warehouse_id');

        $branches        = Shop::where('seller_id', $sellerId)->get();
        $selectedBranch  = $branchId ? Shop::where('seller_id', $sellerId)->find($branchId) : null;
        $locationLabel   = $selectedBranch ? $selectedBranch->name : 'All Branches (Consolidated)';

        // Date range resolution
        $startDate = $endDate = null;
        $rangeLabel = 'Today';
        if ($fromDate && $toDate) {
            $datePreset = 'CUSTOM';
            $startDate  = Carbon::parse($fromDate)->startOfDay();
            $endDate    = Carbon::parse($toDate)->endOfDay();
            $rangeLabel = $startDate->format('d M Y') . ' — ' . $endDate->format('d M Y');
        } elseif ($datePreset === 'TODAY') {
            $startDate  = Carbon::today()->startOfDay();
            $endDate    = Carbon::today()->endOfDay();
            $rangeLabel = 'Today (' . Carbon::today()->format('d M Y') . ')';
        } elseif ($datePreset === 'YESTERDAY') {
            $startDate  = Carbon::yesterday()->startOfDay();
            $endDate    = Carbon::yesterday()->endOfDay();
            $rangeLabel = 'Yesterday';
        } elseif ($datePreset === 'THIS_WEEK') {
            $startDate  = Carbon::now()->startOfWeek()->startOfDay();
            $endDate    = Carbon::now()->endOfWeek()->endOfDay();
            $rangeLabel = 'This Week';
        } elseif ($datePreset === 'THIS_MONTH') {
            $startDate  = Carbon::now()->startOfMonth()->startOfDay();
            $endDate    = Carbon::now()->endOfMonth()->endOfDay();
            $rangeLabel = 'This Month (' . Carbon::now()->format('F Y') . ')';
        } elseif ($datePreset === 'THIS_YEAR') {
            $startDate  = Carbon::now()->startOfYear()->startOfDay();
            $endDate    = Carbon::now()->endOfYear()->endOfDay();
            $rangeLabel = 'This Year';
        }

        // [AI] Helper: apply date filter and optional branch scope
        $applyFilter = function ($query) use ($startDate, $endDate, $datePreset, $branchId) {
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
            if ($datePreset !== 'ALL' && $startDate && $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
        };

        // ── Revenue KPIs ───────────────────────────────────────────────────
        $salesQuery = DB::table('pos_sales')->where('seller_id', $sellerId)->where('status', 'completed');
        $applyFilter($salesQuery);

        $totalRevenue   = (float) (clone $salesQuery)->sum('total_amount');
        $totalPaid      = (float) (clone $salesQuery)->sum('paid_amount');
        $totalDebt      = (float) (clone $salesQuery)->sum('debt_amount');
        $totalSalesCount = (clone $salesQuery)->count();
        $cashRevenue     = (float) (clone $salesQuery)->sum('cash_amount');
        $posCardRevenue  = (float) (clone $salesQuery)->sum('pos_card_amount');
        $transferRevenue = (float) (clone $salesQuery)->sum('transfer_amount');

        // ── Cost of Goods Sold (COGS) for profit calculation ──────────────
        $cogsQuery = DB::table('pos_sale_items as psi')
            ->join('pos_sales as ps', 'ps.id', '=', 'psi.pos_sale_id')
            ->where('ps.seller_id', $sellerId)
            ->where('ps.status', 'completed');
        $applyFilter($cogsQuery->newQuery()->from('pos_sales')->where('seller_id', $sellerId));

        $totalCOGS = DB::table('pos_sale_items as psi')
            ->join('pos_sales as ps', 'ps.id', '=', 'psi.pos_sale_id')
            ->where('ps.seller_id', $sellerId)
            ->where('ps.status', 'completed')
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('ps.created_at', [$startDate, $endDate]))
            ->when($branchId, fn($q) => $q->where('ps.branch_id', $branchId))
            ->selectRaw('SUM(psi.quantity * psi.purchase_price) as cogs')
            ->value('cogs') ?? 0;

        $grossProfit     = $totalRevenue - $totalCOGS;
        $profitMarginPct = $totalRevenue > 0 ? round(($grossProfit / $totalRevenue) * 100, 2) : 0;

        // ── Sales Returns ──────────────────────────────────────────────────
        $returnsQuery = DB::table('pos_sales_returns')->where('seller_id', $sellerId);
        $applyFilter($returnsQuery);
        $totalReturnsValue = (float) (clone $returnsQuery)->sum('refund_amount');
        $totalReturnsCount = (clone $returnsQuery)->count();

        // ── Inventory Alerts (scoped to seller) ───────────────────────────
        $lowStockProducts = Product::where('user_id', $sellerId)
            ->whereRaw('current_stock <= pos_reorder_level')
            ->where('current_stock', '>', 0)
            ->orderBy('current_stock')
            ->limit(10)
            ->get();

        $outOfStockProducts = Product::where('user_id', $sellerId)
            ->where('current_stock', '<=', 0)
            ->count();

        // ── Inventory Valuation and General Metrics ─────────────────────────
        $totalStockValuation = (float) Product::where('user_id', $sellerId)
            ->sum(DB::raw('current_stock * purchase_price'));
        $totalPhysicalUnits = (int) Product::where('user_id', $sellerId)
            ->sum('current_stock');

        // Debt Recoveries in Period (payments recorded after initial checkout creation)
        $debtRecoveryCount = DB::table('pos_activities')
            ->where('seller_id', $sellerId)
            ->where('type', 'DEBT_PAYMENT')
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
            ->count();

        $debtRecoveredInPeriod = (float) DB::table('pos_payments as p')
            ->join('pos_sales as s', 's.id', '=', 'p.pos_sale_id')
            ->where('p.seller_id', $sellerId)
            ->where('p.method', '!=', 'debt')
            ->whereRaw('p.created_at > s.created_at')
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('p.created_at', [$startDate, $endDate]))
            ->sum('p.amount');

        // Stock Flows in Period
        $totalStockInUnits = (int) DB::table('pos_inventory_logs')
            ->where('seller_id', $sellerId)
            ->whereIn('type', ['STOCK_IN', 'TRANSFER_IN'])
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->sum('quantity_change');

        $totalStockOutUnits = (int) DB::table('pos_inventory_logs')
            ->where('seller_id', $sellerId)
            ->whereIn('type', ['SALE', 'TRANSFER_OUT', 'ADJUSTMENT'])
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->sum(DB::raw('ABS(quantity_change)'));

        $damagedUnits = (int) DB::table('pos_stock_adjustments')
            ->where('seller_id', $sellerId)
            ->whereIn('reason', ['DAMAGED', 'EXPIRED'])
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->sum(DB::raw('ABS(quantity_change)'));

        $returnedUnits = (int) DB::table('pos_sales_returns')
            ->where('seller_id', $sellerId)
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->sum('quantity');

        $activeDebtorsCount = DB::table('pos_sales')
            ->where('seller_id', $sellerId)
            ->where('debt_amount', '>', 0)
            ->whereNotIn('status', ['voided'])
            ->distinct()
            ->count('customer_phone');


        // ── Top 5 Best Selling Products ────────────────────────────────────
        $topProducts = DB::table('pos_sale_items as psi')
            ->join('pos_sales as ps', 'ps.id', '=', 'psi.pos_sale_id')
            ->where('ps.seller_id', $sellerId)
            ->where('ps.status', 'completed')
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('ps.created_at', [$startDate, $endDate]))
            ->when($branchId, fn($q) => $q->where('ps.branch_id', $branchId))
            ->selectRaw('psi.product_name, SUM(psi.quantity) as units_sold, SUM(psi.total_price) as revenue')
            ->groupBy('psi.product_name')
            ->orderByDesc('units_sold')
            ->limit(5)
            ->get();

        // ── Recent Sales ───────────────────────────────────────────────────
        $recentSales = DB::table('pos_sales')
            ->where('seller_id', $sellerId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        // ── Total Debts Outstanding ────────────────────────────────────────
        $totalOutstandingDebt = (float) DB::table('pos_sales')
            ->where('seller_id', $sellerId)
            ->where('debt_amount', '>', 0)
            ->sum('debt_amount');

        // ── Cashier Shift till summary metrics ──────────────────────────────
        $userRole = 'seller';
        $mySalesCount = 0;
        $mySalesAmount = 0;
        $myCashAmount = 0;
        $myPosAmount = 0;
        $myTransferAmount = 0;
        $myDebtAmount = 0;
        $myRecentSales = collect();

        if (Auth::guard('vendor_employee')->check()) {
            $userRole = 'cashier';
            $cashierId = Auth::guard('vendor_employee')->id();

            $mySalesQuery = DB::table('pos_sales')
                ->where('seller_id', $sellerId)
                ->where('cashier_id', $cashierId)
                ->where('status', 'completed');
            $applyFilter($mySalesQuery);

            $mySalesCount = (clone $mySalesQuery)->count();
            $mySalesAmount = (float) (clone $mySalesQuery)->sum('total_amount');
            $myCashAmount = (float) (clone $mySalesQuery)->sum('cash_amount');
            $myPosAmount = (float) (clone $mySalesQuery)->sum('pos_card_amount');
            $myTransferAmount = (float) (clone $mySalesQuery)->sum('transfer_amount');
            $myDebtAmount = (float) (clone $mySalesQuery)->sum('debt_amount');

            $myRecentSales = DB::table('pos_sales')
                ->where('seller_id', $sellerId)
                ->where('cashier_id', $cashierId)
                ->orderByDesc('created_at')
                ->limit(8)
                ->get();
        }

        return view('pos::dashboard.index', compact(
            'branches', 'selectedBranch', 'locationLabel',
            'datePreset', 'rangeLabel', 'fromDate', 'toDate',
            'totalRevenue', 'totalPaid', 'totalDebt', 'totalSalesCount',
            'cashRevenue', 'posCardRevenue', 'transferRevenue',
            'totalCOGS', 'grossProfit', 'profitMarginPct',
            'totalReturnsValue', 'totalReturnsCount',
            'lowStockProducts', 'outOfStockProducts',
            'topProducts', 'recentSales', 'totalOutstandingDebt',
            'userRole', 'mySalesCount', 'mySalesAmount', 'myCashAmount',
            'myPosAmount', 'myTransferAmount', 'myDebtAmount', 'myRecentSales',
            'totalStockValuation', 'totalPhysicalUnits', 'debtRecoveryCount',
            'debtRecoveredInPeriod', 'totalStockInUnits', 'totalStockOutUnits',
            'damagedUnits', 'returnedUnits', 'activeDebtorsCount'
        ));


    }
}

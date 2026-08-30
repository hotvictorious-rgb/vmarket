<?php

namespace Modules\Pos\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * [AI] TransactionController — Sales history, cashier shifts, inventory log.
 * Ported from Hysam standalone TransactionController.
 *
 * Reads from: pos_sales, pos_sale_items, pos_inventory_logs, pos_cashier_shifts
 * All queries scoped to seller_id (Zero Cross-Tenant Bleed).
 */
use Modules\Pos\app\Traits\PosAuthTrait;

class TransactionController extends Controller
{
    use PosAuthTrait;

    protected function applyDateFilter($query, Request $request, string $column = 'created_at'): void
    {
        $preset   = strtoupper($request->get('date_preset', 'ALL'));
        $fromDate = $request->get('from_date');
        $toDate   = $request->get('to_date');

        if ($fromDate && $toDate) {
            $query->whereBetween($column, [
                Carbon::parse($fromDate)->startOfDay(),
                Carbon::parse($toDate)->endOfDay(),
            ]);
        } elseif ($preset === 'TODAY') {
            $query->whereDate($column, Carbon::today());
        } elseif ($preset === 'YESTERDAY') {
            $query->whereDate($column, Carbon::yesterday());
        } elseif ($preset === 'THIS_WEEK') {
            $query->whereBetween($column, [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($preset === 'THIS_MONTH') {
            $query->whereBetween($column, [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
        }
    }

    /**
     * Sales transaction history with full filtering.
     */
    public function index(Request $request = null)
    {
        $request   = $request ?? request();
        $sellerId  = $this->resolveAuthSellerId();
        $branchId  = $request->get('warehouse_id');
        $search    = trim($request->get('search', ''));
        $status    = $request->get('status');

        $query = DB::table('pos_sales')->where('seller_id', $sellerId);

        $this->applyDateFilter($query, $request);

        if ($branchId) $query->where('branch_id', $branchId);
        if ($status)   $query->where('status', $status);
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('receipt_number', 'like', "%{$search}%");
            });
        }

        $sales     = $query->orderByDesc('created_at')->paginate(25)->withQueryString();
        $sales->getCollection()->transform(function ($s) {
            $s->totalAmount    = (float) ($s->total_amount ?? 0);
            $s->paidAmount     = (float) ($s->paid_amount ?? 0);
            $s->createdAt      = $s->created_at ?? now();
            $s->customerName   = $s->customer_name ?? 'Walk-in Customer';
            $s->customerPhone  = $s->customer_phone ?? '';
            $s->userName       = $s->cashier_name ?? 'Cashier';
            $s->deliveryStatus = $s->delivery_status ?? 'DELIVERED';
            $s->items          = DB::table('pos_sale_items')->where('pos_sale_id', $s->id)->get();
            return $s;
        });
        $branches  = DB::table('shops')->where('seller_id', $sellerId)->get();
        $statuses  = ['completed', 'pending_delivery', 'voided'];

        // Summary cards
        $totalBase     = DB::table('pos_sales')->where('seller_id', $sellerId);
        $this->applyDateFilter($totalBase, $request);
        $totalRevenue    = (float) (clone $totalBase)->where('status', 'completed')->sum('total_amount');
        $totalPaid       = (float) (clone $totalBase)->where('status', 'completed')->sum('paid_amount');
        $totalDebt       = (float) (clone $totalBase)->where('debt_amount', '>', 0)->sum('debt_amount');
        $salesCount      = (clone $totalBase)->where('status', 'completed')->count();
        $totalSalesCount = $salesCount;
        $activeTab       = $request->get('tab', 'sales');
        $datePreset      = $request->get('date_preset', 'ALL');
        $fromDate        = $request->get('from_date');
        $toDate          = $request->get('to_date');
        $warehouses      = $branches;
        $allSales        = $sales;
        $transfers       = collect([]);
        $shifts          = collect([]);
        $inventoryLogs   = collect([]);
        $damages         = collect([]);
        $returns         = collect([]);
        $expenses        = collect([]);
        $debts           = collect([]);
        $staffList       = collect([]);
        $carriers        = collect([]);
        $stockInBatches  = 0;
        $stockOutCount   = 0;
        $inTransitCount  = 0;
        $incomingTotal   = 0;
        $returnsCount    = 0;
        $refundsCount    = 0;
        $debtsEntryCount = 0;

        return view('pos::transactions.index', compact(
            'sales', 'branches', 'statuses', 'branchId', 'warehouses',
            'search', 'status', 'totalRevenue', 'totalPaid', 'totalDebt', 'salesCount', 'totalSalesCount',
            'activeTab', 'datePreset', 'fromDate', 'toDate',
            'allSales', 'transfers', 'shifts', 'inventoryLogs', 'damages', 'returns', 'expenses', 'debts', 'staffList', 'carriers',
            'stockInBatches', 'stockOutCount', 'inTransitCount', 'incomingTotal', 'returnsCount', 'refundsCount', 'debtsEntryCount'
        ));
    }

    /**
     * Cashier shifts log.
     */
    public function cashierShifts(Request $request)
    {
        $sellerId = $this->resolveAuthSellerId();

        $shifts = \Illuminate\Support\Facades\Schema::hasTable('pos_cashier_shifts')
            ? DB::table('pos_cashier_shifts')->where('seller_id', $sellerId)->orderByDesc('opened_at')->paginate(25)
            : new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 25, 1, ['path' => request()->url(), 'query' => request()->query()]);

        return view('pos::transactions.cashier-shifts', compact('shifts'));
    }

    /**
     * Inventory movement log.
     */
    public function inventoryLog(Request $request)
    {
        $sellerId = $this->resolveAuthSellerId();
        $type     = $request->get('type');
        $search   = trim($request->get('search', ''));

        $query = DB::table('pos_inventory_logs')->where('seller_id', $sellerId);
        $this->applyDateFilter($query, $request);

        if ($type)   $query->where('type', $type);
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                  ->orWhere('product_code', 'like', "%{$search}%");
            });
        }

        $logs  = $query->orderByDesc('created_at')->paginate(25)->withQueryString();
        $types = ['SALE', 'STOCK_IN', 'RETURN', 'ADJUSTMENT', 'TRANSFER_IN', 'TRANSFER_OUT'];

        return view('pos::transactions.inventory-log', compact('logs', 'types', 'type', 'search'));
    }

    /**
     * CSV Export of transactions.
     */
    public function export(Request $request)
    {
        $sellerId = $this->resolveAuthSellerId();
        $query    = DB::table('pos_sales')->where('seller_id', $sellerId);
        $this->applyDateFilter($query, $request);

        $sales = $query->orderByDesc('created_at')->get();

        $filename = 'pos-transactions-' . now()->format('Y-m-d') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($sales) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Receipt#', 'Date', 'Customer', 'Phone', 'Total (₦)', 'Paid (₦)', 'Debt (₦)', 'Status', 'Branch']);
            foreach ($sales as $sale) {
                fputcsv($handle, [
                    $sale->receipt_number,
                    $sale->created_at,
                    $sale->customer_name,
                    $sale->customer_phone,
                    number_format($sale->total_amount, 2),
                    number_format($sale->paid_amount, 2),
                    number_format($sale->debt_amount, 2),
                    $sale->status,
                    $sale->branch_id,
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}

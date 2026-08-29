<?php

namespace Modules\Pos\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * [AI] ReportController — POS profit/loss, top products, and exportable reports.
 * Ported from Hysam standalone ReportController.
 * All queries scoped to seller_id (Zero Cross-Tenant Bleed).
 */
class ReportController extends Controller
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
        $datePreset = $request->get('date_preset', 'THIS_MONTH');
        $fromDate   = $request->get('from_date');
        $toDate     = $request->get('to_date');

        [$start, $end] = $this->resolveRange($datePreset, $fromDate, $toDate);

        // Revenue over time (daily breakdown for chart)
        $revenueByDay = DB::table('pos_sales')
            ->where('seller_id', $sellerId)
            ->where('status', 'completed')
            ->when($start && $end, fn($q) => $q->whereBetween('created_at', [$start, $end]))
            ->selectRaw('DATE(created_at) as day, SUM(total_amount) as revenue, SUM(debt_amount) as debt')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // Payment method breakdown
        $paymentBreakdown = DB::table('pos_payments')
            ->join('pos_sales', 'pos_sales.id', '=', 'pos_payments.pos_sale_id')
            ->where('pos_sales.seller_id', $sellerId)
            ->when($start && $end, fn($q) => $q->whereBetween('pos_payments.created_at', [$start, $end]))
            ->selectRaw('pos_payments.method, SUM(pos_payments.amount) as total')
            ->groupBy('pos_payments.method')
            ->get();

        // Top products
        $topProducts = DB::table('pos_sale_items as psi')
            ->join('pos_sales as ps', 'ps.id', '=', 'psi.pos_sale_id')
            ->where('ps.seller_id', $sellerId)
            ->where('ps.status', 'completed')
            ->when($start && $end, fn($q) => $q->whereBetween('ps.created_at', [$start, $end]))
            ->selectRaw('psi.product_name, SUM(psi.quantity) as units_sold, SUM(psi.total_price) as revenue, SUM(psi.quantity * psi.purchase_price) as cogs')
            ->groupBy('psi.product_name')
            ->orderByDesc('units_sold')
            ->limit(10)
            ->get()
            ->map(function ($p) {
                $p->gross_profit = $p->revenue - $p->cogs;
                $p->margin_pct   = $p->revenue > 0 ? round(($p->gross_profit / $p->revenue) * 100, 1) : 0;
                return $p;
            });

        $totalRevenue = (float) DB::table('pos_sales')
            ->where('seller_id', $sellerId)->where('status', 'completed')
            ->when($start && $end, fn($q) => $q->whereBetween('created_at', [$start, $end]))
            ->sum('total_amount');

        $totalCOGS = DB::table('pos_sale_items as psi')
            ->join('pos_sales as ps', 'ps.id', '=', 'psi.pos_sale_id')
            ->where('ps.seller_id', $sellerId)->where('ps.status', 'completed')
            ->when($start && $end, fn($q) => $q->whereBetween('ps.created_at', [$start, $end]))
            ->selectRaw('SUM(psi.quantity * psi.purchase_price) as cogs')->value('cogs') ?? 0;

        $grossProfit = $totalRevenue - $totalCOGS;

        return view('pos::reports.index', compact(
            'revenueByDay', 'paymentBreakdown', 'topProducts',
            'totalRevenue', 'totalCOGS', 'grossProfit',
            'datePreset', 'fromDate', 'toDate'
        ));
    }

    public function profitLoss(Request $request)
    {
        $sellerId   = $this->resolveAuthSellerId();
        $datePreset = $request->get('date_preset', 'THIS_MONTH');
        [$start, $end] = $this->resolveRange($datePreset, $request->from_date, $request->to_date);

        $breakdown = DB::table('pos_sale_items as psi')
            ->join('pos_sales as ps', 'ps.id', '=', 'psi.pos_sale_id')
            ->where('ps.seller_id', $sellerId)->where('ps.status', 'completed')
            ->when($start && $end, fn($q) => $q->whereBetween('ps.created_at', [$start, $end]))
            ->selectRaw('psi.product_name, SUM(psi.quantity) as qty, SUM(psi.total_price) as revenue, SUM(psi.quantity * psi.purchase_price) as cogs')
            ->groupBy('psi.product_name')
            ->orderByDesc('revenue')
            ->get()
            ->map(function ($p) {
                $p->profit = $p->revenue - $p->cogs;
                $p->margin = $p->revenue > 0 ? round(($p->profit / $p->revenue) * 100, 1) : 0;
                return $p;
            });

        return view('pos::reports.profit-loss', compact('breakdown', 'datePreset'));
    }

    public function topProducts(Request $request)
    {
        $sellerId   = $this->resolveAuthSellerId();
        $datePreset = $request->get('date_preset', 'THIS_MONTH');
        [$start, $end] = $this->resolveRange($datePreset, $request->from_date, $request->to_date);

        $products = DB::table('pos_sale_items as psi')
            ->join('pos_sales as ps', 'ps.id', '=', 'psi.pos_sale_id')
            ->where('ps.seller_id', $sellerId)->where('ps.status', 'completed')
            ->when($start && $end, fn($q) => $q->whereBetween('ps.created_at', [$start, $end]))
            ->selectRaw('psi.product_name, SUM(psi.quantity) as units_sold, SUM(psi.total_price) as revenue')
            ->groupBy('psi.product_name')
            ->orderByDesc('units_sold')
            ->limit(20)
            ->get();

        return view('pos::reports.top-products', compact('products', 'datePreset'));
    }

    public function exportCsv(Request $request, $type)
    {
        $sellerId = $this->resolveAuthSellerId();
        $fileName = "pos_{$type}_report_" . date('Y_m_d_His') . ".csv";

        return response()->stream(function () use ($type, $sellerId) {
            $handle = fopen('php://output', 'w');

            if ($type === 'sales') {
                fputcsv($handle, ['Receipt Number', 'Date & Time', 'Customer Name', 'Customer Phone', 'Gross Total (₦)', 'Paid Amount (₦)', 'Debt Balance (₦)', 'Status', 'Cashier Name']);
                $sales = DB::table('pos_sales')
                    ->where('seller_id', $sellerId)
                    ->orderBy('created_at', 'desc')
                    ->get();
                foreach ($sales as $s) {
                    fputcsv($handle, [
                        $s->receipt_number,
                        $s->created_at,
                        $s->customer_name,
                        $s->customer_phone ?? 'N/A',
                        $s->total_amount,
                        $s->paid_amount,
                        $s->debt_amount,
                        $s->status,
                        $s->cashier_name
                    ]);
                }
            } elseif ($type === 'inventory') {
                fputcsv($handle, ['Product ID', 'SKU', 'Product Name', 'Category', 'Selling Price (₦)', 'Total Physical Stock', 'Stock Status', 'Total Asset Valuation (₦)']);
                $products = Product::where('user_id', $sellerId)->where('status', '!=', 2)->get();
                foreach ($products as $p) {
                    $stockRow = DB::table('product_stocks')->where('product_id', $p->id)->first();
                    $stock = $stockRow ? (int) $stockRow->qty : (int) $p->current_stock;
                    $reorderLevel = (int) ($p->pos_reorder_level ?? 5);
                    $status = $stock <= 0 ? 'OUT_OF_STOCK' : ($stock <= $reorderLevel ? 'LOW_STOCK' : 'IN_STOCK');
                    fputcsv($handle, [$p->id, $p->code, $p->name, $p->pos_category ?? 'General', $p->unit_price, $stock, $status, $stock * (float)$p->unit_price]);
                }
            } elseif ($type === 'transfers') {
                fputcsv($handle, ['Date & Time', 'Product Name', 'Product SKU', 'Type', 'Quantity', 'Recorded By', 'Notes']);
                $transfers = DB::table('pos_inventory_logs')
                    ->where('seller_id', $sellerId)
                    ->whereIn('type', ['TRANSFER_IN', 'TRANSFER_OUT'])
                    ->orderBy('created_at', 'desc')
                    ->get();
                foreach ($transfers as $t) {
                    fputcsv($handle, [
                        $t->created_at,
                        $t->product_name,
                        $t->product_code,
                        $t->type,
                        abs($t->quantity_change),
                        $t->recorded_by,
                        $t->notes
                    ]);
                }
            } elseif ($type === 'debtors') {
                fputcsv($handle, ['Customer Name', 'Phone Number', 'Total Debt Owed (₦)', 'Sales Count', 'Last Transaction']);
                $debtors = DB::table('pos_sales')
                    ->where('seller_id', $sellerId)
                    ->where('debt_amount', '>', 0)
                    ->whereNotIn('status', ['voided'])
                    ->selectRaw('
                        customer_name,
                        customer_phone,
                        SUM(debt_amount) as total_debt,
                        COUNT(id) as sale_count,
                        MAX(created_at) as last_sale
                    ')
                    ->groupBy('customer_name', 'customer_phone')
                    ->get();
                foreach ($debtors as $c) {
                    fputcsv($handle, [$c->customer_name, $c->customer_phone, $c->total_debt, $c->sale_count, $c->last_sale]);
                }
            } elseif ($type === 'damages') {
                fputcsv($handle, ['Date & Time', 'Product Name', 'SKU', 'Deducted Qty', 'Reason', 'Staff Responsible', 'Notes']);
                $damages = DB::table('pos_stock_adjustments')
                    ->where('seller_id', $sellerId)
                    ->orderBy('created_at', 'desc')
                    ->get();
                foreach ($damages as $a) {
                    $p = Product::find($a->product_id);
                    fputcsv($handle, [
                        $a->created_at,
                        $p?->name ?? 'Product',
                        $p?->code ?? '',
                        abs($a->quantity_change),
                        $a->reason,
                        $a->adjusted_by,
                        $a->notes
                    ]);
                }
            } elseif ($type === 'returns') {
                fputcsv($handle, ['Date & Time', 'Receipt Ref', 'Customer Name', 'Product Name', 'Returned Qty', 'Refunded Amount (₦)', 'Reason', 'Handled By']);
                $returns = DB::table('pos_returns')
                    ->where('seller_id', $sellerId)
                    ->orderBy('created_at', 'desc')
                    ->get();
                foreach ($returns as $r) {
                    $p = Product::find($r->product_id);
                    fputcsv($handle, [
                        $r->created_at,
                        $r->receipt_number,
                        $r->customer_name,
                        $p?->name ?? 'Product',
                        $r->quantity,
                        $r->refund_amount,
                        $r->reason,
                        $r->cashier_name
                    ]);
                }
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    public function exportJson(Request $request, $type)
    {
        $sellerId = $this->resolveAuthSellerId();
        $fileName = "pos_{$type}_business_data_" . date('Y_m_d_His') . ".json";

        $data = match($type) {
            'sales' => [
                'metadata' => ['report' => 'Sales & Revenue Analysis', 'generated_at' => now()->toIso8601String(), 'currency' => 'NGN'],
                'data' => DB::table('pos_sales')->where('seller_id', $sellerId)->orderBy('created_at', 'desc')->get()
            ],
            'inventory' => [
                'metadata' => ['report' => 'Multi-Branch Inventory Valuation', 'generated_at' => now()->toIso8601String(), 'currency' => 'NGN'],
                'data' => Product::where('user_id', $sellerId)->where('status', '!=', 2)->get()->map(function($p) {
                    $stockRow = DB::table('product_stocks')->where('product_id', $p->id)->first();
                    $p->qty = $stockRow ? (int) $stockRow->qty : (int) $p->current_stock;
                    return $p;
                })
            ],
            'transfers' => [
                'metadata' => ['report' => 'Inter-Branch Transfer Movements & Discrepancies', 'generated_at' => now()->toIso8601String()],
                'data' => DB::table('pos_inventory_logs')->where('seller_id', $sellerId)->whereIn('type', ['TRANSFER_IN', 'TRANSFER_OUT'])->orderBy('created_at', 'desc')->get()
            ],
            'debtors' => [
                'metadata' => ['report' => 'Debtors Ledger & Credit Exposure', 'generated_at' => now()->toIso8601String(), 'currency' => 'NGN'],
                'data' => DB::table('pos_sales')
                    ->where('seller_id', $sellerId)
                    ->where('debt_amount', '>', 0)
                    ->whereNotIn('status', ['voided'])
                    ->selectRaw('customer_name, customer_phone, SUM(debt_amount) as total_debt, COUNT(id) as sale_count, MAX(created_at) as last_sale')
                    ->groupBy('customer_name', 'customer_phone')
                    ->get()
            ],
            'damages' => [
                'metadata' => ['report' => 'Damaged Goods & Loss Audit Trail', 'generated_at' => now()->toIso8601String()],
                'data' => DB::table('pos_stock_adjustments')->where('seller_id', $sellerId)->orderBy('created_at', 'desc')->get()
            ],
            'returns' => [
                'metadata' => ['report' => 'Customer Returns & Refunds Ledger', 'generated_at' => now()->toIso8601String(), 'currency' => 'NGN'],
                'data' => DB::table('pos_returns')->where('seller_id', $sellerId)->orderBy('created_at', 'desc')->get()
            ],
            default => ['error' => 'Invalid report type'],
        };

        return response()->json($data, 200, [
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ], JSON_PRETTY_PRINT);
    }

    private function resolveRange(string $preset, ?string $from, ?string $to): array
    {
        if ($from && $to) {
            return [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()];
        }
        return match ($preset) {
            'TODAY'      => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
            'YESTERDAY'  => [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()],
            'THIS_WEEK'  => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'THIS_MONTH' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'THIS_YEAR'  => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            default      => [null, null],
        };
    }
}

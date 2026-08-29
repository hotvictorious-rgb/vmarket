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

    public function export(Request $request)
    {
        $sellerId   = $this->resolveAuthSellerId();
        $datePreset = $request->get('date_preset', 'THIS_MONTH');
        [$start, $end] = $this->resolveRange($datePreset, $request->from_date, $request->to_date);

        $data = DB::table('pos_sale_items as psi')
            ->join('pos_sales as ps', 'ps.id', '=', 'psi.pos_sale_id')
            ->where('ps.seller_id', $sellerId)->where('ps.status', 'completed')
            ->when($start && $end, fn($q) => $q->whereBetween('ps.created_at', [$start, $end]))
            ->selectRaw('ps.receipt_number, ps.created_at as sale_date, ps.customer_name, psi.product_name, psi.quantity, psi.unit_price, psi.total_price, psi.purchase_price, (psi.total_price - psi.quantity * psi.purchase_price) as gross_profit')
            ->orderByDesc('ps.created_at')
            ->get();

        $filename = 'pos-report-' . now()->format('Y-m-d') . '.csv';
        $callback = function () use ($data) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Receipt#', 'Date', 'Customer', 'Product', 'Qty', 'Unit Price (₦)', 'Total (₦)', 'Cost (₦)', 'Gross Profit (₦)']);
            foreach ($data as $row) {
                fputcsv($handle, [
                    $row->receipt_number, $row->sale_date, $row->customer_name,
                    $row->product_name, $row->quantity,
                    number_format($row->unit_price, 2),
                    number_format($row->total_price, 2),
                    number_format($row->purchase_price * $row->quantity, 2),
                    number_format($row->gross_profit, 2),
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
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

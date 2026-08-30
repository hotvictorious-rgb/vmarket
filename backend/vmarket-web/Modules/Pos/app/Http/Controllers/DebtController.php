<?php

namespace Modules\Pos\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * [AI] DebtController — Customer debt ledger for In-Store POS credit sales.
 * Ported from Hysam standalone DebtController.
 *
 * Reads/writes: pos_sales (debt_amount), pos_payments (method=debt / debt_payment)
 * All queries scoped to seller_id.
 * Pessimistic locking on debt settlement to prevent race conditions.
 *
 * Clients: Verified Merchant, Unverified Merchant Free POS.
 */
use Modules\Pos\app\Traits\PosAuthTrait;

class DebtController extends Controller
{
    use PosAuthTrait;

    /**
     * Debt ledger summary — all customers with outstanding balances.
     */
    public function index(Request $request = null)
    {
        $request  = $request ?? request();
        $sellerId = $this->resolveAuthSellerId();
        $search   = trim($request->get('search', ''));

        // [AI] Aggregate outstanding debt per customer from pos_sales
        $query = DB::table('pos_sales')
            ->where('seller_id', $sellerId)
            ->where('debt_amount', '>', 0)
            ->whereNotIn('status', ['voided'])
            ->selectRaw('
                customer_name,
                customer_phone,
                customer_id,
                SUM(debt_amount) as total_debt,
                COUNT(id) as sale_count,
                MAX(created_at) as last_sale
            ')
            ->groupBy('customer_name', 'customer_phone', 'customer_id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        $debtors = $query->orderByDesc('total_debt')->paginate(25)->withQueryString();
        $debtors->getCollection()->transform(function ($d) {
            $d->name = $d->customer_name ?? 'Walk-in Customer';
            $d->phone = $d->customer_phone ?? '';
            $d->address = $d->customer_address ?? $d->address ?? 'Walk-in';
            $d->id = $d->customer_id ?? 1;
            $d->totalDebt = $d->total_debt ?? 0;
            $d->customer_code = $d->customer_code ?? ('CUST-' . str_pad($d->id, 4, '0', STR_PAD_LEFT));
            return $d;
        });

        $totalOutstandingDebt = (float) DB::table('pos_sales')
            ->where('seller_id', $sellerId)
            ->where('debt_amount', '>', 0)
            ->whereNotIn('status', ['voided'])
            ->sum('debt_amount');

        $totalDebtorsCount = DB::table('pos_sales')
            ->where('seller_id', $sellerId)
            ->where('debt_amount', '>', 0)
            ->distinct()
            ->count('customer_phone');

        $highRiskDebtorsCount = 0;
        $debtBracket    = $request->get('debt_bracket', 'ALL');
        $datePreset     = $request->get('date_preset', 'ALL');
        $branches       = DB::table('shops')->where('seller_id', $sellerId)->get();
        $warehouses     = $branches;
        $recentPayments = collect([]);

        return view('pos::debts.index', compact('debtors', 'totalOutstandingDebt', 'totalDebtorsCount', 'highRiskDebtorsCount', 'branches', 'warehouses', 'debtBracket', 'datePreset', 'recentPayments', 'search'));
    }

    /**
     * Individual customer ledger — full sale + payment history.
     */
    public function customerLedger($id, Request $request)
    {
        $sellerId = $this->resolveAuthSellerId();
        $phone    = $request->get('phone', $id);

        // [AI] IDOR: scope to seller_id
        $sales = DB::table('pos_sales')
            ->where('seller_id', $sellerId)
            ->where(function ($q) use ($id, $phone) {
                $q->where('customer_phone', $phone)
                  ->orWhere('customer_id', $id);
            })
            ->orderByDesc('created_at')
            ->get();

        $customerName  = $sales->first()->customer_name ?? 'Unknown';
        $customerPhone = $sales->first()->customer_phone ?? '';
        $totalDebt     = $sales->sum('debt_amount');
        $totalPaid     = $sales->sum('paid_amount');
        $totalBought   = $sales->sum('total_amount');

        return view('pos::debts.customer', compact(
            'sales', 'customerName', 'customerPhone',
            'totalDebt', 'totalPaid', 'totalBought'
        ));
    }

    /**
     * Record a debt repayment against a specific sale.
     * [AI] Pessimistic lock ensures no double-crediting.
     */
    public function recordPayment(Request $request)
    {
        $request->validate([
            'pos_sale_id'    => 'required|integer',
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:cash,pos_card,bank_transfer',
            'reference_no'   => 'nullable|string|max:100',
        ]);

        $sellerId = $this->resolveAuthSellerId();
        $saleId   = (int) $request->pos_sale_id;
        $cashierName = $this->resolveAuthUserName();

        try {
            DB::transaction(function () use ($sellerId, $saleId, $amount, $request, $cashierName) {
                // [AI] Pessimistic lock + IDOR check
                $sale = DB::table('pos_sales')
                    ->where('id', $saleId)
                    ->where('seller_id', $sellerId)
                    ->lockForUpdate()
                    ->first();

                abort_if(!$sale, 403, 'Unauthorized sale access.');

                $remainingDebt = (float) $sale->debt_amount;
                if ($amount > $remainingDebt) {
                    throw new \Exception("Payment amount (₦" . number_format($amount, 2) . ") exceeds outstanding debt (₦" . number_format($remainingDebt, 2) . ").");
                }

                // [AI] Decrement debt_amount — atomic write
                DB::table('pos_sales')
                    ->where('id', $saleId)
                    ->where('seller_id', $sellerId)
                    ->decrement('debt_amount', $amount, [
                        'paid_amount' => DB::raw("paid_amount + {$amount}"),
                        'updated_at'  => now(),
                    ]);

                DB::table('pos_payments')->insert([
                    'pos_sale_id'  => $saleId,
                    'seller_id'    => $sellerId,
                    'amount'       => $amount,
                    'method'       => $request->payment_method,
                    'reference_no' => $request->reference_no,
                    'recorded_by'  => $cashierName,
                    'paid_at'      => now(),
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);

                DB::table('pos_activities')->insert([
                    'seller_id'   => $sellerId,
                    'branch_id'   => $sale->branch_id,
                    'actor_id'    => (string) Auth::id(),
                    'actor_name'  => $cashierName,
                    'type'        => 'DEBT_PAYMENT',
                    'description' => "Debt repayment of ₦" . number_format($amount, 2) . " received from {$sale->customer_name} for Sale #{$sale->receipt_number}",
                    'metadata'    => json_encode(['pos_sale_id' => $saleId, 'method' => $request->payment_method]),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            });

            return redirect()->back()->with('success', "✓ Debt payment of ₦" . number_format($amount, 2) . " recorded successfully.");
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * CSV export of outstanding debts.
     */
    public function export(Request $request)
    {
        $sellerId = $this->resolveAuthSellerId();

        $debtors = DB::table('pos_sales')
            ->where('seller_id', $sellerId)
            ->where('debt_amount', '>', 0)
            ->selectRaw('customer_name, customer_phone, SUM(debt_amount) as total_debt, COUNT(id) as sales')
            ->groupBy('customer_name', 'customer_phone')
            ->orderByDesc('total_debt')
            ->get();

        $filename = 'pos-debtors-' . now()->format('Y-m-d') . '.csv';
        $callback = function () use ($debtors) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Customer', 'Phone', 'Outstanding Debt (₦)', 'No. of Sales']);
            foreach ($debtors as $d) {
                fputcsv($handle, [$d->customer_name, $d->customer_phone, number_format($d->total_debt, 2), $d->sales]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}

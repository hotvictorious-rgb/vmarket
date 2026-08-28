<?php

namespace App\Http\Controllers\Vendor\POS;

use App\Http\Controllers\BaseController;
use App\Models\PosCustomerLedger;
use App\Models\PosDebtTransaction;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * [AI] Class CustomerDebtController
 * Manages walk-in customer credit profiles, 30-day aging buckets, and installment repayments.
 */
class CustomerDebtController extends BaseController
{
    public function index(?Request $request, ?string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse|JsonResponse
    {
        $sellerId = auth('seller')->id();
        $aging = $request->get('aging', 'all');
        $search = $request->get('search');

        $ledgers = PosCustomerLedger::with('transactions')
            ->where('seller_id', $sellerId)
            ->when($aging !== 'all', function ($query) use ($aging) {
                return $query->where('aging_bucket', $aging);
            })
            ->when($search, function ($query) use ($search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('total_credit_due', 'desc')
            ->paginate(20);

        $totalDebt = PosCustomerLedger::where('seller_id', $sellerId)->sum('total_credit_due');
        $currentCount = PosCustomerLedger::where('seller_id', $sellerId)->where('aging_bucket', 'current')->where('total_credit_due', '>', 0)->count();
        $dueCount = PosCustomerLedger::where('seller_id', $sellerId)->where('aging_bucket', 'due')->where('total_credit_due', '>', 0)->count();
        $criticalCount = PosCustomerLedger::where('seller_id', $sellerId)->where('aging_bucket', 'critical')->where('total_credit_due', '>', 0)->count();

        return view('vendor-views.pos.debt-ledger', compact(
            'ledgers',
            'aging',
            'search',
            'totalDebt',
            'currentCount',
            'dueCount',
            'criticalCount'
        ));
    }

    public function repay(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'ledger_id' => 'required|integer',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string',
        ]);

        $sellerId = auth('seller')->id();
        $ledgerId = (int)$request->ledger_id;
        $amount = (float)$request->amount;

        $remainingBalance = 0;

        DB::transaction(function () use ($ledgerId, $sellerId, $amount, $request, &$remainingBalance) {
            $ledger = PosCustomerLedger::where('id', $ledgerId)
                ->where('seller_id', $sellerId)
                ->lockForUpdate()
                ->firstOrFail();

            $actualDeduction = min($amount, (float)$ledger->total_credit_due);

            $ledger->total_credit_due = max(0, (float)$ledger->total_credit_due - $actualDeduction);
            if ($ledger->total_credit_due == 0) {
                $ledger->is_credit_blocked = false;
                $ledger->aging_bucket = 'current';
            }
            $ledger->save();

            $remainingBalance = $ledger->total_credit_due;

            PosDebtTransaction::create([
                'ledger_id' => $ledger->id,
                'seller_id' => $sellerId,
                'transaction_type' => 'repayment',
                'amount' => $actualDeduction,
                'payment_method' => $request->payment_method,
                'collected_by_id' => $sellerId,
                'notes' => $request->notes ?? 'Partial debt installment payment',
            ]);
        });

        if ($request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => translate('Repayment_recorded_successfully'),
                'remaining_balance' => $remainingBalance,
            ]);
        }

        ToastMagic::success(translate('Repayment_recorded_successfully'));
        return back();
    }
}

<?php

namespace App\Http\Controllers\Vendor\POS;

use App\Http\Controllers\BaseController;
use App\Models\PosCustomerLedger;
use App\Models\PosDebtTransaction;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * [AI] Class CustomerDebtController
 * Manages walk-in customer credit profiles, 30-day aging buckets, and installment repayments.
 */
class CustomerDebtController extends BaseController
{
    public function index(Request $request): View
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
        $ledger = PosCustomerLedger::where('id', $request->ledger_id)
            ->where('seller_id', $sellerId)
            ->firstOrFail();

        $amount = (float)$request->amount;
        if ($amount > $ledger->total_credit_due) {
            $amount = $ledger->total_credit_due;
        }

        DB::transaction(function () use ($ledger, $sellerId, $amount, $request) {
            $ledger->total_credit_due = max(0, $ledger->total_credit_due - $amount);
            if ($ledger->total_credit_due == 0) {
                $ledger->is_credit_blocked = false;
                $ledger->aging_bucket = 'current';
            }
            $ledger->save();

            PosDebtTransaction::create([
                'ledger_id' => $ledger->id,
                'seller_id' => $sellerId,
                'transaction_type' => 'repayment',
                'amount' => $amount,
                'payment_method' => $request->payment_method,
                'collected_by_id' => auth('seller')->id(),
                'notes' => $request->notes ?? 'Partial debt installment payment',
            ]);
        });

        if ($request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => translate('Repayment_recorded_successfully'),
                'remaining_balance' => $ledger->total_credit_due,
            ]);
        }

        ToastMagic::success(translate('Repayment_recorded_successfully'));
        return back();
    }
}

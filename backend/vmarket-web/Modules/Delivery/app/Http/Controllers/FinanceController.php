<?php

namespace Modules\Delivery\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DeliveryMan;
use App\Models\DeliveryManTransaction;
use App\Models\DeliverymanWallet;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    /**
     * [AI] Financial ledger, Cash-on-Delivery collections, and rider remittances.
     * Optimized with unified SQL aggregation and eager-loaded relationships.
     */
    public function index(Request $request): View
    {
        $wallets = DeliverymanWallet::with(['delivery_man.hub.city'])
            ->where('cash_in_hand', '>', 0)
            ->orderBy('cash_in_hand', 'desc')
            ->paginate(15);

        // [AI] Single consolidated aggregation query instead of 2 separate table scans
        $financialSummary = DeliverymanWallet::selectRaw('
            COALESCE(SUM(cash_in_hand), 0) as total_cash_in_hand,
            COALESCE(SUM(total_withdraw), 0) as total_collected_cash
        ')->first();

        $totalCashInHand = (float) ($financialSummary->total_cash_in_hand ?? 0);
        $totalCollectedCash = (float) ($financialSummary->total_collected_cash ?? 0);

        $recentTransactions = DeliveryManTransaction::with(['delivery_man'])
            ->latest()
            ->take(10)
            ->get();

        return view('delivery::finance.index', compact('wallets', 'totalCashInHand', 'totalCollectedCash', 'recentTransactions'));
    }

    /**
     * [AI] Record Cash-in-Hand remittance from rider with pessimistic concurrency lock.
     */
    public function recordRemittance(Request $request): RedirectResponse
    {
        $request->validate([
            'delivery_man_id' => 'required|exists:delivery_men,id',
            'amount' => 'required|numeric|min:1',
            'remittance_type' => 'required|in:cash_deposit_at_hub,bank_transfer_to_admin',
            'reference' => 'nullable|string|max:100',
        ]);

        $deliveryManId = (int) $request->delivery_man_id;
        $amount = (float) $request->amount;

        $settled = DB::transaction(function () use ($deliveryManId, $amount, $request) {
            $wallet = DeliverymanWallet::where('delivery_man_id', $deliveryManId)->lockForUpdate()->first();
            if (!$wallet) return false;

            if ($wallet->cash_in_hand < $amount) {
                return 'insufficient';
            }

            $wallet->cash_in_hand -= $amount;
            $wallet->save();

            // Record transaction ledger
            DeliveryManTransaction::create([
                'delivery_man_id' => $deliveryManId,
                'user_id' => auth('admin')->id() ?? 1,
                'user_type' => 'admin',
                'amount' => $amount,
                'transaction_type' => 'cash_in_hand_settlement',
                'debit' => 0.00,
                'credit' => $amount,
                'transaction_id' => 'REMIT-' . strtoupper(\Illuminate\Support\Str::random(8)),
            ]);

            return true;
        });

        if ($settled === 'insufficient') {
            Toastr::error('Remittance amount exceeds the rider\'s current Cash-in-Hand balance!');
            return back();
        }

        if (!$settled) {
            Toastr::error('Failed to process remittance.');
            return back();
        }

        Cache::forget('delivery_dashboard_kpis');

        Toastr::success("₦" . number_format($amount, 2) . " remittance recorded and cleared from rider successfully!");
        return back();
    }
}

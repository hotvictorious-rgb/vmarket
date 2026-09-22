<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\CashbackRedemption;
use App\Models\CustomerCashbackLedger;
use App\Utils\Helpers;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * [AI] Class CustomerCashbackAdminController
 * Administrative oversight for Victorious Points (Cashback) ledger.
 * Spec Reference: Sections 36, 37, 38; Alignment Rules Section 7
 *
 * Invariant: Admin may VIEW and AUDIT cashback entries only.
 * Admin must NEVER manually credit, debit, or delete cashback entries —
 * all mutations come exclusively from authoritative service events (settlement, refund, pickup payment).
 * Invariant: Δ cashback_amount = 0.00 (all ledger adjustments are system-originated, never manual).
 */
class CustomerCashbackAdminController extends Controller
{
    /**
     * Display Victorious Points cashback ledger overview
     */
    public function index(Request $request): View|RedirectResponse
    {
        if (!Helpers::module_permission_check('cashback.manage') && !Helpers::module_permission_check('report')) {
            ToastMagic::error(translate('Access Denied: Permission required to view cashback ledger.'));
            return redirect()->route('admin.dashboard.index');
        }

        $status = $request->get('status', 'all');
        $channel = $request->get('channel', 'all'); // 'delivery', 'pickup', 'all'

        // Cashback Ledger (5% earn entries — delivery orders)
        $ledgerQuery = CustomerCashbackLedger::with(['customer', 'order'])
            ->when($status !== 'all', fn($q) => $q->where('status', $status));

        if ($request->filled('searchValue')) {
            $search = $request->searchValue;
            $ledgerQuery->whereHas('customer', function ($q) use ($search) {
                $q->where('f_name', 'like', "%{$search}%")
                  ->orWhere('l_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            })->orWhereHas('order', function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%");
            });
        }

        $ledgerEntries = $ledgerQuery->latest()->paginate(25);

        // Redemption Ledger (spend/capture/release records)
        $redemptionQuery = CashbackRedemption::with(['customer', 'checkoutIntent', 'pickupReservation'])
            ->when($channel === 'delivery', fn($q) => $q->whereNotNull('checkout_intent_id'))
            ->when($channel === 'pickup', fn($q) => $q->whereNotNull('pickup_reservation_id'))
            ->when($status !== 'all', fn($q) => $q->where('status', $status));

        $redemptions = $redemptionQuery->latest()->paginate(25, ['*'], 'redemption_page');

        // Summary metrics — compute from DB aggregates (not from PHP accumulation)
        $metrics = [
            'total_pending_cashback'   => CustomerCashbackLedger::where('status', 'pending')->sum('cashback_amount'),
            'total_available_cashback' => CustomerCashbackLedger::where('status', 'available')->sum('cashback_amount'),
            'total_redeemed_points'    => CashbackRedemption::where('status', 'captured')->sum('cashback_amount'),
            'total_released_points'    => CashbackRedemption::where('status', 'released')->sum('cashback_amount'),
            'total_expired_reservations' => CashbackRedemption::where('status', 'expired')->count(),
        ];

        return view('admin-views.finance.cashback-ledger', compact(
            'ledgerEntries', 'redemptions', 'metrics', 'status', 'channel'
        ));
    }
}

<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\WalletTransaction;
use App\Utils\Helpers;
use App\Models\AddFundBonusCategories;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;

use function App\Utils\payment_gateways;

class UserWalletController extends Controller
{

    public function index(Request $request): View|RedirectResponse
    {
        // [AI] Customer Wallet Decommissioned: Controlled redirect with info notice
        Toastr::info('Customer wallet feature is permanently decommissioned in Victorious MARKET.');
        return redirect()->route('user-profile');
    }

    public function myWalletAccount(): View|RedirectResponse
    {
        // [AI] Customer Wallet Decommissioned: Controlled redirect with info notice
        Toastr::info('Customer wallet feature is permanently decommissioned in Victorious MARKET.');
        return redirect()->route('user-profile');
    }

    private function getWalletTransactionList(object|array $request, array $types)
    {
        $startDate = '';
        $endDate = '';
        if (isset($request['transaction_range']) && !empty($request['transaction_range'])) {
            $dates = explode(' - ', $request['transaction_range']);
            if (count($dates) !== 2 || !checkDateFormatInMDY($dates[0]) || !checkDateFormatInMDY($dates[1])) {
                Toastr::error(translate('Invalid_date_range_format'));
                return back();
            }
            $startDate = Carbon::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d') . ' 00:00:00';
            $endDate = Carbon::createFromFormat('d/m/Y', $dates[1])->format('Y-m-d') . ' 23:59:59';
        }
        return WalletTransaction::where('user_id', auth('customer')->id())
            ->when($request->has('filter_by') && in_array($request['filter_by'], ['debit', 'credit']), function ($query) use ($request) {
                $query->when($request['filter_by'] == 'debit', function ($query) {
                    $query->where('debit', '!=', 0);
                })->when($request['filter_by'] == 'credit', function ($query) {
                    $query->where('debit', '=', 0);
                });
            })
            ->when(!empty($startDate) && !empty($endDate), function ($query) use ($startDate, $endDate) {
                return $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->when(!empty($types) || in_array('added_via_payment_method', $request['types'] ?? []) || in_array('earned_by_referral', $request['types'] ?? []), function ($query) use ($types, $request) {
                $query->where(function ($query) use ($types, $request) {
                    return $query->when(!empty($types), function ($query) use ($types, $request) {
                        return $query->when(!in_array('earned_by_referral', $types), function ($query) use ($types) {
                                return $query->where('reference', '!=', 'earned_by_referral');
                            })->whereIn('transaction_type', $types)
                            ->orWhere(function ($query) use ($types, $request) {
                                return $query->whereNull('reference');
                            });
                    })->when(in_array('added_via_payment_method', $request['types'] ?? []), function ($query) use ($types, $request) {
                        return $query->orWhere('reference', 'add_funds_to_wallet');
                    })->when(in_array('earned_by_referral', $request['types'] ?? []), function ($query) use ($types, $request) {
                       return $query->orWhere('reference', 'earned_by_referral');
                    });
                });
            })
            ->latest()
            ->paginate(10)->appends(request()->query());
    }

    public function getAddFundBonusList()
    {
        return AddFundBonusCategories::where('is_active', 1)
            ->whereDate('start_date_time', '<=', date('Y-m-d'))
            ->whereDate('end_date_time', '>=', date('Y-m-d'))
            ->get();
    }

    public function getSelectTransactionTypes($types): array
    {
        $typeMapping = [
            'order_refund' => 'order_refund',
            'order_place' => 'order_place',
            'loyalty_point' => 'loyalty_point',
            'add_fund' => 'add_fund',
            'add_fund_by_admin' => 'add_fund_by_admin',
            'due_payment_for_order' => 'due_payment_for_order',
            'return_order_amount_by_admin' => 'return_order_amount_by_admin',
        ];

        foreach ($typeMapping as $key => $value) {
            if (in_array($key, $types)) {
                $transactionTypes[] = $value;
            }
        }
        return $transactionTypes ?? [];
    }
}

<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\LogisticsCompanyTransaction;
use App\Models\LogisticsCompanyWallet;
use App\Models\LogisticsCompanyWithdrawRequest;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function index(Request $request): View
    {
        $company = Auth::guard('logistics')->user();
        $companyId = $company->id;

        $wallet = LogisticsCompanyWallet::firstOrCreate(
            ['logistics_company_id' => $companyId],
            [
                'total_earned' => 0.00,
                'withdrawn' => 0.00,
                'pending_withdraw' => 0.00,
                'current_balance' => 0.00,
            ]
        );

        $transactions = LogisticsCompanyTransaction::where('logistics_company_id', $companyId)
            ->with(['order', 'deliveryMan'])
            ->latest()
            ->paginate(20, ['*'], 'tx_page');

        $withdrawRequests = LogisticsCompanyWithdrawRequest::where('logistics_company_id', $companyId)
            ->latest()
            ->paginate(15, ['*'], 'withdraw_page');

        return view('logistics-views.wallet.index', compact('company', 'wallet', 'transactions', 'withdrawRequests'));
    }

    public function requestWithdraw(Request $request): RedirectResponse
    {
        $company = Auth::guard('logistics')->user();
        $companyId = $company->id;

        $request->validate([
            'amount' => 'required|numeric|min:1000',
            'transaction_note' => 'nullable|string|max:500',
        ]);

        $requestedAmount = round((float) $request->amount, 2);

        DB::beginTransaction();
        try {
            $wallet = LogisticsCompanyWallet::where('logistics_company_id', $companyId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($wallet->current_balance < $requestedAmount) {
                DB::rollBack();
                ToastMagic::error(translate('Insufficient_available_balance._Current_balance:_₦') . number_format($wallet->current_balance, 2));
                return redirect()->back();
            }

            if (empty($company->bank_name) || empty($company->account_number)) {
                DB::rollBack();
                ToastMagic::warning(translate('Please_configure_your_company_bank_account_details_before_requesting_a_withdrawal.'));
                return redirect()->back();
            }

            // Deduct available balance and hold in pending_withdraw
            $wallet->current_balance = round((float)$wallet->current_balance - $requestedAmount, 2);
            $wallet->pending_withdraw = round((float)$wallet->pending_withdraw + $requestedAmount, 2);
            $wallet->save();

            LogisticsCompanyWithdrawRequest::create([
                'logistics_company_id' => $companyId,
                'amount' => $requestedAmount,
                'bank_name' => $company->bank_name,
                'account_number' => $company->account_number,
                'account_name' => $company->account_name,
                'status' => 'pending',
                'transaction_note' => $request->transaction_note,
            ]);

            DB::commit();
            ToastMagic::success(translate('Withdrawal_request_of_₦') . number_format($requestedAmount, 2) . translate('_submitted_successfully!'));
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            ToastMagic::error(translate('Withdrawal_failed:_') . $e->getMessage());
            return redirect()->back();
        }
    }
}

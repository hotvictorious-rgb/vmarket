<?php

namespace App\Http\Controllers\RestAPI\v2\delivery_man;

use App\Http\Controllers\Controller;
use App\Models\DeliverymanWallet;
use App\Models\WithdrawRequest;
use App\Traits\CommonTrait;
use App\Utils\Convert;
use App\Utils\Helpers;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\DB;

class WithdrawController extends Controller
{
    public function sendWithdrawRequest(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'regex:/^(?:0|[1-9][0-9]{0,12})(?:\.[0-9]{1,2})?$/D', 'numeric', 'min:1'],
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $deliveryMan = $request->delivery_man;
        $parentId = $request->delivery_man->seller_id;
        // [AI] Victorious Market operates natively in NGN. Delivery charges and wallet balances are stored in NGN.
        $requestedAmount = bcadd((string)$request['amount'], '0', 2);

        return DB::transaction(function () use ($deliveryMan, $parentId, $requestedAmount, $request) {
            $wallet = DeliverymanWallet::where('delivery_man_id', $deliveryMan['id'])->lockForUpdate()->first();
            
            if (!$wallet) {
                return response()->json(['message' => translate('Wallet not found')], 404);
            }

            // [AI] Directive 57326: cash_in_hand removed — V1 riders do not collect cash.
            $withdrawable = bcsub((string)$wallet->getRawOriginal('current_balance'), (string)$wallet->getRawOriginal('pending_withdraw'), 2);
            if (bccomp($withdrawable, $requestedAmount, 2) < 0) {
                return response()->json(['message' => translate('withdraw_request_amount_can_not_be_more_than_withdrawable_balance')], 403);
            }

            // [AI] Freeze bank details from the authenticated profile; submitted beneficiary fields are never authoritative.
            $profile = \App\Models\DeliveryMan::whereKey($deliveryMan['id'])->lockForUpdate()->firstOrFail();
            if (empty($profile->bank_name) || empty($profile->account_no) || empty($profile->holder_name)) {
                return response()->json(['message' => 'Complete your bank beneficiary before requesting a payout.'], 422);
            }
            WithdrawRequest::create([
                'withdrawal_method_fields' => ['currency' => 'NGN', 'bank_name' => $profile->bank_name,
                    'account_no' => $profile->account_no, 'holder_name' => $profile->holder_name, 'captured_at' => now()->toIso8601String()],
                'delivery_man_id' => $deliveryMan['id'],
                ($parentId == 0) ? 'admin_id' : 'seller_id' => $parentId,
                'amount' => $requestedAmount,
                'transaction_note' => $request['note'] ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $wallet->pending_withdraw = bcadd((string)$wallet->getRawOriginal('pending_withdraw'), $requestedAmount, 2);
            $wallet->save();

            return response()->json(['message' => translate('Withdraw_request_sent_successfully!')], 200);
        });
    }

    public function getWithdrawListByApproved(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'offset' => 'required',
            'limit' => 'required',
            'type' => 'required|in:withdrawn,pending',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }
        $delivery_man = $request['delivery_man'];
        $approved = $request->type == 'withdrawn' ? 1 : 0;

        $withdraw = WithdrawRequest::where(['delivery_man_id' => $delivery_man->id, 'approved' => $approved]);

        if (isset($request->start_date) && isset($request->end_date)) {
            $start_date = Carbon::parse($request['start_date'])->format('Y-m-d 00:00:00');
            $end_data = Carbon::parse($request['end_date'])->format('Y-m-d 23:59:59');
            $withdraw->whereBetween('created_at', [$start_date, $end_data]);
        }
        $withdraws = $withdraw->latest()->paginate($request['limit'], ['*'], 'page', $request['offset']);

        $data['total_size'] = $withdraws->total();
        $data['limit'] = $request['limit'];
        $data['offset'] = $request['offset'];
        $data['withdraws'] = $withdraws->items();
        return response()->json($data, 200);
    }
}

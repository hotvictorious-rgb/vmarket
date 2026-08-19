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
            'amount' => 'required|numeric|min:1',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $deliveryMan = $request->delivery_man;
        $parentId = $request->delivery_man->seller_id;
        $requestedAmount = Convert::usd($request['amount']);

        return DB::transaction(function () use ($deliveryMan, $parentId, $requestedAmount, $request) {
            $wallet = DeliverymanWallet::where('delivery_man_id', $deliveryMan['id'])->lockForUpdate()->first();
            
            if (!$wallet) {
                return response()->json(['message' => translate('Wallet not found')], 404);
            }

            $withdrawable = ($wallet->current_balance ?? 0) - (($wallet->cash_in_hand ?? 0) + ($wallet->pending_withdraw ?? 0));
            if ($withdrawable < $requestedAmount) {
                return response()->json(['message' => translate('withdraw_request_amount_can_not_be_more_than_withdrawable_balance')], 403);
            }

            WithdrawRequest::create([
                'delivery_man_id' => $deliveryMan['id'],
                ($parentId == 0) ? 'admin_id' : 'seller_id' => $parentId,
                'amount' => $requestedAmount,
                'transaction_note' => $request['note'] ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $wallet->pending_withdraw += $requestedAmount;
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

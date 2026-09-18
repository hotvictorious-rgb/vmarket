<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPointTransaction;
use App\Utils\CustomerManager;
use App\Utils\Helpers;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class UserLoyaltyController extends Controller
{
    public function list(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required',
            'offset' => 'required',
        ]);

        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)]);
        }

        $loyaltyPointStatus = getWebConfig(name: 'loyalty_point_status');
        if ($loyaltyPointStatus == 1) {
            $user = $request->user();

            $transactionTypes = json_decode($request['transaction_types'] ?? '', true) ?? [];
            if (request()->has('start_date') && request()->has('end_date') && !checkDateFormatInMDY($request['start_date']) && !checkDateFormatInMDY($request['end_date'])) {
                $startDate = Carbon::createFromFormat('m/d/Y h:i:s a', $request['start_date'])->format('Y-m-d') . ' 00:00:00';
                $endDate = Carbon::createFromFormat('m/d/Y h:i:s a', $request['end_date'])->format('Y-m-d') . ' 23:59:59';
            } else {
                $startDate = '';
                $endDate = '';
            }
            $loyaltyPointList = LoyaltyPointTransaction::where('user_id', $user->id)
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
                ->when(!empty($transactionTypes) && !in_array('all', $transactionTypes), function ($query) use ($transactionTypes) {
                    return $query->whereIn('transaction_type', $transactionTypes);
                })
                ->latest()
                ->paginate($request['limit'], ['*'], 'page', $request['offset']);

            return response()->json([
                'limit' => (integer)$request['limit'],
                'offset' => (integer)$request['offset'],
                'total_loyalty_point' => $user->loyalty_point,
                'total_size' => $loyaltyPointList->total(),
                'loyalty_point_list' => $loyaltyPointList->items(),
                'filter_by' => $request['filter_by'],
                'start_date' => $request['start_date'],
                'end_date' => $request['end_date'],
                'transaction_types' => (array)$transactionTypes,
            ], 200);
        } else {
            return response()->json(['message' => translate('access_denied!')], 422);
        }
    }

    public function loyalty_exchange_currency(Request $request): JsonResponse
    {
        // [AI] Customer Wallet Decommissioned: Exchange to wallet currency is permanently blocked.
        return response()->json([
            'status' => false,
            'message' => 'Exchanging loyalty points for wallet balance is permanently decommissioned in Victorious MARKET.',
        ], 403);
    }
}

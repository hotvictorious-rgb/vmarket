<?php

namespace App\Http\Controllers\RestAPI\v3\seller;

use App\Http\Controllers\Controller;
use App\Models\DeliveryMan;
use App\Models\DeliverymanWallet;
use App\Models\WithdrawRequest;
use App\Utils\Convert;
use App\Utils\Helpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

class DeliverymanWithdrawController extends Controller
{
    public function list(Request $request): JsonResponse
    {
        $seller = $request->seller;
        $status = null;
        if($request->status == 'approved'){
            $status = 1;
        }elseif($request->status == 'denied'){
            $status = 2;
        }elseif($request->status == 'pending'){
            $status = '0';
        }

        $withdraws = WithdrawRequest::with(['deliveryMan'])
            ->where('seller_id', $seller->id)
            ->whereNotNull('delivery_man_id')
            ->when($request->status == 'all', function ($query) {
                return $query;
            })
            ->when($status!=null, function ($query) use($status){
                return $query->where('approved', $status);
            })
            ->latest()
            ->paginate($request['limit'], ['*'], 'page', $request['offset']);

        $data = array();
        $data['total_size'] = $withdraws->total();
        $data['limit'] = $request['limit'];
        $data['offset'] = $request['offset'];
        $data['withdraws'] = $withdraws->items();
        return response()->json($data, 200);
    }

    public function details(Request $request, $id): JsonResponse
    {
        $seller = $request->seller;
        $details = WithdrawRequest::with(['deliveryMan'])
            ->where('delivery_man_id', '<>', null)
            ->where(['seller_id' => $seller->id])
            ->find($id);

        return response()->json(['details'=>$details], 200);
    }

    public function status_update(Request $request): JsonResponse
    {
        // [AI] Nested rider withdrawals are owner-only and use the common reserved-funds payout protocol.
        if ($request->boolean('is_vendor_employee') || !in_array((string)$request->approved, ['1', '2'], true)) {
            return response()->json(['message' => 'Invalid or unauthorized withdrawal decision.'], 422);
        }
        try {
            return DB::transaction(function () use ($request) {
                $withdraw = WithdrawRequest::where('seller_id', $request->seller->id)
                    ->whereNotNull('delivery_man_id')->lockForUpdate()->find($request->id);
                if (!$withdraw) return response()->json(['message' => 'Withdrawal not found.'], 404);
                $wallet = DeliverymanWallet::where('delivery_man_id', $withdraw->delivery_man_id)->lockForUpdate()->first();
                $data = app(\App\Services\DeliveryManWithdrawService::class)->getUpdateData($request, $wallet, $withdraw);
                $wallet->forceFill($data['wallet'])->save();
                $withdraw->forceFill($data['withdraw'])->save();
                \App\Services\AdminAuditService::log('vendor.rider_withdrawal_decision', \App\Models\WithdrawRequest::class, $withdraw->id, ['approved' => 0], ['approved' => (int)$request->approved, 'actor_type' => 'seller', 'actor_id' => $request->seller->id, 'amount' => (string)$withdraw->getRawOriginal('amount')], $request->note);
                return response()->json(['message' => 'Withdrawal decision recorded.'], 200);
            });
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}

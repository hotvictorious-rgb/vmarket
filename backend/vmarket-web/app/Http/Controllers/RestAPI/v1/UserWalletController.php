<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Http\Controllers\Controller;
use App\Models\AddFundBonusCategories;
use App\Models\WalletTransaction;
use App\Utils\Helpers;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserWalletController extends Controller
{
    public function list(Request $request): JsonResponse
    {
        // [AI] Customer Wallet Decommissioned: Return 403 Forbidden
        return response()->json([
            'status' => false,
            'message' => 'Customer wallet is permanently decommissioned in Victorious MARKET.',
        ], 403);
    }

    public function bonus_list(Request $request): JsonResponse
    {
        // [AI] Customer Wallet Decommissioned: Return 403 Forbidden
        return response()->json([
            'status' => false,
            'message' => 'Customer wallet bonuses are permanently decommissioned in Victorious MARKET.',
        ], 403);
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

<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Http\Controllers\Controller;
use App\Models\CustomerCashbackLedger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * [AI] CustomerCashbackController
 *
 * Exposes customer-scoped cashback summary and history for the Victorious MARKET 5% Reward Ledger.
 * Operating Rules:
 * 1. Customer-scoped: Strictly queries the authenticated customer (auth('api')->id()).
 * 2. Exact DECIMAL calculations using raw SQL CAST(SUM(...) AS CHAR) with zero float conversion.
 * 3. Exact decimal string monetary representation ("0.00").
 * 4. Distinct status vocabulary: 'pending', 'available', 'redeemed', 'cancelled'.
 * 5. Not a stored-value cash wallet: Non-withdrawable purchase reward ledger.
 */
class CustomerCashbackController extends Controller
{
    /**
     * Get aggregated customer cashback balance summary.
     */
    public function getCashbackSummary(Request $request): JsonResponse
    {
        $customer = $request->user();
        if (!$customer) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated customer.',
            ], 401);
        }

        $customerId = $customer->id;

        // Exact DECIMAL string calculations via raw SQL CAST(COALESCE(SUM(...), 0.00) AS CHAR)
        $pendingResult = DB::select(
            'SELECT CAST(COALESCE(SUM(cashback_amount), 0.00) AS CHAR) AS total FROM customer_cashback_ledgers WHERE customer_id = ? AND status = ?',
            [$customerId, 'pending']
        );
        $pendingCashback = (string)($pendingResult[0]->total ?? '0.00');

        $availableResult = DB::select(
            'SELECT CAST(COALESCE(SUM(cashback_amount), 0.00) AS CHAR) AS total FROM customer_cashback_ledgers WHERE customer_id = ? AND status = ?',
            [$customerId, 'available']
        );
        $availableCashback = (string)($availableResult[0]->total ?? '0.00');

        $redeemedResult = DB::select(
            'SELECT CAST(COALESCE(SUM(cashback_amount), 0.00) AS CHAR) AS total FROM customer_cashback_ledgers WHERE customer_id = ? AND status = ?',
            [$customerId, 'redeemed']
        );
        $redeemedCashback = (string)($redeemedResult[0]->total ?? '0.00');

        $cancelledResult = DB::select(
            'SELECT CAST(COALESCE(SUM(cashback_amount), 0.00) AS CHAR) AS total FROM customer_cashback_ledgers WHERE customer_id = ? AND status = ?',
            [$customerId, 'cancelled']
        );
        $cancelledCashback = (string)($cancelledResult[0]->total ?? '0.00');

        return response()->json([
            'status' => true,
            'customer_id' => $customerId,
            'currency' => 'NGN',
            'pending_cashback_amount' => $pendingCashback,
            'available_cashback_amount' => $availableCashback,
            'redeemed_cashback_amount' => $redeemedCashback,
            'cancelled_cashback_amount' => $cancelledCashback,
        ], 200);
    }

    /**
     * Get paginated customer cashback ledger history.
     */
    public function getCashbackList(Request $request): JsonResponse
    {
        $customer = $request->user();
        if (!$customer) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated customer.',
            ], 401);
        }

        $limit = max(1, min(50, (int)$request->get('limit', 10)));
        $offset = max(0, (int)$request->get('offset', 0));
        $statusFilter = $request->get('status');

        $query = CustomerCashbackLedger::where('customer_id', $customer->id);

        if (!empty($statusFilter) && in_array($statusFilter, ['pending', 'available', 'redeemed', 'cancelled'], true)) {
            $query->where('status', $statusFilter);
        }

        $totalCount = $query->count();
        $ledgers = $query->orderByDesc('id')
            ->skip($offset)
            ->take($limit)
            ->get()
            ->map(function ($ledger) {
                return [
                    'id' => $ledger->id,
                    'order_id' => $ledger->order_id,
                    'merchandise_amount' => (string)($ledger->getRawOriginal('merchandise_amount') ?? '0.00'),
                    'cashback_rate' => (string)($ledger->getRawOriginal('cashback_rate') ?? '0.05'),
                    'cashback_amount' => (string)($ledger->getRawOriginal('cashback_amount') ?? '0.00'),
                    'status' => (string)$ledger->status,
                    'available_at' => $ledger->available_at ? $ledger->available_at->toISOString() : null,
                    'redeemed_at' => $ledger->redeemed_at ? $ledger->redeemed_at->toISOString() : null,
                    'redeemed_order_id' => $ledger->redeemed_order_id,
                    'description' => (string)$ledger->description,
                    'created_at' => $ledger->created_at ? $ledger->created_at->toISOString() : null,
                ];
            });

        return response()->json([
            'status' => true,
            'total_size' => $totalCount,
            'limit' => $limit,
            'offset' => $offset,
            'ledgers' => $ledgers,
        ], 200);
    }
}

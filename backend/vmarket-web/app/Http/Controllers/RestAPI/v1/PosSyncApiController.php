<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PosSyncApiController extends Controller
{
    /**
     * [AI] Fetch real-time products for POS terminal.
     * Supports both Super Admin in-house catalog and vendor-specific catalogs.
     */
    public function getProducts(Request $request): JsonResponse
    {
        $sellerId = $request->input('seller_id');
        $isAdmin = $request->boolean('is_admin', false);

        $query = Product::query()->active();

        if ($isAdmin || $sellerId === 'admin') {
            $query->where('added_by', 'admin');
        } elseif ($sellerId) {
            $query->where('added_by', 'seller')->where('user_id', $sellerId);
        }

        $products = $query->select([
            'id', 'name', 'code', 'current_stock', 'unit_price', 'purchase_price',
            'tax', 'discount', 'discount_type', 'unit', 'thumbnail', 'status'
        ])->get();

        return response()->json([
            'status' => 'success',
            'total' => $products->count(),
            'products' => $products
        ]);
    }

    /**
     * [AI] Real-time Atomic Stock Adjustment from POS Checkouts.
     * Decrements physical stock in Victorious MARKET with zero drift.
     */
    public function syncStock(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer',
            'items.*.qty' => 'required|numeric',
        ]);

        $items = $request->input('items');
        $updated = [];

        DB::beginTransaction();
        try {
            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $qty = (float) $item['qty'];

                $product = Product::where('id', $productId)->lockForUpdate()->first();
                if ($product) {
                    $newStock = max(0, $product->current_stock - $qty);
                    $product->current_stock = $newStock;
                    $product->save();

                    $updated[] = [
                        'product_id' => $productId,
                        'previous_stock' => $product->current_stock + $qty,
                        'current_stock' => $newStock,
                    ];
                }
            }
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Stock synchronized successfully across Victorious MARKET and POS.',
                'updated' => $updated
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[POS Stock Sync Error] ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * [AI] Confirm In-Store Order Dispatch
     */
    public function confirmDispatch(Request $request, $orderId): JsonResponse
    {
        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Order not found.'], 404);
        }

        $order->order_status = 'out_for_delivery';
        $order->save();

        return response()->json([
            'status' => 'success',
            'message' => "Order #{$orderId} status advanced to out_for_delivery.",
            'order_status' => $order->order_status
        ]);
    }
}

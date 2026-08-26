<?php

namespace App\Http\Controllers\Vendor\Branch;

use App\Http\Controllers\BaseController;
use App\Models\PosSubscription;
use App\Models\PosTransfer;
use App\Models\PosTransferItem;
use App\Models\Product;
use App\Models\Shop;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * [AI] Class BranchTransferController
 * Manages Multi-Branch Waybills, In-Transit Buffers, and Destination Theft Discrepancy Detection.
 */
class BranchTransferController extends BaseController
{
    public function index(): View|RedirectResponse
    {
        $sellerId = auth('seller')->id();

        // Check if vendor has multi-branch access
        $branches = Shop::where('seller_id', $sellerId)->get();
        $activeSub = PosSubscription::where('seller_id', $sellerId)
            ->where('status', 'active')
            ->orderBy('id', 'desc')
            ->first();

        $transfers = PosTransfer::with(['originBranch', 'destinationBranch', 'items.product'])
            ->where('seller_id', $sellerId)
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('vendor-views.branch.transfers', compact('transfers', 'branches', 'activeSub'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'origin_branch_id' => 'required|integer',
            'destination_branch_id' => 'required|integer|different:origin_branch_id',
            'driver_name' => 'required|string',
            'driver_phone' => 'required|string',
            'vehicle_number' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $sellerId = auth('seller')->id();
        $waybillNumber = 'WB-' . strtoupper(Str::random(8));

        DB::transaction(function () use ($request, $sellerId, $waybillNumber) {
            $totalDispatched = 0;

            $transfer = PosTransfer::create([
                'seller_id' => $sellerId,
                'waybill_number' => $waybillNumber,
                'origin_branch_id' => $request->origin_branch_id,
                'destination_branch_id' => $request->destination_branch_id,
                'dispatched_by_id' => auth('seller')->id(),
                'driver_name' => $request->driver_name,
                'driver_phone' => $request->driver_phone,
                'vehicle_number' => $request->vehicle_number,
                'status' => 'in_transit',
                'dispatched_at' => now(),
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $itemData) {
                $product = Product::where('id', $itemData['product_id'])
                    ->where('user_id', $sellerId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $qty = (int)$itemData['quantity'];
                // Deduct from physical origin stock
                $product->current_stock = max(0, $product->current_stock - $qty);
                $product->save();

                PosTransferItem::create([
                    'transfer_id' => $transfer->id,
                    'product_id' => $product->id,
                    'dispatched_quantity' => $qty,
                    'unit_cost' => (float)$product->unit_price,
                ]);

                $totalDispatched += $qty;
            }

            $transfer->total_items_dispatched = $totalDispatched;
            $transfer->save();
        });

        ToastMagic::success(translate('Waybill_created_and_stock_placed_in_transit_buffer!'));
        return back();
    }

    public function receive(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'received_items' => 'required|array|min:1',
            'received_items.*.product_id' => 'required|integer',
            'received_items.*.quantity' => 'required|integer|min:0',
        ]);

        $sellerId = auth('seller')->id();
        $transfer = PosTransfer::with('items')
            ->where('id', $id)
            ->where('seller_id', $sellerId)
            ->firstOrFail();

        if ($transfer->status === 'received' || $transfer->status === 'variance_flagged') {
            ToastMagic::error(translate('Waybill_already_received'));
            return back();
        }

        DB::transaction(function () use ($request, $transfer, $sellerId) {
            $totalReceived = 0;
            $totalVariance = 0;

            foreach ($request->received_items as $itemData) {
                $transferItem = PosTransferItem::where('transfer_id', $transfer->id)
                    ->where('product_id', $itemData['product_id'])
                    ->first();

                if ($transferItem) {
                    $recQty = (int)$itemData['quantity'];
                    $dispQty = $transferItem->dispatched_quantity;
                    $variance = $dispQty - $recQty;

                    $transferItem->received_quantity = $recQty;
                    $transferItem->variance_quantity = $variance;
                    $transferItem->save();

                    // Add only physically verified counted units to destination stock
                    $product = Product::where('id', $itemData['product_id'])
                        ->where('user_id', $sellerId)
                        ->first();

                    if ($product) {
                        $product->current_stock += $recQty;
                        $product->save();
                    }

                    $totalReceived += $recQty;
                    $totalVariance += max(0, $variance);
                }
            }

            $transfer->total_items_received = $totalReceived;
            $transfer->variance_count = $totalVariance;
            $transfer->received_by_id = auth('seller')->id();
            $transfer->received_at = now();
            $transfer->status = $totalVariance > 0 ? 'variance_flagged' : 'received';
            $transfer->save();
        });

        if ($transfer->status === 'variance_flagged') {
            ToastMagic::warning(translate('Transfer_received_with_theft/loss_discrepancies_flagged_on_Audit_Radar!'));
        } else {
            ToastMagic::success(translate('Transfer_verified_and_stocked_successfully!'));
        }

        return back();
    }
}

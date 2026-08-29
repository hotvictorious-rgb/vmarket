<?php

namespace Modules\Delivery\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DeliveryHub;
use App\Models\DeliveryMan;
use App\Models\Order;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Delivery\app\Models\DeliveryBatch;

class ShipmentController extends Controller
{
    /**
     * [AI] Display live shipments and package dispatch pipeline.
     */
    public function index(Request $request): View
    {
        $query = Order::with(['seller.shop', 'delivery_man', 'customer']);

        if ($request->filled('status')) {
            $query->where('order_status', $request->status);
        } else {
            $query->whereIn('order_status', ['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered']);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('id', 'like', "%{$s}%")
                  ->orWhere('recipient_phone', 'like', "%{$s}%")
                  ->orWhere('waybill_slip_no', 'like', "%{$s}%")
                  ->orWhere('batch_dispatch_id', 'like', "%{$s}%");
            });
        }

        $shipments = $query->latest()->paginate(15)->appends($request->all());
        $batches = DeliveryBatch::with(['originHub', 'destinationHub', 'driver'])->latest()->take(10)->get();
        $hubs = DeliveryHub::where('is_active', 1)->get();
        $drivers = DeliveryMan::where('is_active', 1)->get();

        return view('delivery::shipments.index', compact('shipments', 'batches', 'hubs', 'drivers'));
    }

    /**
     * [AI] Create a consolidated linehaul batch for inter-hub transport.
     */
    public function createBatch(Request $request): RedirectResponse
    {
        $request->validate([
            'origin_hub_id' => 'required|exists:delivery_hubs,id',
            'destination_hub_id' => 'required|exists:delivery_hubs,id|different:origin_hub_id',
            'driver_id' => 'nullable|exists:delivery_men,id',
            'vehicle_no' => 'nullable|string|max:50',
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'exists:orders,id',
        ]);

        $batchNo = 'VM-BATCH-' . strtoupper(Str::random(6));
        $transitOtp = (string) rand(100000, 999999);

        $batch = DeliveryBatch::create([
            'batch_no' => $batchNo,
            'origin_hub_id' => $request->origin_hub_id,
            'destination_hub_id' => $request->destination_hub_id,
            'driver_id' => $request->driver_id,
            'vehicle_no' => $request->vehicle_no,
            'package_count' => count($request->order_ids),
            'transit_otp' => $transitOtp,
            'status' => 'dispatched',
            'dispatched_at' => now(),
        ]);

        // Link orders and assign batch_dispatch_id
        $batch->orders()->attach($request->order_ids, ['status' => 'loaded']);
        Order::whereIn('id', $request->order_ids)->update([
            'batch_dispatch_id' => $batchNo,
            'driver_transit_code' => $transitOtp,
            'order_status' => 'processing',
        ]);

        Toastr::success("Linehaul Batch #{$batchNo} generated with {$batch->package_count} packages!");
        return redirect()->route('delivery.shipments.waybill', ['id' => $batch->id]);
    }

    /**
     * [AI] Show Printable Barcode Waybill and Manifest for Batch.
     */
    public function waybill(int $id): View
    {
        $batch = DeliveryBatch::with(['originHub.city.state', 'destinationHub.city.state', 'driver', 'orders.customer', 'orders.seller.shop'])
            ->findOrFail($id);

        return view('delivery::shipments.waybill', compact('batch'));
    }
}

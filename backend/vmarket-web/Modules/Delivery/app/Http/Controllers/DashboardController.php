<?php

namespace Modules\Delivery\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DeliveryHub;
use App\Models\DeliveryMan;
use App\Models\DeliverymanWallet;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Delivery\app\Models\Delivery3plCompany;
use Modules\Delivery\app\Models\DeliveryBatch;
use Modules\Delivery\app\Models\DeliveryRoute;

class DashboardController extends Controller
{
    /**
     * [AI] Delivery & Fleet Logistics Hub Master Command Center
     */
    public function index(Request $request): View
    {
        // 1. Live Logistics KPI Aggregations
        $activeShipmentsCount = Order::whereIn('order_status', ['confirmed', 'processing', 'out_for_delivery'])->count();
        $deliveredTodayCount = Order::where('order_status', 'delivered')->whereDate('updated_at', today())->count();
        $activeLinehaulsCount = DeliveryBatch::whereIn('status', ['dispatched', 'in_transit'])->count();
        $activeCouriersCount = DeliveryMan::where('is_active', 1)->count();
        $activeHubsCount = DeliveryHub::where('is_active', 1)->count();
        $activeRoutesCount = DeliveryRoute::where('is_active', 1)->count();

        // 2. Real-Time Cash-in-Hand Total in Transit
        $cashInHandTotal = (float) DeliverymanWallet::sum('cash_in_hand');

        // 3. Active Linehaul Batches
        $recentBatches = DeliveryBatch::with(['originHub', 'destinationHub', 'driver'])
            ->latest()
            ->take(5)
            ->get();

        // 4. Live Out-for-Delivery Orders
        $liveDeliveries = Order::whereIn('order_status', ['processing', 'out_for_delivery'])
            ->with(['delivery_man', 'seller.shop', 'customer'])
            ->latest()
            ->take(10)
            ->get();

        // 5. Corridor Performance Matrix
        $topRoutes = DeliveryRoute::with(['originHub', 'destinationHub'])
            ->where('is_active', 1)
            ->take(6)
            ->get();

        return view('delivery::dashboard', compact(
            'activeShipmentsCount',
            'deliveredTodayCount',
            'activeLinehaulsCount',
            'activeCouriersCount',
            'activeHubsCount',
            'activeRoutesCount',
            'cashInHandTotal',
            'recentBatches',
            'liveDeliveries',
            'topRoutes'
        ));
    }
}

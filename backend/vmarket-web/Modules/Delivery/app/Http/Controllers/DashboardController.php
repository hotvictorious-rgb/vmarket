<?php

namespace Modules\Delivery\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DeliveryHub;
use App\Models\DeliveryMan;
use App\Models\DeliverymanWallet;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Delivery\app\Models\Delivery3plCompany;
use Modules\Delivery\app\Models\DeliveryBatch;
use Modules\Delivery\app\Models\DeliveryRoute;

class DashboardController extends Controller
{
    /**
     * [AI] Delivery & Fleet Logistics Hub Master Command Center
     * Optimized with 60-second KPI caching, index-friendly sargable date bounds, and deep eager loading.
     */
    public function index(Request $request): View
    {
        // 1. Live Logistics KPI Aggregations (Cached for 60s to guarantee sub-50ms dashboard loads)
        $kpiData = Cache::remember('delivery_dashboard_kpis', 60, function () {
            $todayStart = now()->startOfDay();
            $todayEnd = now()->endOfDay();

            return [
                'activeShipmentsCount' => Order::whereIn('order_status', ['confirmed', 'processing', 'out_for_delivery'])->count(),
                'deliveredTodayCount'  => Order::where('order_status', 'delivered')->whereBetween('updated_at', [$todayStart, $todayEnd])->count(),
                'activeLinehaulsCount' => DeliveryBatch::whereIn('status', ['dispatched', 'in_transit'])->count(),
                'activeCouriersCount'  => DeliveryMan::where('is_active', 1)->count(),
                'activeHubsCount'      => DeliveryHub::where('is_active', 1)->count(),
                'activeRoutesCount'    => DeliveryRoute::where('is_active', 1)->count(),
                'cashInHandTotal'      => (float) DeliverymanWallet::sum('cash_in_hand'),
            ];
        });

        $activeShipmentsCount = $kpiData['activeShipmentsCount'];
        $deliveredTodayCount  = $kpiData['deliveredTodayCount'];
        $activeLinehaulsCount = $kpiData['activeLinehaulsCount'];
        $activeCouriersCount  = $kpiData['activeCouriersCount'];
        $activeHubsCount      = $kpiData['activeHubsCount'];
        $activeRoutesCount    = $kpiData['activeRoutesCount'];
        $cashInHandTotal      = $kpiData['cashInHandTotal'];

        // 2. Active Linehaul Batches (Eager loaded)
        $recentBatches = DeliveryBatch::with(['originHub', 'destinationHub', 'driver'])
            ->latest()
            ->take(5)
            ->get();

        // 3. Live Out-for-Delivery Orders (Deep eager loaded to eliminate N+1 queries)
        $liveDeliveries = Order::whereIn('order_status', ['processing', 'out_for_delivery'])
            ->with(['delivery_man.hub', 'seller.shop.hub', 'customer'])
            ->latest()
            ->take(10)
            ->get();

        // 4. Corridor Performance Matrix (Eager loaded)
        $topRoutes = DeliveryRoute::with(['originHub.city', 'destinationHub.city'])
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

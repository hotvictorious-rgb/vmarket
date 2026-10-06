<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\DeliveryMan;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $company = Auth::guard('logistics')->user();
        $companyId = $company->id;

        // Wallet metrics
        $wallet = $company->wallet ?? null;
        $currentBalance = $wallet ? (float)$wallet->current_balance : 0.00;
        $totalEarned = $wallet ? (float)$wallet->total_earned : 0.00;
        $pendingWithdraw = $wallet ? (float)$wallet->pending_withdraw : 0.00;

        // Fleet metrics
        $totalRiders = DeliveryMan::where('logistics_company_id', $companyId)->count();
        $activeOnlineRiders = DeliveryMan::where('logistics_company_id', $companyId)
            ->where('is_active', 1)
            ->where('is_online', 1)
            ->count();

        // Orders metrics
        $deliveriesToday = Order::where('logistics_company_id', $companyId)
            ->where('order_status', 'delivered')
            ->whereDate('updated_at', today())
            ->count();

        $activeDispatches = Order::where('logistics_company_id', $companyId)
            ->whereIn('order_status', ['confirmed', 'processing', 'out_for_delivery'])
            ->count();

        $recentOrders = Order::where('logistics_company_id', $companyId)
            ->with(['deliveryMan', 'customer'])
            ->latest()
            ->take(8)
            ->get();

        return view('logistics-views.dashboard', compact(
            'company',
            'currentBalance',
            'totalEarned',
            'pendingWithdraw',
            'totalRiders',
            'activeOnlineRiders',
            'deliveriesToday',
            'activeDispatches',
            'recentOrders'
        ));
    }
}

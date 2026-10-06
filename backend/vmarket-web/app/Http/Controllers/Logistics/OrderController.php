<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\DeliveryMan;
use App\Models\Order;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Auth::guard('logistics')->id();
        $status = $request->get('status', 'all');
        $searchValue = $request->get('searchValue');

        $query = Order::where('logistics_company_id', $companyId)
            ->with(['deliveryMan', 'customer', 'seller.shop']);

        if ($status !== 'all') {
            if ($status === 'ongoing') {
                $query->whereIn('order_status', ['confirmed', 'processing', 'out_for_delivery']);
            } elseif ($status === 'delivered') {
                $query->where('order_status', 'delivered');
            } elseif ($status === 'canceled') {
                $query->whereIn('order_status', ['canceled', 'failed', 'returned']);
            }
        }

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('id', 'like', "%{$searchValue}%")
                    ->orWhereHas('customer', function ($c) use ($searchValue) {
                        $c->where('f_name', 'like', "%{$searchValue}%")
                            ->orWhere('l_name', 'like', "%{$searchValue}%")
                            ->orWhere('phone', 'like', "%{$searchValue}%");
                    });
            });
        }

        $orders = $query->latest()->paginate(20);
        $companyRiders = DeliveryMan::where('logistics_company_id', $companyId)->where('is_active', 1)->get();

        return view('logistics-views.orders.index', compact('orders', 'status', 'searchValue', 'companyRiders'));
    }

    public function show($id): View
    {
        $companyId = Auth::guard('logistics')->id();
        $order = Order::where('id', $id)
            ->where('logistics_company_id', $companyId)
            ->with(['deliveryMan', 'customer', 'seller.shop', 'details.product'])
            ->firstOrFail();

        $companyRiders = DeliveryMan::where('logistics_company_id', $companyId)->where('is_active', 1)->get();

        return view('logistics-views.orders.show', compact('order', 'companyRiders'));
    }

    public function assignRider(Request $request): RedirectResponse
    {
        $companyId = Auth::guard('logistics')->id();

        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'delivery_man_id' => 'required|exists:delivery_men,id',
        ]);

        DB::beginTransaction();
        try {
            $order = Order::where('id', $request->order_id)
                ->where('logistics_company_id', $companyId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->order_status === 'delivered') {
                DB::rollBack();
                ToastMagic::warning(translate('Order_is_already_delivered'));
                return redirect()->back();
            }

            $rider = DeliveryMan::where('id', $request->delivery_man_id)
                ->where('logistics_company_id', $companyId)
                ->where('is_active', 1)
                ->lockForUpdate()
                ->firstOrFail();

            // Vehicle compatibility check for bulky large orders
            if ($order->package_tier === 'large' && $rider->vehicle_type === 'motorbike') {
                DB::rollBack();
                ToastMagic::warning(translate('Notice:_This_is_a_Large_Bulky_package._Assigning_a_van_or_truck_is_strongly_recommended!'));
            }

            // Ensure OTP exists
            if (empty($order->pickup_verification_code)) {
                $order->pickup_verification_code = (string) random_int(100000, 999999);
            }
            if (empty($order->verification_code)) {
                $order->verification_code = (string) random_int(100000, 999999);
            }

            $order->delivery_man_id = $rider->id;
            $order->deliveryman_assigned_at = now();
            $order->save();

            DB::commit();
            ToastMagic::success(translate("Order_assigned_to_{$rider->f_name}_{$rider->l_name}_successfully"));
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            ToastMagic::error(translate('Failed_to_assign_rider:_') . $e->getMessage());
            return redirect()->back();
        }
    }
}

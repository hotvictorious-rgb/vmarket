<?php

namespace Modules\Delivery\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DeliveryHub;
use App\Models\DeliveryMan;
use App\Models\DeliverymanWallet;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Delivery\app\Models\Delivery3plCompany;

class FleetController extends Controller
{
    /**
     * [AI] Display list of couriers & 3PL logistics partners.
     */
    public function index(Request $request): View
    {
        $courierQuery = DeliveryMan::with(['wallet', 'hub.city']);

        if ($request->filled('search')) {
            $s = $request->search;
            $courierQuery->where(function ($q) use ($s) {
                $q->where('f_name', 'like', "%{$s}%")
                  ->orWhere('l_name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        if ($request->filled('hub_id')) {
            $courierQuery->where('delivery_hub_id', $request->hub_id);
        }

        $couriers = $courierQuery->latest()->paginate(15)->appends($request->all());
        $companies = Delivery3plCompany::withCount('riders')->latest()->get();
        $hubs = DeliveryHub::where('is_active', 1)->get();

        return view('delivery::fleet.index', compact('couriers', 'companies', 'hubs'));
    }

    /**
     * [AI] Store a new 3PL Courier Company partner.
     */
    public function storeCompany(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:50|unique:delivery_3pl_companies,code',
            'contact_person' => 'required|string|max:100',
            'phone' => 'required|string|max:50|unique:delivery_3pl_companies,phone',
            'email' => 'nullable|email|max:150|unique:delivery_3pl_companies,email',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'address' => 'nullable|string|max:255',
        ]);

        Delivery3plCompany::create([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'contact_person' => $request->contact_person,
            'phone' => $request->phone,
            'email' => $request->email,
            'commission_rate' => $request->commission_rate,
            'address' => $request->address,
            'status' => 'approved',
        ]);

        Toastr::success('3PL Logistics partner company registered successfully!');
        return redirect()->route('delivery.fleet.index');
    }

    /**
     * [AI] Show rider performance and Cash-in-Hand details.
     */
    public function showRider(int $id): View
    {
        $courier = DeliveryMan::with(['wallet', 'hub.city'])->findOrFail($id);
        return view('delivery::fleet.show-rider', compact('courier'));
    }
}

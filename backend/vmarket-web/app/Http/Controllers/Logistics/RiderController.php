<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\DeliveryMan;
use App\Models\Lga;
use App\Models\State;
use App\Traits\StorageTrait;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RiderController extends Controller
{
    use StorageTrait;

    public function index(Request $request): View
    {
        $companyId = Auth::guard('logistics')->id();
        $searchValue = $request->get('searchValue');

        $query = DeliveryMan::where('logistics_company_id', $companyId)
            ->withCount(['orders' => function ($q) {
                $q->whereIn('order_status', ['confirmed', 'processing', 'out_for_delivery']);
            }]);

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('f_name', 'like', "%{$searchValue}%")
                    ->orWhere('l_name', 'like', "%{$searchValue}%")
                    ->orWhere('phone', 'like', "%{$searchValue}%")
                    ->orWhere('email', 'like', "%{$searchValue}%");
            });
        }

        $riders = $query->latest()->paginate(20);

        return view('logistics-views.riders.index', compact('riders', 'searchValue'));
    }

    public function create(): View
    {
        $states = State::active()->orderBy('name')->get();
        $lgas = Lga::active()->orderBy('name')->get();
        return view('logistics-views.riders.create', compact('states', 'lgas'));
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Auth::guard('logistics')->id();

        $request->validate([
            'f_name' => 'required|string|max:100',
            'l_name' => 'required|string|max:100',
            'phone' => 'required|string|max:30|unique:delivery_men,phone',
            'email' => 'required|email|max:100|unique:delivery_men,email',
            'password' => 'required|string|min:6',
            'vehicle_type' => 'required|in:motorbike,tricycle,van,truck',
            'address' => 'nullable|string|max:300',
            'identity_type' => 'nullable|string|max:50',
            'identity_number' => 'nullable|string|max:50',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        $imageName = null;
        if ($request->hasFile('image')) {
            $imageName = $this->upload(dir: 'delivery-man/', format: 'webp', image: $request->file('image'));
        }

        DeliveryMan::create([
            'seller_id' => 0,
            'logistics_company_id' => $companyId,
            'f_name' => $request->f_name,
            'l_name' => $request->l_name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'vehicle_type' => $request->vehicle_type,
            'address' => $request->address,
            'identity_type' => $request->identity_type,
            'identity_number' => $request->identity_number,
            'image' => $imageName,
            'is_active' => 1,
            'is_online' => 0,
            'max_active_orders_limit' => $request->vehicle_type === 'motorbike' ? 4 : 8,
        ]);

        ToastMagic::success(translate('Rider_added_to_fleet_successfully'));
        return redirect()->route('logistics.riders.index');
    }

    public function edit($id): View
    {
        $companyId = Auth::guard('logistics')->id();
        $rider = DeliveryMan::where('id', $id)
            ->where('logistics_company_id', $companyId)
            ->firstOrFail();

        return view('logistics-views.riders.edit', compact('rider'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $companyId = Auth::guard('logistics')->id();
        $rider = DeliveryMan::where('id', $id)
            ->where('logistics_company_id', $companyId)
            ->firstOrFail();

        $request->validate([
            'f_name' => 'required|string|max:100',
            'l_name' => 'required|string|max:100',
            'phone' => 'required|string|max:30|unique:delivery_men,phone,' . $id,
            'email' => 'required|email|max:100|unique:delivery_men,email,' . $id,
            'password' => 'nullable|string|min:6',
            'vehicle_type' => 'required|in:motorbike,tricycle,van,truck',
            'address' => 'nullable|string|max:300',
            'identity_type' => 'nullable|string|max:50',
            'identity_number' => 'nullable|string|max:50',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $rider->image = $this->upload(dir: 'delivery-man/', format: 'webp', image: $request->file('image'));
        }

        $rider->f_name = $request->f_name;
        $rider->l_name = $request->l_name;
        $rider->phone = $request->phone;
        $rider->email = $request->email;
        if ($request->filled('password')) {
            $rider->password = Hash::make($request->password);
        }
        $rider->vehicle_type = $request->vehicle_type;
        $rider->address = $request->address;
        $rider->identity_type = $request->identity_type;
        $rider->identity_number = $request->identity_number;
        $rider->save();

        ToastMagic::success(translate('Rider_information_updated_successfully'));
        return redirect()->route('logistics.riders.index');
    }

    public function status(Request $request): JsonResponse
    {
        $companyId = Auth::guard('logistics')->id();
        $rider = DeliveryMan::where('id', $request->id)
            ->where('logistics_company_id', $companyId)
            ->firstOrFail();

        $rider->is_active = (int) $request->status;
        $rider->save();

        return response()->json([
            'success' => true,
            'message' => translate('Rider_status_updated_successfully'),
        ]);
    }
}

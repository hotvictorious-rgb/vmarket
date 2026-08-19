<?php

namespace App\Http\Controllers\Vendor\Employee;

use App\Http\Controllers\Controller;
use App\Models\VendorEmployee;
use App\Models\VendorRole;
use App\Traits\FileManagerTrait;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class VendorEmployeeController extends Controller
{
    use FileManagerTrait;

    public function list(Request $request): View
    {
        $sellerId = auth('seller')->id();
        $searchValue = $request->get('searchValue');

        $employees = VendorEmployee::with('role')
            ->where('seller_id', $sellerId)
            ->when($searchValue, function ($query) use ($searchValue) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('name', 'like', "%{$searchValue}%")
                      ->orWhere('email', 'like', "%{$searchValue}%")
                      ->orWhere('phone', 'like', "%{$searchValue}%");
                });
            })
            ->latest()
            ->paginate(10);

        return view('vendor-views.employee.list', compact('employees', 'searchValue'));
    }

    public function addNew(): View|RedirectResponse
    {
        $sellerId = auth('seller')->id();
        $roles = VendorRole::where('seller_id', $sellerId)->where('status', true)->get();

        if ($roles->isEmpty()) {
            ToastMagic::warning(translate('Please create an employee role first before adding staff members'));
            return redirect()->route('vendor.employee-role.index');
        }

        return view('vendor-views.employee.add-new', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $sellerId = auth('seller')->id();

        $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:30',
            'email' => 'required|email|unique:vendor_employees,email',
            'password' => 'required|string|min:8|same:confirm_password',
            'vendor_role_id' => 'required|exists:vendor_roles,id',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'vendor_role_id.required' => translate('Please select an employee role'),
            'password.min' => translate('Password must be at least 8 characters'),
        ]);

        $role = VendorRole::where('seller_id', $sellerId)->where('id', $request->vendor_role_id)->first();
        if (!$role) {
            ToastMagic::error(translate('Invalid employee role selected'));
            return redirect()->back();
        }

        $imageName = $request->hasFile('image') 
            ? $this->upload(dir: 'vendor-employee/', format: 'webp', image: $request->file('image'))
            : null;

        VendorEmployee::create([
            'seller_id' => $sellerId,
            'vendor_role_id' => $request->vendor_role_id,
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'image' => $imageName,
            'status' => true,
        ]);

        ToastMagic::success(translate('Shop employee added successfully'));
        return redirect()->route('vendor.employee.list');
    }

    public function edit(int|string $id): View|RedirectResponse
    {
        $sellerId = auth('seller')->id();
        $employee = VendorEmployee::where('seller_id', $sellerId)->find($id);

        if (!$employee) {
            ToastMagic::error(translate('Employee not found'));
            return redirect()->route('vendor.employee.list');
        }

        $roles = VendorRole::where('seller_id', $sellerId)->where('status', true)->get();
        return view('vendor-views.employee.edit', compact('employee', 'roles'));
    }

    public function update(Request $request, int|string $id): RedirectResponse
    {
        $sellerId = auth('seller')->id();
        $employee = VendorEmployee::where('seller_id', $sellerId)->find($id);

        if (!$employee) {
            ToastMagic::error(translate('Employee not found'));
            return redirect()->route('vendor.employee.list');
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:30',
            'email' => 'required|email|unique:vendor_employees,email,' . $employee->id,
            'vendor_role_id' => 'required|exists:vendor_roles,id',
            'password' => 'nullable|string|min:8|same:confirm_password',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // [AI] Ownership Guard: Role must belong to this vendor
        $role = VendorRole::where('seller_id', $sellerId)->where('id', $request->vendor_role_id)->first();
        if (!$role) {
            ToastMagic::error(translate('Invalid employee role selected'));
            return redirect()->back();
        }

        $data = [
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'vendor_role_id' => $request->vendor_role_id,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('image')) {
            $data['image'] = $this->update(dir: 'vendor-employee/', oldImage: $employee->image, format: 'webp', image: $request->file('image'));
        }

        $employee->update($data);

        ToastMagic::success(translate('Employee details updated successfully'));
        return redirect()->route('vendor.employee.list');
    }

    public function status(Request $request): JsonResponse
    {
        $sellerId = auth('seller')->id();
        $employee = VendorEmployee::where('seller_id', $sellerId)->find($request->id);

        if (!$employee) {
            return response()->json(['success' => false, 'message' => translate('Employee not found')], 404);
        }

        $employee->status = $request->status;
        $employee->save();

        return response()->json(['success' => true, 'message' => translate('Employee status updated')]);
    }
}

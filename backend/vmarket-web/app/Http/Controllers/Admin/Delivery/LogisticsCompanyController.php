<?php

namespace App\Http\Controllers\Admin\Delivery;

use App\Http\Controllers\Controller;
use App\Models\DeliveryMan;
use App\Models\Lga;
use App\Models\LogisticsCompany;
use App\Models\LogisticsCompanyTransaction;
use App\Models\LogisticsCompanyWallet;
use App\Models\LogisticsCompanyWithdrawRequest;
use App\Models\State;
use App\Traits\StorageTrait;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LogisticsCompanyController extends Controller
{
    use StorageTrait;

    /**
     * Display a listing of logistics partners.
     */
    public function index(Request $request): View
    {
        $searchValue = $request->get('searchValue');
        $status = $request->get('status');

        $query = LogisticsCompany::with(['state', 'lga', 'wallet'])
            ->withCount(['deliveryMen', 'orders']);

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                    ->orWhere('company_email', 'like', "%{$searchValue}%")
                    ->orWhere('company_phone', 'like', "%{$searchValue}%")
                    ->orWhere('contact_person_name', 'like', "%{$searchValue}%");
            });
        }

        if (!empty($status) && in_array($status, ['active', 'pending', 'suspended', 'rejected'])) {
            $query->where('status', $status);
        }

        $companies = $query->latest()->paginate(25);

        return view('admin-views.delivery.logistics-companies.index', compact('companies', 'searchValue', 'status'));
    }

    /**
     * Show the form for creating a new logistics company.
     */
    public function create(): View
    {
        $states = State::active()->orderBy('name')->get();
        $lgas = Lga::active()->orderBy('name')->get();

        return view('admin-views.delivery.logistics-companies.create', compact('states', 'lgas'));
    }

    /**
     * Store a newly created logistics company in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'company_email' => 'required|email|max:191|unique:logistics_companies,company_email',
            'company_phone' => 'required|string|max:50',
            'password' => 'required|string|min:8',
            'contact_person_name' => 'nullable|string|max:191',
            'contact_person_phone' => 'nullable|string|max:50',
            'cac_number' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'state_id' => 'nullable|exists:states,id',
            'lga_id' => 'nullable|exists:lgas,id',
            'operating_lgas' => 'nullable|array',
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'account_name' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        DB::beginTransaction();
        try {
            $logoName = null;
            if ($request->hasFile('logo')) {
                $logoName = $this->upload(dir: 'logistics/logo/', format: 'webp', image: $request->file('logo'));
            }

            $company = LogisticsCompany::create([
                'name' => $request->name,
                'company_email' => $request->company_email,
                'company_phone' => $request->company_phone,
                'contact_person_name' => $request->contact_person_name,
                'contact_person_phone' => $request->contact_person_phone,
                'password' => Hash::make($request->password),
                'cac_number' => $request->cac_number,
                'address' => $request->address,
                'state_id' => $request->state_id,
                'lga_id' => $request->lga_id,
                'operating_lgas' => $request->operating_lgas,
                'bank_name' => $request->bank_name,
                'account_number' => $request->account_number,
                'account_name' => $request->account_name,
                'logo' => $logoName,
                'status' => 'active',
                'is_active' => 1,
            ]);

            // Create initial wallet
            LogisticsCompanyWallet::create([
                'logistics_company_id' => $company->id,
                'total_earned' => 0.00,
                'withdrawn' => 0.00,
                'pending_withdraw' => 0.00,
                'current_balance' => 0.00,
            ]);

            DB::commit();
            ToastMagic::success(translate('Logistics_company_registered_successfully'));
            return redirect()->route('admin.logistics-companies.index');
        } catch (\Exception $e) {
            DB::rollBack();
            ToastMagic::error(translate('Failed_to_create_company:_') . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Display company details, fleet, and financial transactions.
     */
    public function show($id): View
    {
        $company = LogisticsCompany::with(['state', 'lga', 'wallet', 'deliveryMen'])
            ->findOrFail($id);

        $transactions = LogisticsCompanyTransaction::where('logistics_company_id', $id)
            ->with(['order', 'deliveryMan'])
            ->latest()
            ->paginate(20, ['*'], 'tx_page');

        $withdrawRequests = LogisticsCompanyWithdrawRequest::where('logistics_company_id', $id)
            ->latest()
            ->paginate(15, ['*'], 'withdraw_page');

        return view('admin-views.delivery.logistics-companies.show', compact('company', 'transactions', 'withdrawRequests'));
    }

    /**
     * Show the form for editing the specified company.
     */
    public function edit($id): View
    {
        $company = LogisticsCompany::findOrFail($id);
        $states = State::active()->orderBy('name')->get();
        $lgas = Lga::active()->orderBy('name')->get();

        return view('admin-views.delivery.logistics-companies.edit', compact('company', 'states', 'lgas'));
    }

    /**
     * Update company details.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $company = LogisticsCompany::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:191',
            'company_email' => 'required|email|max:191|unique:logistics_companies,company_email,' . $id,
            'company_phone' => 'required|string|max:50',
            'password' => 'nullable|string|min:8',
            'contact_person_name' => 'nullable|string|max:191',
            'contact_person_phone' => 'nullable|string|max:50',
            'cac_number' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'state_id' => 'nullable|exists:states,id',
            'lga_id' => 'nullable|exists:lgas,id',
            'operating_lgas' => 'nullable|array',
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'account_name' => 'nullable|string|max:100',
            'status' => 'required|in:active,pending,suspended,rejected',
            'logo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            $company->logo = $this->upload(dir: 'logistics/logo/', format: 'webp', image: $request->file('logo'));
        }

        $company->name = $request->name;
        $company->company_email = $request->company_email;
        $company->company_phone = $request->company_phone;
        $company->contact_person_name = $request->contact_person_name;
        $company->contact_person_phone = $request->contact_person_phone;
        if ($request->filled('password')) {
            $company->password = Hash::make($request->password);
        }
        $company->cac_number = $request->cac_number;
        $company->address = $request->address;
        $company->state_id = $request->state_id;
        $company->lga_id = $request->lga_id;
        $company->operating_lgas = $request->operating_lgas;
        $company->bank_name = $request->bank_name;
        $company->account_number = $request->account_number;
        $company->account_name = $request->account_name;
        $company->status = $request->status;
        $company->is_active = ($request->status === 'active') ? 1 : 0;
        $company->save();

        ToastMagic::success(translate('Company_details_updated_successfully'));
        return redirect()->route('admin.logistics-companies.show', $company->id);
    }

    /**
     * Toggle company active status via AJAX.
     */
    public function statusUpdate(Request $request): JsonResponse
    {
        $company = LogisticsCompany::findOrFail($request->id);
        $company->is_active = (int) $request->status;
        $company->status = $company->is_active ? 'active' : 'suspended';
        $company->save();

        return response()->json([
            'success' => true,
            'message' => translate('Status_updated_successfully'),
        ]);
    }

    /**
     * View all withdrawal requests from logistics companies.
     */
    public function withdrawRequests(Request $request): View
    {
        $status = $request->get('status', 'all');

        $query = LogisticsCompanyWithdrawRequest::with(['company.wallet']);

        if ($status !== 'all' && in_array($status, ['pending', 'approved', 'denied'])) {
            $query->where('status', $status);
        }

        $requests = $query->latest()->paginate(25);

        return view('admin-views.delivery.logistics-companies.withdraw-requests', compact('requests', 'status'));
    }

    /**
     * Process withdrawal status (Approve / Deny) with pessimistic lock.
     */
    public function withdrawStatus(Request $request): RedirectResponse
    {
        $request->validate([
            'id' => 'required|exists:logistics_company_withdraw_requests,id',
            'status' => 'required|in:approved,denied',
            'admin_note' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $withdraw = LogisticsCompanyWithdrawRequest::where('id', $request->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($withdraw->status !== 'pending') {
                DB::rollBack();
                ToastMagic::warning(translate('Withdrawal_request_has_already_been_processed'));
                return redirect()->back();
            }

            $wallet = LogisticsCompanyWallet::where('logistics_company_id', $withdraw->logistics_company_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($request->status === 'approved') {
                $withdraw->status = 'approved';
                $withdraw->admin_note = $request->admin_note;
                $withdraw->approved_at = now();
                $withdraw->save();

                $wallet->pending_withdraw = max(0, round((float)$wallet->pending_withdraw - (float)$withdraw->amount, 2));
                $wallet->withdrawn = round((float)$wallet->withdrawn + (float)$withdraw->amount, 2);
                $wallet->save();

                LogisticsCompanyTransaction::create([
                    'logistics_company_id' => $withdraw->logistics_company_id,
                    'gross_delivery_fee' => $withdraw->amount,
                    'net_partner_amount' => $withdraw->amount,
                    'transaction_type' => 'withdrawal',
                    'balance_before' => (float)$wallet->current_balance,
                    'balance_after' => (float)$wallet->current_balance,
                    'transaction_note' => "Bank payout approved of ₦{$withdraw->amount} to {$withdraw->bank_name} ({$withdraw->account_number})",
                ]);
            } else {
                // Denied: refund back to current balance
                $withdraw->status = 'denied';
                $withdraw->admin_note = $request->admin_note;
                $withdraw->save();

                $wallet->pending_withdraw = max(0, round((float)$wallet->pending_withdraw - (float)$withdraw->amount, 2));
                $wallet->current_balance = round((float)$wallet->current_balance + (float)$withdraw->amount, 2);
                $wallet->save();
            }

            DB::commit();
            ToastMagic::success(translate('Withdrawal_request_processed_successfully'));
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            ToastMagic::error(translate('Failed_to_process_request:_') . $e->getMessage());
            return redirect()->back();
        }
    }
}

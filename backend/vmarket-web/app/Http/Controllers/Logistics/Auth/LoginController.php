<?php

namespace App\Http\Controllers\Logistics\Auth;

use App\Http\Controllers\Controller;
use App\Models\Lga;
use App\Models\LogisticsCompany;
use App\Models\LogisticsCompanyWallet;
use App\Models\State;
use App\Traits\StorageTrait;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    use StorageTrait;

    public function __construct()
    {
        $this->middleware('guest:logistics', ['except' => ['logout']]);
    }

    public function login(): View
    {
        return view('logistics-views.auth.login');
    }

    public function submitLogin(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        $credentials = [
            'company_email' => $request->email,
            'password' => $request->password,
        ];

        if (Auth::guard('logistics')->attempt($credentials, $request->filled('remember'))) {
            $company = Auth::guard('logistics')->user();

            if ($company->status === 'suspended' || !$company->is_active) {
                Auth::guard('logistics')->logout();
                ToastMagic::error(translate('Your_logistics_company_account_is_suspended._Please_contact_support.'));
                return redirect()->back();
            }

            if ($company->status === 'pending') {
                Auth::guard('logistics')->logout();
                ToastMagic::warning(translate('Your_application_is_under_review_by_Victorious_Market_compliance.'));
                return redirect()->back();
            }

            ToastMagic::success(translate('Welcome_back,_') . $company->name);
            return redirect()->route('logistics.dashboard');
        }

        ToastMagic::error(translate('Invalid_login_credentials'));
        return redirect()->back()->withInput(['email' => $request->email]);
    }

    public function register(): View
    {
        $states = State::active()->orderBy('name')->get();
        $lgas = Lga::active()->orderBy('name')->get();
        return view('logistics-views.auth.register', compact('states', 'lgas'));
    }

    public function submitRegister(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'company_email' => 'required|email|max:191|unique:logistics_companies,company_email',
            'company_phone' => 'required|string|max:50',
            'password' => 'required|string|min:8|confirmed',
            'contact_person_name' => 'required|string|max:191',
            'contact_person_phone' => 'required|string|max:50',
            'cac_number' => 'nullable|string|max:100',
            'address' => 'required|string|max:500',
            'state_id' => 'required|exists:states,id',
            'lga_id' => 'required|exists:lgas,id',
            'bank_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:100',
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
                'operating_lgas' => [$request->lga_id],
                'bank_name' => $request->bank_name,
                'account_number' => $request->account_number,
                'account_name' => $request->account_name,
                'logo' => $logoName,
                'status' => 'pending', // Requires admin review
                'is_active' => 1,
            ]);

            LogisticsCompanyWallet::create([
                'logistics_company_id' => $company->id,
                'total_earned' => 0.00,
                'withdrawn' => 0.00,
                'pending_withdraw' => 0.00,
                'current_balance' => 0.00,
            ]);

            DB::commit();
            ToastMagic::success(translate('Registration_submitted_successfully!_Our_fleet_team_will_review_and_activate_your_portal.'));
            return redirect()->route('logistics.auth.login');
        } catch (\Exception $e) {
            DB::rollBack();
            ToastMagic::error(translate('Registration_failed:_') . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function logout(): RedirectResponse
    {
        Auth::guard('logistics')->logout();
        ToastMagic::info(translate('Logged_out_successfully'));
        return redirect()->route('logistics.auth.login');
    }
}

<?php

namespace App\Http\Controllers\Vendor\Auth;

use App\Enums\SessionKey;
use App\Traits\RecaptchaTrait;
use App\Services\VendorService;
use App\Services\RecaptchaService;
use Illuminate\Contracts\View\View;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use App\Http\Requests\Vendor\LoginRequest;
use App\Repositories\VendorWalletRepository;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use App\Contracts\Repositories\VendorRepositoryInterface;

class LoginController extends Controller
{
    use RecaptchaTrait;

    public function __construct(
        private readonly VendorRepositoryInterface $vendorRepo,
        private readonly VendorService             $vendorService,
        private readonly VendorWalletRepository    $vendorWalletRepo,

    )
    {
        $this->middleware('guest:seller', ['except' => ['logout']]);
    }

    public function getLoginView(): View
    {
        $recaptchaBuilder = $this->generateDefaultReCaptcha(4);
        $recaptcha = getWebConfig(name: 'recaptcha');
        Session::put(SessionKey::VENDOR_RECAPTCHA_KEY, $recaptchaBuilder->getPhrase());
        return view('vendor-views.auth.login', compact('recaptchaBuilder', 'recaptcha'));
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $result = RecaptchaService::verificationStatus(request: $request, session: SessionKey::VENDOR_RECAPTCHA_KEY, action: "login");
        if ($result && !$result['status']) {
            ToastMagic::error($result['message']);
            return back();
        }

        $vendor = $this->vendorRepo->getFirstWhere(['identity' => $request['email']]);
        if (!$vendor) {
            // Check if login is by a Vendor Employee
            $employee = \App\Models\VendorEmployee::with('role', 'seller')
                ->where('email', $request['email'])
                ->first();

            if ($employee && Hash::check($request['password'], $employee->password)) {
                if (!$employee->status) {
                    ToastMagic::error(translate('Your employee account has been deactivated by the shop owner.'));
                    return back();
                }

                if (!$employee->seller || $employee->seller->status !== 'approved') {
                    ToastMagic::error(translate('The associated vendor shop is not approved yet.'));
                    return back();
                }

                // Authenticate under the parent seller shop context
                auth('seller')->loginUsingId($employee->seller_id, $request->remember ?? false);

                // Set employee sub-account session context
                session([
                    'is_vendor_employee' => true,
                    'vendor_employee_data' => $employee->toArray(),
                    'vendor_employee_role' => $employee->role ? $employee->role->toArray() : [],
                ]);

                ToastMagic::info(translate('Welcome back, ') . $employee->name . ' (' . ($employee->role->name ?? translate('Staff')) . ')');
                return redirect()->route('vendor.dashboard.index');
            }

            ToastMagic::error(translate('credentials_doesnt_match') . '!');
            return back();
        }
        $passwordCheck = Hash::check($request['password'], $vendor['password']);
        if ($passwordCheck && $vendor['status'] !== 'approved') {
            ToastMagic::error(translate('Not_approve_yet') . '!');
            return back();
        }
        if ($this->vendorService->isLoginSuccessful($request->email, $request->password, $request->remember)) {
            session()->forget(['is_vendor_employee', 'vendor_employee_data', 'vendor_employee_role']);
            if ($this->vendorWalletRepo->getFirstWhere(params: ['id' => auth('seller')->id()]) === false) {
                $this->vendorWalletRepo->add($this->vendorService->getInitialWalletData(vendorId: auth('seller')->id()));
            }
            ToastMagic::info(translate('welcome_to_your_dashboard') . '.');
            return redirect()->route('vendor.dashboard.index');
        } else {
            ToastMagic::error(translate('credentials_doesnt_match') . '!');
            return back();
        }
    }

    public function logout(): RedirectResponse
    {
        session()->forget(['is_vendor_employee', 'vendor_employee_data', 'vendor_employee_role']);
        $this->vendorService->logout();
        ToastMagic::success(translate('logged_out_successfully') . '.');
        return redirect()->route('vendor.auth.login');
    }
}

@extends('layouts.blank')

@section('content')
    <div class="text-center text-white mb-4">
        <h2>{{ "victorious Software Installation" }}</h2>
        <h6 class="fw-normal">
            {{ "Please proceed step by step with proper data according to instructions" }}
        </h6>
    </div>

    <div class="pb-2">
        <div class="progress cursor-pointer" role="progressbar" aria-label="victorious Software Installation"
             aria-valuenow="90" aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip"
             data-bs-placement="top" data-bs-custom-class="custom-progress-tooltip" data-bs-title="Final Step!"
             data-bs-delay='{"hide":1000}'>
            <div class="progress-bar width-90"></div>
        </div>
    </div>

    <div class="card mt-4 position-relative">
        <div class="d-flex justify-content-end mb-2 position-absolute top-end">
            <a href="#" class="d-flex align-items-center gap-1">
                <span data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip"
                      data-bs-title="Admin setup">

                    <img src="{{ dynamicAsset(path: 'public/assets/installation/assets/img/svg-icons/info.svg') }}" alt="" class="svg">
                </span>
            </a>
        </div>
        <div class="p-4 mb-md-3 mx-xl-4 px-md-5">
            <div class="d-flex align-items-center column-gap-3 flex-wrap">
                <h5 class="fw-bold fs text-uppercase">{{ "Step 5." }}</h5>
                <h5 class="fw-normal">{{ "Admin Account Settings" }}</h5>
            </div>
            <p class="mb-4">
                {{ "These information will be used to create" }}
                <strong>{{ "super admin credential" }}</strong>
                {{ "for your admin panel." }}
            </p>

            <form method="POST" action="{{ route('system_settings') }}">
                @csrf
                <div class="bg-light p-4 rounded mb-4">
                    <div class="px-xl-2 pb-sm-3">
                        <div class="row gy-4">
                            <div class="col-md-6">
                                <div class="from-group">
                                    <label for="first-name" class="d-flex align-items-center gap-2 mb-2">
                                        {{ "Business Name" }}
                                    </label>
                                    <input type="text" id="first-name" class="form-control" name="company_name"
                                           value="{{ env('APP_NAME', 'Victorious MARKET') }}"
                                           required placeholder="Ex: Victorious MARKET">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="from-group">
                                    <label for="admin-name" class="d-flex align-items-center gap-2 mb-2">
                                        {{ "Admin Name" }}
                                    </label>
                                    <input type="text" id="admin-name" class="form-control" name="admin_name"
                                           value="{{ env('SUPER_ADMIN_NAME', 'Victorious Super Admin') }}"
                                           required placeholder="Ex: Victorious Super Admin">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="from-group">
                                    <label for="phone" class="d-flex align-items-center gap-2 mb-2">
                                        <span class="fw-medium">{{ "Admin Phone" }}</span>
                                    </label>

                                    <div class="number-input-wrap">
                                        <input type="tel" id="admin_phone" class="form-control" name="admin_phone"
                                               value="{{ env('SUPER_ADMIN_PHONE', '08000000000') }}"
                                               required placeholder="Ex: 08000000000">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="from-group">
                                    <label for="email" class="d-flex align-items-center gap-2 mb-2">
                                        <span class="fw-medium">{{ "Admin Email" }}</span>
                                    </label>

                                    <input type="email" id="admin_email" class="form-control" name="admin_email"
                                           value="{{ env('SUPER_ADMIN_EMAIL', 'admin@admin.com') }}"
                                           required placeholder="Ex: admin@admin.com">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="from-group">
                                    <label for="password" class="d-flex align-items-center gap-2 mb-2">
                                        {{ "Currency Model" }}
                                    </label>
                                    <div class="input-inner-end-ele position-relative">
                                        <select class="form-control form-select action-installation-currency-select" name="currency_model">
                                            <option value="single_currency" selected>{{ "Single Currency (Nigerian Naira ₦)" }}</option>
                                            <option value="multi_currency">{{ "Multi Currency" }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="from-group">
                                    <label for="password" class="d-flex align-items-center gap-2 mb-2">
                                        {{ "Admin Password (At least 8 characters)" }}
                                    </label>
                                    <div class="input-inner-end-ele position-relative">
                                        <input type="password" autocomplete="new-password" id="admin_password"
                                               name="admin_password" required class="form-control"
                                               value="{{ env('SUPER_ADMIN_PASSWORD', '12345678') }}"
                                               placeholder="Ex: 8+ character" minlength="8">
                                        <div class="togglePassword">
                                            <img alt="" class="svg eye"
                                                src="{{ dynamicAsset(path: 'public/assets/installation/assets/img/svg-icons/eye.svg') }}">
                                            <img alt="" class="svg eye-off"
                                                src="{{ dynamicAsset(path: 'public/assets/installation/assets/img/svg-icons/eye-off.svg') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-dark px-sm-5">
                        {{ "Complete Installation" }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@extends('layouts.blank')

@section('content')
    <div class="text-center text-white mb-4">
        <h2>{{ "Victorious MARKET Enterprise Setup" }}</h2>
        <h6 class="fw-normal">
            {{ "Autonomous Enterprise Setup & Administrator Verification" }}
        </h6>
    </div>

    <div class="pb-2">
        <div class="progress cursor-pointer" role="progressbar" aria-label="Victorious MARKET Enterprise Setup"
             aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip"
             data-bs-placement="top" data-bs-custom-class="custom-progress-tooltip" data-bs-title="Step 2: Enterprise Activation"
             data-bs-delay='{"hide":1000}'>
            <div class="progress-bar width-40"></div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="p-4 mb-md-3 mx-xl-4 px-md-5">
            <div class="d-flex align-items-center column-gap-3 flex-wrap mb-3">
                <h5 class="fw-bold fs text-uppercase text-primary">{{ "Step 2." }}</h5>
                <h5 class="fw-bold">{{ "Enterprise License & Administrator Identity" }}</h5>
            </div>
            <p class="text-muted mb-4">
                {{ "Your system is verified with Victorious MARKET Enterprise License. Confirm your administrator details below to proceed." }}
            </p>

            <form method="POST" action="{{ route('purchase.code') }}">
                @csrf
                <div class="bg-light p-4 rounded mb-4">

                    <div class="px-xl-2 pb-sm-3">
                        <div class="row gy-4">
                            <div class="col-md-6">
                                <div class="from-group">
                                    <label for="person_name" class="d-flex align-items-center gap-2 mb-2">
                                        <span class="fw-medium">{{ "Super Admin Name" }}</span>
                                    </label>
                                    <input type="text" id="person_name" class="form-control" name="name"
                                           value="{{ env('SUPER_ADMIN_NAME', 'Victorious Super Admin') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="from-group">
                                    <label for="person_email" class="d-flex align-items-center gap-2 mb-2">
                                        <span class="fw-medium">{{ "Super Admin Email" }}</span>
                                    </label>
                                    <input type="email" id="person_email" class="form-control" name="email"
                                           value="{{ env('SUPER_ADMIN_EMAIL', 'admin@admin.com') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="from-group">
                                    <label for="username" class="d-flex align-items-center gap-2 mb-2">
                                        <span class="fw-medium">{{ "System Username" }}</span>
                                    </label>
                                    <input type="text" id="username" class="form-control" name="username"
                                           value="victorious_admin" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="from-group">
                                    <label for="purchase_key" class="mb-2">{{ "Enterprise License Key" }}</label>
                                    <input type="text" id="purchase_key" class="form-control" name="purchase_key"
                                           value="VICTORIOUS-MARKET-ENTERPRISE-KEY" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-dark px-sm-5">{{ "Continue to Database Setup →" }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

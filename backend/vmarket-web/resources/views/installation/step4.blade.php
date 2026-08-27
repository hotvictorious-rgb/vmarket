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
             aria-valuenow="80" aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip"
             data-bs-placement="top" data-bs-custom-class="custom-progress-tooltip" data-bs-title="Fourth Step!"
             data-bs-delay='{"hide":1000}'>
            <div class="progress-bar width-80"></div>
        </div>
    </div>

    <div class="card mt-4 position-relative">
        <div class="d-flex justify-content-end mb-2 position-absolute top-end">
            <a href="#" class="d-flex align-items-center gap-1">
                <span data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip"
                      data-bs-title="Click on the section to automatically import database">
                    <img src="{{ dynamicAsset(path: 'public/assets/installation/assets/img/svg-icons/info.svg') }}" alt=""
                         class="svg">
                </span>
            </a>
        </div>
        <div class="p-4 mb-md-3 mx-xl-4 px-md-5">
            <div class="d-flex align-items-center column-gap-3 flex-wrap">
                <h5 class="fw-bold fs text-uppercase">{{ "Step 4." }}</h5>
                <h5 class="fw-normal">{{ "Import Database" }}</h5>
            </div>
            <p class="mb-5">
                {{ "Your Database has been connected ! Just click on the section to automatically import database" }}
            </p>

            <div class="text-center d-flex justify-content-center flex-wrap gap-3">
                <a href="{{ route('step5') }}" class="btn btn-dark px-sm-5 py-2">
                    {{ "Continue to Admin Setup (Step 5) →" }}
                </a>
            </div>

        </div>
    </div>
@endsection

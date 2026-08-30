@extends('pos::layouts.app')

@section('title', 'Edit Branch')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="h4 mb-0 text-gray-800"><i class="fas fa-edit me-2 text-primary"></i>Edit Branch</h2>
            <p class="text-muted small mb-0">Modify details or update logistics routing for branch: <strong>{{ $branch->name }}</strong>.</p>
        </div>
        <a href="{{ route('pos.warehouses.index') }}" class="btn btn-outline-secondary d-flex align-items-center gap-2">
            <i class="fas fa-arrow-left"></i> Back to Listing
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8 col-xl-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 font-weight-bold text-dark">Branch Information</h5>
                </div>
                <div class="card-body">
                    @if (isset($errors) && $errors->any())
                        <div class="alert alert-danger mb-4">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('pos.warehouses.update', $branch->id) }}" method="POST" id="edit-branch-form">
                        @csrf
                        @method('PUT')

                        <!-- Branch Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label font-weight-bold text-muted small text-uppercase">Branch / Warehouse Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control form-control-lg @error('name') is-invalid @enderror" placeholder="e.g. Uyo Central Hub or Eket Branch" value="{{ old('name', $branch->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Contact Phone -->
                        <div class="mb-3">
                            <label for="phone" class="form-label font-weight-bold text-muted small text-uppercase">Contact Phone Number</label>
                            <input type="text" name="phone" id="phone" class="form-control form-control-lg @error('phone') is-invalid @enderror" placeholder="e.g. +2348012345678" value="{{ old('phone', $branch->contact) }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr class="my-4 text-light">
                        <h6 class="mb-3 text-primary font-weight-bold"><i class="fas fa-map-marked-alt me-1"></i> Logistics & Shipping Routing</h6>

                        <!-- Country Selection -->
                        <div class="mb-3">
                            <label for="country_display" class="form-label font-weight-bold text-muted small text-uppercase">Country</label>
                            <input type="text" id="country_display" class="form-control form-control-lg bg-light" value="{{ $branch->country ?: 'Nigeria' }}" readonly disabled>
                            <input type="hidden" name="country" value="{{ $branch->country ?: 'Nigeria' }}">
                        </div>

                        <!-- State Selection -->
                        <div class="mb-3">
                            <label for="state_select" class="form-label font-weight-bold text-muted small text-uppercase">Select State <span class="text-danger">*</span></label>
                            <select name="state_id" id="state_select" class="form-select form-select-lg @error('state_id') is-invalid @enderror" required>
                                <option value="">--- Select State ---</option>
                                @foreach($states as $state)
                                    <option value="{{ $state->id }}" {{ old('state_id', $branch->state_id) == $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                                @endforeach
                            </select>
                            @error('state_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- LGA / City Selection -->
                        <div class="mb-3">
                            <label for="lga_select" class="form-label font-weight-bold text-muted small text-uppercase">Local Government Area (LGA) <span class="text-danger">*</span></label>
                            <select name="lga_id" id="lga_select" class="form-select form-select-lg @error('lga_id') is-invalid @enderror" required {{ empty($cities) ? 'disabled' : '' }}>
                                <option value="">--- Select LGA ---</option>
                                @foreach($cities as $city)
                                    <option value="{{ $city->id }}" {{ old('lga_id', $branch->lga_id) == $city->id ? 'selected' : '' }}>{{ $city->name }}</option>
                                @endforeach
                            </select>
                            @error('lga_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Logistics Hub Selection -->
                        <div class="mb-3">
                            <label for="hub_select" class="form-label font-weight-bold text-muted small text-uppercase">Assigned Dispatch Hub / Terminal <span class="text-danger">*</span></label>
                            <select name="hub_id" id="hub_select" class="form-select form-select-lg @error('hub_id') is-invalid @enderror" required {{ empty($hubs) ? 'disabled' : '' }}>
                                <option value="">--- Select Hub ---</option>
                                @foreach($hubs as $hub)
                                    <option value="{{ $hub->id }}" {{ old('hub_id', $branch->hub_id) == $hub->id ? 'selected' : '' }}>{{ $hub->name }} ({{ $hub->type === 'motor_park' ? 'Motor Park' : 'Landmark' }})</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Configured by Super Admin for interstate motor parks or local delivery landmarks.</small>
                            @error('hub_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Street Address -->
                        <div class="mb-4">
                            <label for="address" class="form-label font-weight-bold text-muted small text-uppercase">Physical Street Address <span class="text-danger">*</span></label>
                            <textarea name="address" id="address" rows="3" class="form-control form-control-lg @error('address') is-invalid @enderror" placeholder="e.g. Suite 4, Ewet Housing Estate Office Complex" required>{{ old('address', $branch->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Actions -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg font-weight-bold py-3">
                                <i class="fas fa-save me-2"></i> Update Branch details
                            </button>
                            <a href="{{ route('pos.warehouses.index') }}" class="btn btn-light btn-lg py-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const stateSelect = document.getElementById('state_select');
        const lgaSelect = document.getElementById('lga_select');
        const hubSelect = document.getElementById('hub_select');

        // [AI] Handles State Selection Change
        stateSelect.addEventListener('change', function () {
            const stateId = this.value;
            lgaSelect.innerHTML = '<option value="">--- Select State First ---</option>';
            lgaSelect.disabled = true;
            hubSelect.innerHTML = '<option value="">--- Select LGA First ---</option>';
            hubSelect.disabled = true;

            if (!stateId) return;

            lgaSelect.innerHTML = '<option value="">Loading LGAs...</option>';

            fetch(`{{ url('/pos/warehouses/ajax/cities') }}/${stateId}`)
                .then(response => response.json())
                .then(data => {
                    lgaSelect.innerHTML = '<option value="">--- Select LGA ---</option>';
                    if (data.length > 0) {
                        lgaSelect.disabled = false;
                        data.forEach(item => {
                            const option = document.createElement('option');
                            option.value = item.id;
                            option.textContent = item.name;
                            lgaSelect.appendChild(option);
                        });
                    } else {
                        lgaSelect.innerHTML = '<option value="">No LGAs configured for this state</option>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching LGAs:', error);
                    lgaSelect.innerHTML = '<option value="">Error loading LGAs</option>';
                });
        });

        // [AI] Handles LGA Selection Change
        lgaSelect.addEventListener('change', function () {
            const lgaId = this.value;
            hubSelect.innerHTML = '<option value="">--- Select LGA First ---</option>';
            hubSelect.disabled = true;

            if (!lgaId) return;

            hubSelect.innerHTML = '<option value="">Loading Hubs...</option>';

            fetch(`{{ url('/pos/warehouses/ajax/hubs') }}/${lgaId}`)
                .then(response => response.json())
                .then(data => {
                    hubSelect.innerHTML = '<option value="">--- Select Hub ---</option>';
                    if (data.length > 0) {
                        hubSelect.disabled = false;
                        data.forEach(item => {
                            const option = document.createElement('option');
                            option.value = item.id;
                            option.textContent = `${item.name} (${item.type === 'motor_park' ? 'Motor Park' : 'Landmark'})`;
                            hubSelect.appendChild(option);
                        });
                    } else {
                        hubSelect.innerHTML = '<option value="">No Hubs configured in this LGA</option>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching Hubs:', error);
                    hubSelect.innerHTML = '<option value="">Error loading Hubs</option>';
                });
        });
    });
</script>
@endpush
@endsection

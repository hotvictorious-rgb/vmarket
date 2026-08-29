@extends('delivery::layouts.app')

@section('title', 'Create New Hub')

@section('content')
<div class="mb-4">
    <a href="{{ route('delivery.hubs.index') }}" class="text-decoration-none text-muted fs-13">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Hubs
    </a>
    <h4 class="fw-bold mt-2" style="color: #1a1a2e;">📍 Add New Logistics Hub</h4>
    <p class="text-muted fs-13 mb-0">Create an official logistics aggregation point that vendor shops and corridor routes attach to.</p>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="glass-card">
            <form action="{{ route('delivery.hubs.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label font-weight-bold">State <span class="text-danger">*</span></label>
                        <select id="stateSelector" class="form-select" required>
                            <option value="">-- Select State --</option>
                            @foreach($states as $state)
                                <option value="{{ $state->id }}" {{ $state->name == 'Akwa Ibom' ? 'selected' : '' }}>{{ $state->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label font-weight-bold">City / LGA <span class="text-danger">*</span></label>
                        <select id="citySelector" name="city_id" class="form-select" required>
                            <option value="">-- Select LGA / City --</option>
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label font-weight-bold">Hub Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Uyo Central Hub (Itam / Plaza)" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">Hub Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            <option value="landmark">Landmark / Central Hub</option>
                            <option value="motor_park">Motor Park / Corridor Depot</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label font-weight-bold">Base Shipping Rate (₦) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="base_shipping_cost" class="form-control" placeholder="1000.00" required>
                        <small class="text-muted fs-11">Default delivery charge billed to customers for this zone.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label font-weight-bold">Rider Delivery Fee (₦) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="rider_delivery_fee" class="form-control" placeholder="700.00" required>
                        <small class="text-muted fs-11">Default payout allocated to dispatch riders per drop.</small>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label font-weight-bold">Estimated Delivery Time</label>
                        <input type="text" name="estimated_delivery_time" class="form-control" placeholder="1 - 3 Hours (Same-Day Express)">
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-brand-primary w-100 py-2">
                            <i class="fa-solid fa-check me-1"></i> Save & Publish Logistics Hub
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const stateSelect = document.getElementById('stateSelector');
        const citySelect = document.getElementById('citySelector');

        function loadCities(stateId) {
            if (!stateId) {
                citySelect.innerHTML = '<option value="">-- Select LGA / City --</option>';
                return;
            }
            fetch(`{{ url('delivery/hubs/ajax/cities') }}/${stateId}`)
                .then(res => res.json())
                .then(data => {
                    let html = '<option value="">-- Select LGA / City --</option>';
                    data.forEach(c => {
                        html += `<option value="${c.id}">${c.name}</option>`;
                    });
                    citySelect.innerHTML = html;
                });
        }

        stateSelect.addEventListener('change', function() {
            loadCities(this.value);
        });

        if (stateSelect.value) {
            loadCities(stateSelect.value);
        }
    });
</script>
@endpush
@endsection

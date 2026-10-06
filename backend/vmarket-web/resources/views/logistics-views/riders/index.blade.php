@extends('logistics-views.layouts.app')

@section('title', translate('Fleet_Riders'))

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="h3 fw-bold mb-1">{{ translate('Fleet_Riders') }}</h2>
            <p class="text-muted fs-13 mb-0">{{ translate('Manage_your_delivery_riders,_vehicles,_and_active_workload.') }}</p>
        </div>
        <div>
            <a href="{{ route('logistics.riders.create') }}" class="btn btn-primary">
                <i class="fi fi-sr-plus me-1"></i> {{ translate('Add_New_Rider') }}
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-12">
        <div class="card-header bg-white border-0 py-3">
            <form action="{{ url()->current() }}" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <div class="input-group" style="max-width: 320px;">
                    <input type="search" name="searchValue" class="form-control"
                           placeholder="{{ translate('Search_rider_name,_phone...') }}"
                           value="{{ $searchValue }}">
                    <button type="submit" class="btn btn-primary">{{ translate('Search') }}</button>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-nowrap align-middle mb-0">
                    <thead class="table-light fs-12 text-capitalize">
                        <tr>
                            <th>{{ translate('Rider') }}</th>
                            <th>{{ translate('Vehicle_Type') }}</th>
                            <th>{{ translate('Phone') }}</th>
                            <th>{{ translate('Email') }}</th>
                            <th>{{ translate('Online_Status') }}</th>
                            <th>{{ translate('Active_Orders') }}</th>
                            <th>{{ translate('Account_Status') }}</th>
                            <th class="text-center">{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($riders as $rider)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar avatar-md border rounded overflow-hidden bg-light d-flex align-items-center justify-content-center">
                                        @if($rider->image)
                                            <img src="{{ getStorageImages(path: $rider->image_full_url ?? null, type: 'backend-profile') }}"
                                                 alt="{{ $rider->f_name }}" class="img-fluid">
                                        @else
                                            <span class="fw-bold text-primary">{{ strtoupper(substr($rider->f_name, 0, 1)) }}</span>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="fw-bold">{{ $rider->f_name }} {{ $rider->l_name }}</div>
                                        <span class="fs-11 text-muted">{{ translate('ID:') }} #DM-{{ $rider->id }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($rider->vehicle_type === 'motorbike')
                                    <span class="badge badge-soft-info px-2 py-1">🏍️ Motorbike</span>
                                @elseif($rider->vehicle_type === 'van')
                                    <span class="badge badge-soft-primary px-2 py-1">🚐 Delivery Van</span>
                                @elseif($rider->vehicle_type === 'truck')
                                    <span class="badge badge-soft-danger px-2 py-1">🚚 Cargo Truck</span>
                                @else
                                    <span class="badge badge-soft-warning px-2 py-1">🛺 Tricycle (Keke)</span>
                                @endif
                            </td>
                            <td>{{ $rider->phone }}</td>
                            <td>{{ $rider->email }}</td>
                            <td>
                                @if($rider->is_online)
                                    <span class="badge badge-soft-success">● {{ translate('Online') }}</span>
                                @else
                                    <span class="badge badge-soft-secondary">○ {{ translate('Offline') }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-soft-info fs-13">
                                    {{ $rider->orders_count }} {{ translate('Active') }}
                                </span>
                            </td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input change-status-ajax" type="checkbox"
                                           data-id="{{ $rider->id }}"
                                           data-url="{{ route('logistics.riders.status') }}"
                                           {{ $rider->is_active ? 'checked' : '' }}>
                                </div>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('logistics.riders.edit', $rider->id) }}" class="btn btn-outline-primary btn-sm px-3">
                                    {{ translate('Edit') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                {{ translate('No_riders_found_in_your_fleet.') }}
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3">
                {!! $riders->links() !!}
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    $('.change-status-ajax').on('change', function() {
        let id = $(this).data('id');
        let url = $(this).data('url');
        let status = $(this).is(':checked') ? 1 : 0;

        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: id,
                status: status
            },
            success: function(response) {
                toastr.success(response.message);
            },
            error: function() {
                toastr.error('{{ translate("Status_update_failed") }}');
            }
        });
    });
</script>
@endpush

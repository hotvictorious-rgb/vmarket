@extends('layouts.admin.app')

@section('title', translate('WhatsApp_Broadcast_Campaigns'))

@section('content')
<div class="content container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h2 class="h1 mb-0 d-flex align-items-center gap-2" style="color: #4A154B;">
                <i class="tio-speakerphone"></i> {{ translate('WhatsApp_Broadcast_Campaigns') }}
            </h2>
            <p class="text-muted fs-12 mb-0">{{ translate('Targeted marketing broadcasts, seasonal sales & promotional blasts') }}</p>
        </div>
        <button class="btn btn--primary" data-toggle="modal" data-target="#createBroadcastModal" style="background-color: #4A154B; border-color: #4A154B;">
            <i class="tio-add"></i> {{ translate('Create_New_Broadcast') }}
        </button>
    </div>

    <!-- Campaigns Table -->
    <div class="card">
        <div class="card-header border-0 py-3">
            <h5 class="card-title mb-0">{{ translate('Campaign_History_&_Analytics') }}</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-borderless table-thead-bordered table-nowrap align-middle">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('Campaign_Title') }}</th>
                        <th>{{ translate('Template') }}</th>
                        <th>{{ translate('Recipients') }}</th>
                        <th>{{ translate('Sent') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($broadcasts as $bc)
                        <tr>
                            <td class="font-weight-bold">{{ $bc->title }}</td>
                            <td><code>{{ $bc->template_name }}</code></td>
                            <td>{{ number_format($bc->total_recipients) }}</td>
                            <td>{{ number_format($bc->sent_count) }}</td>
                            <td>
                                @if($bc->status === 'completed')
                                    <span class="badge bg-success">{{ translate('Completed') }}</span>
                                @elseif($bc->status === 'processing')
                                    <span class="badge bg-warning">{{ translate('Processing') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ $bc->status }}</span>
                                @endif
                            </td>
                            <td>{{ $bc->created_at->format('M d, Y h:i A') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                {{ translate('No_broadcast_campaigns_created_yet') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $broadcasts->links() }}
        </div>
    </div>
</div>

<!-- Modal: Create Broadcast -->
<div class="modal fade" id="createBroadcastModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.whatsapp-crm.broadcasts.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold">{{ translate('Create_Targeted_WhatsApp_Broadcast') }}</h5>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Campaign_Title') }}</label>
                        <input type="text" name="title" class="form-control" placeholder="{{ translate('e.g. Uyo Weekend Flash Sale 2026') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Meta_Template_Name') }}</label>
                        <input type="text" name="template_name" class="form-control" placeholder="{{ translate('e.g. flash_sale_promo') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Filter_by_City_(Optional)') }}</label>
                        <input type="text" name="city_filter" class="form-control" placeholder="{{ translate('e.g. Uyo, Eket, Ikot Ekpene') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Minimum_Customer_Spend_₦_(Optional)') }}</label>
                        <input type="number" name="min_ltv" class="form-control" placeholder="{{ translate('e.g. 25000') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn--primary" style="background: #4A154B; border-color: #4A154B;">{{ translate('Queue_&_Launch_Broadcast') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

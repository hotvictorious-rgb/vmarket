@extends('layouts.admin.app')

@section('title', translate('Victorious Points — Cashback Ledger'))

@section('content')
<div class="content container-fluid">
    <div class="mb-3">
        <h1 class="page-header-title d-flex align-items-center gap-2">
            <i class="tio-star"></i> {{ translate('Victorious Points — Cashback Ledger Oversight') }}
        </h1>
        <p class="text-muted">{{ translate('Read-only audit view of the 5% Victorious Cashback reward ledger. All mutations are system-originated; manual edits are not permitted.') }}</p>
        <div class="badge badge-soft-warning px-2 py-1">
            <i class="tio-lock"></i> {{ translate('Invariant: Δ cashback_amount = 0.00 — No manual mutations permitted.') }}
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="row g-2 mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body">
                <div class="media-body">
                    <span class="text-muted fs-12">{{ translate('Pending Cashback (₦)') }}</span>
                    <h2 class="mb-0 text-warning">₦{{ number_format($metrics['total_pending_cashback'], 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body">
                <div class="media-body">
                    <span class="text-muted fs-12">{{ translate('Available Cashback (₦)') }}</span>
                    <h2 class="mb-0 text-success">₦{{ number_format($metrics['total_available_cashback'], 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body">
                <div class="media-body">
                    <span class="text-muted fs-12">{{ translate('Total Points Redeemed (₦)') }}</span>
                    <h2 class="mb-0 text-info">₦{{ number_format($metrics['total_redeemed_points'], 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body">
                <div class="media-body">
                    <span class="text-muted fs-12">{{ translate('Released (Refund / Cancel)') }}</span>
                    <h2 class="mb-0 text-secondary">₦{{ number_format($metrics['total_released_points'], 2) }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Cashback Earn Ledger -->
    <div class="card mb-4">
        <div class="card-header border-0 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <h5 class="mb-0">{{ translate('5% Cashback Earn Ledger (Delivery Orders)') }}</h5>
            <form action="{{ route('admin.cashback.index') }}" method="GET" class="d-flex gap-2">
                <input type="search" name="searchValue" class="form-control form-control-sm"
                    placeholder="{{ translate('Customer or Order ID...') }}" value="{{ request('searchValue') }}">
                <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="all" {{ $status == 'all' ? 'selected' : '' }}>{{ translate('All Statuses') }}</option>
                    <option value="pending" {{ $status == 'pending' ? 'selected' : '' }}>{{ translate('Pending') }}</option>
                    <option value="available" {{ $status == 'available' ? 'selected' : '' }}>{{ translate('Available') }}</option>
                    <option value="redeemed" {{ $status == 'redeemed' ? 'selected' : '' }}>{{ translate('Redeemed') }}</option>
                    <option value="cancelled" {{ $status == 'cancelled' ? 'selected' : '' }}>{{ translate('Cancelled') }}</option>
                </select>
                <button type="submit" class="btn btn--primary btn-sm">{{ translate('Filter') }}</button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('ID') }}</th>
                        <th>{{ translate('Customer') }}</th>
                        <th>{{ translate('Order ID') }}</th>
                        <th>{{ translate('Merchandise (₦)') }}</th>
                        <th>{{ translate('Cashback Rate') }}</th>
                        <th>{{ translate('Cashback Amount (₦)') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Available From') }}</th>
                        <th>{{ translate('Redeemed At') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledgerEntries as $entry)
                        <tr>
                            <td>#{{ $entry->id }}</td>
                            <td>
                                <div>{{ $entry->customer ? ($entry->customer->f_name . ' ' . $entry->customer->l_name) : '—' }}</div>
                                <small class="text-muted">{{ $entry->customer?->phone }}</small>
                            </td>
                            <td><a href="{{ route('admin.orders.details', $entry->order_id) }}" class="text-primary">#{{ $entry->order_id }}</a></td>
                            <td>₦{{ number_format($entry->merchandise_amount, 2) }}</td>
                            <td>{{ $entry->cashback_rate }}%</td>
                            <td class="font-weight-bold text-success">₦{{ number_format($entry->cashback_amount, 2) }}</td>
                            <td>
                                @if($entry->status == 'pending')
                                    <span class="badge badge-soft-warning">{{ translate('Pending') }}</span>
                                @elseif($entry->status == 'available')
                                    <span class="badge badge-soft-success">{{ translate('Available') }}</span>
                                @elseif($entry->status == 'redeemed')
                                    <span class="badge badge-soft-info">{{ translate('Redeemed') }}</span>
                                @elseif($entry->status == 'cancelled')
                                    <span class="badge badge-soft-danger">{{ translate('Cancelled') }}</span>
                                @else
                                    <span class="badge badge-soft-secondary">{{ $entry->status }}</span>
                                @endif
                            </td>
                            <td>{{ $entry->available_at ? $entry->available_at->format('Y-m-d') : '—' }}</td>
                            <td>{{ $entry->redeemed_at ? $entry->redeemed_at->format('Y-m-d H:i') : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center p-4 text-muted">{{ translate('No cashback ledger entries found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer border-0">{!! $ledgerEntries->links() !!}</div>
    </div>

    <!-- Cashback Redemption / Spend Ledger -->
    <div class="card mb-3">
        <div class="card-header border-0 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <h5 class="mb-0">{{ translate('Points Spend / Redemption Transactions') }}</h5>
            <form action="{{ route('admin.cashback.index') }}" method="GET" class="d-flex gap-2">
                <select name="channel" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="all" {{ $channel == 'all' ? 'selected' : '' }}>{{ translate('All Channels') }}</option>
                    <option value="delivery" {{ $channel == 'delivery' ? 'selected' : '' }}>{{ translate('Delivery Orders') }}</option>
                    <option value="pickup" {{ $channel == 'pickup' ? 'selected' : '' }}>{{ translate('In-Shop Pickup') }}</option>
                </select>
                <button type="submit" class="btn btn--primary btn-sm">{{ translate('Filter') }}</button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('ID') }}</th>
                        <th>{{ translate('Customer') }}</th>
                        <th>{{ translate('Channel') }}</th>
                        <th>{{ translate('Order Group') }}</th>
                        <th>{{ translate('Points Used (₦)') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Captured At') }}</th>
                        <th>{{ translate('Released At') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($redemptions as $redemption)
                        <tr>
                            <td>#{{ $redemption->id }}</td>
                            <td>
                                <div>{{ $redemption->customer ? ($redemption->customer->f_name . ' ' . $redemption->customer->l_name) : '—' }}</div>
                                <small class="text-muted">{{ $redemption->customer?->phone }}</small>
                            </td>
                            <td>
                                @if($redemption->checkout_intent_id)
                                    <span class="badge badge-soft-primary">{{ translate('Delivery') }}</span>
                                @elseif($redemption->pickup_reservation_id)
                                    <span class="badge badge-soft-info">{{ translate('Pickup') }}</span>
                                @else
                                    <span class="badge badge-soft-secondary">{{ translate('Unknown') }}</span>
                                @endif
                            </td>
                            <td><code>{{ $redemption->order_group_id }}</code></td>
                            <td class="font-weight-bold">₦{{ number_format($redemption->cashback_amount, 2) }}</td>
                            <td>
                                @if($redemption->status == 'reserved')
                                    <span class="badge badge-soft-warning">{{ translate('Reserved') }}</span>
                                @elseif($redemption->status == 'captured')
                                    <span class="badge badge-soft-success">{{ translate('Captured') }}</span>
                                @elseif($redemption->status == 'released')
                                    <span class="badge badge-soft-secondary">{{ translate('Released') }}</span>
                                @elseif($redemption->status == 'expired')
                                    <span class="badge badge-soft-danger">{{ translate('Expired') }}</span>
                                @else
                                    <span class="badge badge-soft-secondary">{{ $redemption->status }}</span>
                                @endif
                            </td>
                            <td>{{ $redemption->captured_at ? $redemption->captured_at->format('Y-m-d H:i') : '—' }}</td>
                            <td>{{ $redemption->released_at ? $redemption->released_at->format('Y-m-d H:i') : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center p-4 text-muted">{{ translate('No cashback redemption records found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer border-0">{!! $redemptions->appends(request()->except('redemption_page'))->links() !!}</div>
    </div>
</div>
@endsection

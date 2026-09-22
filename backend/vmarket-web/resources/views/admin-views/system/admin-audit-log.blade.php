@extends('layouts.admin.app')

@section('title', translate('Immutable Admin Audit Log'))

@section('content')
<div class="content container-fluid">
    <div class="mb-3">
        <h1 class="page-header-title d-flex align-items-center gap-2">
            <i class="tio-lock"></i> {{ translate('Immutable Administrative Audit Log') }}
        </h1>
        <p class="text-muted">{{ translate('Permanent, append-only record of all sensitive administrative actions across Victorious MARKET.') }}</p>
    </div>

    <div class="card mb-3">
        <div class="card-header border-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 w-100">
                <form action="{{ route('admin.audit-logs.index') }}" method="GET" class="d-flex gap-2">
                    <div class="input-group input-group-merge input-group-custom">
                        <div class="input-group-prepend">
                            <div class="input-group-text">
                                <i class="tio-search"></i>
                            </div>
                        </div>
                        <input type="search" name="searchValue" class="form-control" placeholder="{{ translate('Search action, admin, IP...') }}" value="{{ request('searchValue') }}">
                    </div>
                    <button type="submit" class="btn btn--primary">{{ translate('Search') }}</button>
                </form>

                <div class="badge badge-soft-info p-2">
                    <i class="tio-shield"></i> {{ translate('Audit Log Integrity: Append-Only Immutable') }}
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('ID') }}</th>
                        <th>{{ translate('Timestamp (UTC)') }}</th>
                        <th>{{ translate('Admin User') }}</th>
                        <th>{{ translate('Action') }}</th>
                        <th>{{ translate('Resource') }}</th>
                        <th>{{ translate('Reason / Note') }}</th>
                        <th>{{ translate('IP Address') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($auditLogs as $log)
                        <tr>
                            <td>#{{ $log->id }}</td>
                            <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                            <td>
                                <div class="font-weight-bold">{{ $log->admin_name }}</div>
                                <small class="text-muted">{{ $log->admin_role }}</small>
                            </td>
                            <td>
                                <span class="badge badge-soft-primary px-2 py-1">{{ $log->action }}</span>
                            </td>
                            <td>
                                @if($log->resource_type)
                                    <div><code>{{ class_basename($log->resource_type) }}</code></div>
                                    @if($log->resource_id)
                                        <small class="text-muted">ID: {{ $log->resource_id }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-wrap" style="max-width: 250px; display: inline-block;">
                                    {{ $log->reason ?? translate('No reason provided') }}
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">{{ $log->ip_address }}</small>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center p-4">
                                <div class="text-muted">{{ translate('No audit log entries recorded yet.') }}</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer border-0">
            {!! $auditLogs->links() !!}
        </div>
    </div>
</div>
@endsection

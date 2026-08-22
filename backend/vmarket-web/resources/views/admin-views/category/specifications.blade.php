@extends('layouts.admin.app')

@section('title', translate('Category Specifications Setup'))

@section('content')
<div class="content container-fluid">
    <div class="mb-3">
        <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2" style="color: #4A154B;">
            <i class="tio-help-outlined"></i>
            {{ translate('Smart Category Specifications & Questionnaires') }}
        </h2>
        <p class="fs-12 text-muted mt-1">
            {{ translate('Define category-specific questions and dropdown options. Vendors will be automatically prompted with these questions when listing products in each category.') }}
        </p>
    </div>

    <!-- Category Selector Ribbon -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <form action="{{ route('admin.category-specifications.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="title-color font-weight-bold">{{ translate('Select Category to Configure') }}</label>
                    <select name="category_id" class="form-control js-select2-custom" onchange="this.form.submit()">
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $selectedCategoryId == $cat->id ? 'selected' : '' }}>
                                📁 {{ $cat->name }}
                            </option>
                            @foreach($cat->childes as $sub)
                                <option value="{{ $sub->id }}" {{ $selectedCategoryId == $sub->id ? 'selected' : '' }}>
                                    &nbsp;&nbsp;&nbsp;&nbsp;↳ 📂 {{ $sub->name }} (Sub-Category)
                                </option>
                                @foreach($sub->childes as $subSub)
                                    <option value="{{ $subSub->id }}" {{ $selectedCategoryId == $subSub->id ? 'selected' : '' }}>
                                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;↳ 📄 {{ $subSub->name }} (Sub-Sub)
                                    </option>
                                @endforeach
                            @endforeach
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 text-right">
                    <span class="badge bg-soft-primary px-3 py-2 fs-13">
                        <i class="tio-filter-list"></i> {{ count($specifications) }} {{ translate('Active Questions Configured') }}
                    </span>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        <!-- 1. Add Specification Form -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-light py-3">
                    <h5 class="mb-0 text-capitalize font-weight-bold" style="color: #4A154B;">
                        <i class="tio-add-circle mr-1"></i> {{ translate('Add Specification Question') }}
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.category-specifications.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="category_id" value="{{ $selectedCategoryId }}">

                        <div class="form-group mb-3">
                            <label class="title-color font-weight-bold">{{ translate('Question / Specification Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="{{ translate('e.g. Battery Capacity, Screen Size, Fabric') }}" required>
                        </div>

                        <div class="form-group mb-3">
                            <label class="title-color font-weight-bold">{{ translate('Input Type') }} <span class="text-danger">*</span></label>
                            <select name="input_type" class="form-control" id="spec-input-type" required>
                                <option value="text">{{ translate('Text Input (Free-form)') }}</option>
                                <option value="number">{{ translate('Number Input') }}</option>
                                <option value="select">{{ translate('Dropdown Select (Predefined Choices)') }}</option>
                                <option value="multi_select">{{ translate('Multi-Select (Multiple Choices)') }}</option>
                            </select>
                        </div>

                        <div class="form-group mb-3" id="spec-options-group" style="display: none;">
                            <label class="title-color font-weight-bold">{{ translate('Predefined Choices (Comma Separated)') }}</label>
                            <textarea name="options" rows="2" class="form-control" placeholder="{{ translate('e.g. 64GB, 128GB, 256GB, 512GB') }}"></textarea>
                            <small class="text-muted">{{ translate('Separate options with commas. The vendor will pick from these choices.') }}</small>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="title-color">{{ translate('Unit (Optional)') }}</label>
                                <input type="text" name="unit" class="form-control" placeholder="{{ translate('e.g. mAh, Watts, Inches') }}">
                            </div>
                            <div class="col-6">
                                <label class="title-color">{{ translate('Sort Order') }}</label>
                                <input type="number" name="sort_order" class="form-control" value="{{ count($specifications) + 1 }}">
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="title-color">{{ translate('Placeholder Hint') }}</label>
                            <input type="text" name="placeholder" class="form-control" placeholder="{{ translate('e.g. e.g. 5000 mAh or Cotton') }}">
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="is_required" value="1" id="req-check">
                            <label class="form-check-label title-color" for="req-check">
                                {{ translate('Make this question Mandatory for vendors') }}
                            </label>
                        </div>

                        <button type="submit" class="btn btn--primary btn-block text-capitalize" style="background-color: #4A154B; border-color: #4A154B;">
                            <i class="tio-save"></i> {{ translate('Save Specification Question') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- 2. Active Specifications Table -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-capitalize font-weight-bold">
                        {{ translate('Configured Questions for') }}: <span class="text-primary">{{ $selectedCategory?->name ?? translate('Category') }}</span>
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-borderless table-thead-bordered text-center align-middle mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th class="text-left">{{ translate('Specification Name') }}</th>
                                <th>{{ translate('Type') }}</th>
                                <th>{{ translate('Choices / Unit') }}</th>
                                <th>{{ translate('Mandatory') }}</th>
                                <th>{{ translate('Status') }}</th>
                                <th>{{ translate('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($specifications as $key => $spec)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td class="text-left font-weight-bold">
                                        {{ $spec->name }}
                                        @if($spec->placeholder)
                                            <div class="fs-11 text-muted">{{ $spec->placeholder }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-soft-info">{{ translate($spec->input_type) }}</span>
                                    </td>
                                    <td>
                                        @if($spec->options && count($spec->options) > 0)
                                            <span class="badge badge-soft-secondary" title="{{ implode(', ', $spec->options) }}">
                                                {{ count($spec->options) }} {{ translate('choices') }}
                                            </span>
                                        @elseif($spec->unit)
                                            <code>{{ $spec->unit }}</code>
                                        @else
                                            <span class="text-muted fs-12">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($spec->is_required)
                                            <span class="badge bg-danger">{{ translate('Required') }}</span>
                                        @else
                                            <span class="badge bg-secondary">{{ translate('Optional') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <label class="switcher mx-auto">
                                            <input type="checkbox" class="switcher_input spec-status-toggle" data-id="{{ $spec->id }}" data-url="{{ route('admin.category-specifications.status') }}" {{ $spec->is_active ? 'checked' : '' }}>
                                            <span class="switcher_control"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            <button type="button" class="btn btn-outline-primary btn-sm square-btn edit-spec-btn"
                                                    data-id="{{ $spec->id }}"
                                                    data-name="{{ $spec->name }}"
                                                    data-input-type="{{ $spec->input_type }}"
                                                    data-options="{{ $spec->options ? implode(', ', $spec->options) : '' }}"
                                                    data-unit="{{ $spec->unit }}"
                                                    data-placeholder="{{ $spec->placeholder }}"
                                                    data-is-required="{{ $spec->is_required ? 1 : 0 }}"
                                                    data-sort-order="{{ $spec->sort_order }}"
                                                    data-url="{{ route('admin.category-specifications.update', $spec->id) }}"
                                                    title="{{ translate('Edit Question') }}">
                                                <i class="tio-edit"></i>
                                            </button>
                                            <form action="{{ route('admin.category-specifications.delete', $spec->id) }}" method="POST" onsubmit="return confirm('{{ translate('Delete this specification question?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm square-btn" title="{{ translate('Delete Question') }}">
                                                    <i class="tio-delete"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        {{ translate('No specification questions configured for this category yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- EDIT SPECIFICATION MODAL -->
<div class="modal fade" id="editSpecModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold" style="color: #4A154B;">{{ translate('Edit Specification Question') }}</h5>
                <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editSpecForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="title-color font-weight-bold">{{ translate('Question Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit-spec-name" class="form-control" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="title-color font-weight-bold">{{ translate('Input Type') }} <span class="text-danger">*</span></label>
                        <select name="input_type" id="edit-spec-input-type" class="form-control" required>
                            <option value="text">{{ translate('Text Input') }}</option>
                            <option value="number">{{ translate('Number Input') }}</option>
                            <option value="select">{{ translate('Dropdown Select (Predefined Choices)') }}</option>
                            <option value="multi_select">{{ translate('Multi-Select') }}</option>
                        </select>
                    </div>

                    <div class="form-group mb-3" id="edit-spec-options-group">
                        <label class="title-color font-weight-bold">{{ translate('Predefined Choices (Comma Separated)') }}</label>
                        <textarea name="options" id="edit-spec-options" rows="2" class="form-control"></textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="title-color">{{ translate('Unit') }}</label>
                            <input type="text" name="unit" id="edit-spec-unit" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="title-color">{{ translate('Sort Order') }}</label>
                            <input type="number" name="sort_order" id="edit-spec-sort-order" class="form-control">
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="title-color">{{ translate('Placeholder') }}</label>
                        <input type="text" name="placeholder" id="edit-spec-placeholder" class="form-control">
                    </div>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_required" value="1" id="edit-req-check">
                        <label class="form-check-label title-color" for="edit-req-check">
                            {{ translate('Make Mandatory') }}
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn--primary" style="background: #4A154B; border-color: #4A154B;">{{ translate('Update Question') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('script')
<script>
    $('#spec-input-type').on('change', function() {
        var val = $(this).val();
        if (val === 'select' || val === 'multi_select') {
            $('#spec-options-group').slideDown();
        } else {
            $('#spec-options-group').slideUp();
        }
    });

    $('#edit-spec-input-type').on('change', function() {
        var val = $(this).val();
        if (val === 'select' || val === 'multi_select') {
            $('#edit-spec-options-group').slideDown();
        } else {
            $('#edit-spec-options-group').slideUp();
        }
    });

    $(document).on('click', '.edit-spec-btn', function() {
        var btn = $(this);
        $('#editSpecForm').attr('action', btn.data('url'));
        $('#edit-spec-name').val(btn.data('name'));
        $('#edit-spec-input-type').val(btn.data('input-type')).trigger('change');
        $('#edit-spec-options').val(btn.data('options'));
        $('#edit-spec-unit').val(btn.data('unit'));
        $('#edit-spec-placeholder').val(btn.data('placeholder'));
        $('#edit-spec-sort-order').val(btn.data('sort-order'));
        $('#edit-req-check').prop('checked', btn.data('is-required') == 1);
        $('#editSpecModal').modal('show');
    });

    $('.spec-status-toggle').on('change', function() {
        var id = $(this).data('id');
        var url = $(this).data('url');
        var status = $(this).prop('checked') ? 1 : 0;
        $.post(url, {_token: '{{ csrf_token() }}', id: id, status: status}, function(response) {
            if (typeof toastr !== 'undefined') {
                toastr.success(response.message);
            }
        });
    });
</script>
@endpush
@endsection

@extends('layouts.vendor.app')

@section('title', translate('bank_Info'))

@section('content')
    <div class="content container-fluid text-start">
        <div class="mb-3">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img width="20" src="{{dynamicAsset(path: 'public/assets/back-end/img/my-bank-info.png')}}" alt="">
                {{translate('edit_Bank_info')}}
            </h2>
        </div>
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0 text-capitalize">{{translate('edit_bank_info')}}</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{route('vendor.profile.update-bank-info',[$vendor->id])}}" method="post"
                              enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="bank_select" class="title-color d-flex align-items-center justify-content-between">
                                            <span>{{translate('select_Bank')}} ({{ translate('Nigeria') }}) <span class="text-danger">*</span></span>
                                            <span class="badge badge-soft-info font-size-xs">{{ translate('Paystack_Verified') }}</span>
                                        </label>
                                        <select id="bank_select" class="form-control">
                                            <option value="">-- {{translate('choose_Nigerian_Bank')}} --</option>
                                            @if(!empty($nigerianBanks))
                                                @foreach($nigerianBanks as $bank)
                                                    <option value="{{ $bank['code'] ?? '' }}"
                                                            data-name="{{ $bank['name'] ?? '' }}"
                                                            {{ strtolower(trim($vendor->bank_name ?? '')) === strtolower(trim($bank['name'] ?? '')) ? 'selected' : '' }}>
                                                        {{ $bank['name'] ?? '' }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <input type="hidden" name="bank_name" id="bank_name_input" value="{{ $vendor->bank_name }}" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="account_no" class="title-color">{{translate('NUBAN_Account_No')}} (10 {{ translate('digits') }}) <span class="text-danger">*</span></label>
                                        <input type="text" name="account_no" value="{{$vendor->account_no}}"
                                               class="form-control" id="account_no" maxlength="10"
                                               placeholder="0123456789" required>
                                        <div id="account_resolution_status" class="mt-1 font-size-sm"></div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="holder_name" class="title-color">{{translate('account_Holder_Name')}} <span class="text-danger">*</span></label>
                                        <input type="text" name="holder_name" value="{{$vendor->holder_name}}"
                                               class="form-control" id="holder_name"
                                               placeholder="{{ translate('verified_account_name') }}" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="branch_input" class="title-color">{{translate('branch_Code_or_Name')}} <span class="text-danger">*</span></label>
                                        <input type="text" name="branch" value="{{$vendor->branch ?: 'Main Branch'}}"
                                               class="form-control" id="branch_input" required>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a class="btn btn-danger" href="{{route('vendor.profile.index')}}">{{translate('cancel')}}</a>
                                <button type="submit" class="btn btn--primary" id="btn_update">{{translate('update_Bank_Info')}}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const bankSelect = document.getElementById('bank_select');
        const bankNameInput = document.getElementById('bank_name_input');
        const accountNoInput = document.getElementById('account_no');
        const holderNameInput = document.getElementById('holder_name');
        const statusBox = document.getElementById('account_resolution_status');
        const resolveUrl = "{{ route('vendor.profile.resolve-bank-account') }}";
        const csrfToken = "{{ csrf_token() }}";

        function checkAndResolveAccount() {
            const selectedOption = bankSelect.options[bankSelect.selectedIndex];
            const bankCode = bankSelect.value;
            const bankName = selectedOption?.getAttribute('data-name') || '';
            const accountNo = accountNoInput.value.trim();

            if (bankName) {
                bankNameInput.value = bankName;
            }

            if (accountNo.length === 10 && bankCode) {
                statusBox.innerHTML = `<span class="text-info"><i class="tio-sync spin"></i> {{ translate('resolving_NUBAN_account_name') }}...</span>`;

                fetch(resolveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        account_number: accountNo,
                        bank_code: bankCode
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status && data.data && data.data.account_name) {
                        holderNameInput.value = data.data.account_name;
                        statusBox.innerHTML = `<span class="text-success font-weight-bold"><i class="tio-checkmark-circle"></i> {{ translate('verified') }}: ${data.data.account_name}</span>`;
                    } else {
                        statusBox.innerHTML = `<span class="text-danger"><i class="tio-clear-circle"></i> ${data.message || '{{ translate("could_not_resolve_account") }}'}</span>`;
                    }
                })
                .catch(() => {
                    statusBox.innerHTML = `<span class="text-warning"><i class="tio-warning-outlined"></i> {{ translate("live_verification_unavailable_check_connection") }}</span>`;
                });
            } else if (accountNo.length > 0 && accountNo.length < 10) {
                statusBox.innerHTML = `<span class="text-muted">{{ translate('enter_10_digits_to_verify') }}</span>`;
            } else {
                statusBox.innerHTML = '';
            }
        }

        bankSelect?.addEventListener('change', checkAndResolveAccount);
        accountNoInput?.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '');
            checkAndResolveAccount();
        });
    });
</script>
@endpush

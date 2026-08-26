@extends('layouts.back-end.app')

@section('title', translate('POS_Configuration_&_Pricing'))

@section('content')
<div class="content container-fluid">
    <div class="mb-4 pb-2">
        <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/pos.png') }}" width="20" alt="">
            {{ translate('POS_SaaS_Pricing_&_Receipt_Branding_Settings') }}
        </h2>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form action="{{ route('admin.pos-management.settings.update') }}" method="POST">
                @csrf
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="title-color d-flex gap-1 fw-bold">{{ translate('Free_Branch_Limit_(Per_Vendor)') }}</label>
                        <input type="number" name="pos_free_branch_limit" class="form-control" value="{{ $freeBranchLimit }}" min="1" required>
                        <small class="text-muted">{{ translate('Number_of_physical_store_locations_vendors_can_use_for_free_(Default:_1).') }}</small>
                    </div>

                    <div class="col-md-6">
                        <label class="title-color d-flex gap-1 fw-bold">{{ translate('Multi-Branch_Pro_Monthly_Price_(NGN)') }}</label>
                        <input type="number" name="pos_multi_branch_monthly_price" class="form-control" value="{{ $monthlyPrice }}" min="0" step="100" required>
                        <small class="text-muted">{{ translate('Monthly_SaaS_fee_charged_for_unlimited_branches_and_waybill_theft_tracking.') }}</small>
                    </div>

                    <div class="col-md-6">
                        <label class="title-color d-flex gap-1 fw-bold">{{ translate('Multi-Branch_Pro_Annual_Price_(NGN)') }}</label>
                        <input type="number" name="pos_multi_branch_annual_price" class="form-control" value="{{ $annualPrice }}" min="0" step="1000" required>
                        <small class="text-muted">{{ translate('Discounted_yearly_subscription_rate.') }}</small>
                    </div>

                    <div class="col-md-6">
                        <label class="title-color d-flex gap-1 fw-bold">{{ translate('Pro_Trial_Period_(Days)') }}</label>
                        <input type="number" name="pos_trial_days" class="form-control" value="{{ $trialDays }}" min="0" required>
                        <small class="text-muted">{{ translate('Free_trial_days_given_to_merchants_before_first_billing.') }}</small>
                    </div>

                    <div class="col-12">
                        <label class="title-color d-flex gap-1 fw-bold">{{ translate('Thermal_Receipt_Mandatory_Viral_Footer_Text') }}</label>
                        <input type="text" name="pos_receipt_footer_text" class="form-control" value="{{ $receiptFooter }}" required>
                        <small class="text-muted">{{ translate('Printed_permanently_at_the_bottom_of_all_merchant_58mm/80mm_receipts.') }}</small>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="pos_reorder_qr_status" id="reorderQr" {{ $reorderQrStatus ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="reorderQr">
                                {{ translate('Print_Dynamic_Online_Re-Order_QR_Code_on_Physical_Receipts') }}
                            </label>
                        </div>
                        <small class="text-muted">{{ translate('Allows_walk-in_customers_to_scan_the_paper_receipt_at_home_to_reorder_online.') }}</small>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top">
                    <button type="reset" class="btn btn-secondary px-4">{{ translate('Reset') }}</button>
                    <button type="submit" class="btn btn--primary px-4">{{ translate('Save_Configuration') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

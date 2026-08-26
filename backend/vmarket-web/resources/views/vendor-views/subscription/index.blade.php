@extends('layouts.back-end.app-seller')

@section('title', translate('POS_Subscription_&_Marketplace_Settings'))

@section('content')
<div class="content container-fluid">
    <div class="mb-4 pb-2">
        <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/pos.png') }}" width="20" alt="">
            {{ translate('POS_Plan_&_Marketplace_Selling_Status') }}
        </h2>
    </div>

    <div class="row g-4">
        <!-- POS Plan Status Card -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-bold">📦 {{ translate('Your_Current_POS_Plan') }}</h5>
                </div>
                <div class="card-body">
                    @if($activeSub && $activeSub->isCurrentlyActive())
                        <div class="p-3 bg-light-success rounded mb-3">
                            <span class="badge bg-success mb-2">{{ translate('MULTI-BRANCH_PRO_ACTIVE') }}</span>
                            <h4 class="fw-bold mb-1">{{ translate('Unlimited_Physical_Branches_&_Waybills') }}</h4>
                            <p class="text-muted fs-12 mb-0">
                                {{ translate('Renews_/_Expires_on') }}: <strong>{{ $activeSub->expires_at ? $activeSub->expires_at->format('d M Y') : translate('Never') }}</strong>
                            </p>
                        </div>
                    @else
                        <div class="p-3 bg-light rounded mb-3">
                            <span class="badge bg-primary mb-2">{{ translate('FREE_STARTER_PLAN') }}</span>
                            <h4 class="fw-bold mb-1">1 {{ translate('Physical_Store_Location') }}</h4>
                            <p class="text-muted fs-12 mb-0">
                                {{ translate('Free_Forever._Upgrade_to_Pro_to_add_multiple_shops_and_waybill_tracking.') }}
                            </p>
                        </div>

                        <!-- Upgrade Form -->
                        <form action="{{ route('vendor.subscription.subscribe') }}" method="POST">
                            @csrf
                            <input type="hidden" name="plan_type" value="multi_branch_pro">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ translate('Choose_Billing_Cycle') }}</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="billing_cycle" id="monthly" value="monthly" checked>
                                        <label class="form-check-label" for="monthly">
                                            {{ translate('Monthly') }} (<strong>{{ setCurrencySymbol(amount: $monthlyPrice, currencyCode: getCurrencyCode()) }}/mo</strong>)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="billing_cycle" id="yearly" value="yearly">
                                        <label class="form-check-label" for="yearly">
                                            {{ translate('Annual_(2_Months_Free)') }} (<strong>{{ setCurrencySymbol(amount: $annualPrice, currencyCode: getCurrencyCode()) }}/yr</strong>)
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ translate('Payment_Method') }}</label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="wallet">💰 {{ translate('Pay_from_Vendor_Wallet_Balance') }} ({{ setCurrencySymbol(amount: $walletBalance, currencyCode: getCurrencyCode()) }})</option>
                                    <option value="paystack">💳 {{ translate('Pay_with_Paystack_(Card_/_Bank_Transfer_/_USSD)') }}</option>
                                    <option value="offline_payment">🏦 {{ translate('Offline_Direct_Bank_Deposit') }}</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn--primary w-100 py-2 fw-bold">
                                🚀 {{ translate('Upgrade_to_Multi-Branch_Pro') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <!-- Online Marketplace Activation Card -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-bold">🌐 {{ translate('Victorious_Marketplace_Online_Selling') }}</h5>
                </div>
                <div class="card-body">
                    @if($seller->marketplace_status === 'approved')
                        <div class="p-3 bg-light-success rounded text-center mb-3">
                            <span class="fs-32">🎉</span>
                            <h4 class="fw-bold text-success mt-2">{{ translate('Your_Online_Storefront_is_LIVE!') }}</h4>
                            <p class="text-muted fs-12 mb-0">
                                {{ translate('Your_selected_POS_products_are_now_visible_to_thousands_of_online_shoppers_on_VictoriousMarket.com.ng_and_the_Customer_App.') }}
                            </p>
                        </div>
                    @elseif($seller->marketplace_status === 'pending_approval')
                        <div class="p-3 bg-light-warning rounded text-center mb-3">
                            <span class="fs-32">⏳</span>
                            <h4 class="fw-bold text-warning mt-2">{{ translate('Application_Under_Review') }}</h4>
                            <p class="text-muted fs-12 mb-0">
                                {{ translate('Your_bank_payout_details_and_KYC_documents_are_being_reviewed_by_Super_Admin._Your_in-store_POS_remains_100%_active_and_operational.') }}
                            </p>
                        </div>
                    @else
                        <p class="text-muted fs-13">
                            {{ translate('Currently,_your_POS_operates_privately_for_in-store_walk-in_sales._Apply_below_to_start_receiving_online_marketplace_orders_and_doorstep_delivery_pickups.') }}
                        </p>

                        <form action="{{ route('vendor.subscription.apply-marketplace') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ translate('Bank_Name_(For_Online_Sales_Payouts)') }}</label>
                                <input type="text" name="bank_name" class="form-control" placeholder="{{ translate('e.g._Access_Bank,_Zenith_Bank') }}" value="{{ $seller->bank_name }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ translate('10-Digit_NUBAN_Account_Number') }}</label>
                                <input type="text" name="account_no" class="form-control" maxlength="10" placeholder="0123456789" value="{{ $seller->account_no }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ translate('Account_Holder_Name') }}</label>
                                <input type="text" name="holder_name" class="form-control" placeholder="{{ translate('As_registered_with_bank') }}" value="{{ $seller->holder_name }}" required>
                            </div>

                            <button type="submit" class="btn btn-success w-100 py-2 fw-bold">
                                📝 {{ translate('Submit_Application_to_Sell_Online') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

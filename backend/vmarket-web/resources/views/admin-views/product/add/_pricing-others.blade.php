<div class="price_wrapper mt-3">
    <div class="outline-wrapper">
        <div class="card rest-part bg-animate">
            <div class="card-header d-flex justify-content-between align-items-center border-0 pb-0 pc-header-ai-btn">
                <div>
                    <h2 class="mb-1">{{ translate('Pricing_&_Others') }}</h2>
                    <p class="fs-12 mb-0">
                        {{ translate('Here_you_can_setup_the_price_and_other_information_for_the_product.') }}
                    </p>
                </div>

                @if(getActiveAIProviderConfigCache())
                <button type="button"
                    class="btn bg-white text-primary bg-transparent shadow-none border-0 opacity-1 generate_btn_wrapper p-0 price_others_auto_fill"
                    id="price_others_auto_fill"
                        data-route="{{ route('admin.product.price-others-auto-fill') }}"  data-lang="en">
                    <div class="btn-svg-wrapper">
                        <img width="18" height="18" class=""
                            src="{{ dynamicAsset(path: 'public/assets//back-end/img/ai/blink-right-small.svg') }}" alt="">
                    </div>
                    <span class="ai-text-animation d-none" role="status">
                        {{ translate('Just_a_second') }}
                    </span>
                    <span class="btn-text">{{ translate('Generate') }}</span>
                </button>
                @endif
            </div>
            <div class="card-body">
                <div class="bg-section rounded-10 p-12 p-sm-20">
                    <div class="row gy-4 align-items-end">
                        <div class="col-md-6 col-lg-4">
                            <div class="form-group">
                                <label class="form-label">
                                    {{ translate('Unit_Price') }}
                                    <span class="input-required-icon">*</span>
                                    ({{ getCurrencySymbol(currencyCode: getCurrencyCode()) }})
                                    <span class="tooltip-icon cursor-pointer" data-bs-toggle="tooltip"
                                          aria-label="{{ translate('set_the_selling_price_for_each_unit_of_this_product._This_Unit_Price_section_would_not_be_applied_if_you_set_a_variation_wise_price') }}"
                                          data-bs-title="{{ translate('set_the_selling_price_for_each_unit_of_this_product._This_Unit_Price_section_would_not_be_applied_if_you_set_a_variation_wise_price') }}"
                                    >
                                        <i class="fi fi-sr-info"></i>
                                    </span>
                                </label>

                                <input type="number" min="0" step="0.01"
                                       placeholder="{{ translate('Unit_Price') }}" name="unit_price" id="unit_price"
                                       value="{{ old('unit_price') }}" class="form-control"  data-required-msg="{{ translate('unit_price_is_required') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4" id="">
                            <div class="form-group">
                                <label class="form-label" for="minimum_order_qty">
                                    {{ translate('Minimum_Order_Qty') }}
                                    ({{ getCurrencySymbol(currencyCode: getCurrencyCode()) }})
                                    <span class="input-required-icon">*</span>
                                    <span class="tooltip-icon cursor-pointer" data-bs-toggle="tooltip"
                                          aria-label="{{ translate('set_the_minimum_order_quantity_that_customers_must_choose._Otherwise,_the_checkout_process_would_not_start') }}."
                                          data-bs-title="{{ translate('set_the_minimum_order_quantity_that_customers_must_choose._Otherwise,_the_checkout_process_would_not_start') }}."
                                    >
                                        <i class="fi fi-sr-info"></i>
                                    </span>
                                </label>
                                <input type="number" min="1" value="1" step="1"
                                       placeholder="{{ translate('Minimum_Order_Quantity') }}" name="minimum_order_qty"
                                       id="minimum_order_qty" class="form-control only-number-input">
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4 show-for-physical-product" id="quantity">
                            <div class="form-group">
                                <label class="form-label" for="current_stock">
                                    {{ translate('Current_Stock_Qty') }}
                                    <span class="input-required-icon">*</span>
                                    <span class="tooltip-icon cursor-pointer" data-bs-toggle="tooltip"
                                          aria-label="{{ translate('add_the_Stock_Quantity_of_this_product_that_will_be_visible_to_customers') }}."
                                          data-bs-title="{{ translate('add_the_Stock_Quantity_of_this_product_that_will_be_visible_to_customers') }}."
                                    >
                                        <i class="fi fi-sr-info"></i>
                                    </span>
                                </label>

                                <input type="number" min="0" value="0" step="1"
                                       placeholder="{{ translate('quantity') }}" name="current_stock" id="current_stock"
                                       class="form-control only-number-input"  data-required-msg="{{ translate('current_stock_is_required') }}" required>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4">
                            <div class="form-group">
                                <label class="form-label" for="discount">
                                    {{ translate('Discount_Amount') }}
                                    <span class="discount-amount-symbol" data-percent="%"
                                          data-currency="{{ getCurrencySymbol(currencyCode: getCurrencyCode()) }}">
                                        ({{ getCurrencySymbol(currencyCode: getCurrencyCode()) }})
                                    </span>
                                    <span class="input-required-icon">*</span>
                                    <span class="tooltip-icon cursor-pointer" data-bs-toggle="tooltip"
                                          aria-label="{{ translate('add_the_discount_amount_in_percentage_or_a_fixed_value_here') }}."
                                          data-bs-title="{{ translate('add_the_discount_amount_in_percentage_or_a_fixed_value_here') }}."
                                    >
                                        <i class="fi fi-sr-info"></i>
                                    </span>
                                </label>
                                <div class="input-group">
                                    <input type="number" min="0" value="0" step="0.01"
                                           placeholder="{{ translate('ex: 5') }}"
                                           name="discount" id="discount" class="form-control"  data-required-msg="{{ translate('discount_is_required') }}" required>
                                    <div class="input-group-append select-wrapper">
                                        <select class="form-control form-select shadow-none product-discount-type" name="discount_type" id="product-discount-type">
                                            <option value="flat">{{ translate('flat') }}</option>
                                            <option value="percent">{{ translate('percent') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($productWiseTax)
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label class="form-label" for="">
                                        {{ translate('Select_Vat/Tax_Rate') }}
                                        <span class="input-required-icon">*</span>
                                    </label>

                                    <select class="custom-select multiple-select2 multiple-select-tax-input" name="tax_ids[]" multiple="multiple"
                                            data-placeholder="{{ translate('Type_&_Select_Vat/Tax_Rate') }}">
                                        @foreach ($taxVats as $taxVat)
                                            <option value="{{ $taxVat->id }}"> {{ $taxVat->name }}
                                                ({{ $taxVat->tax_rate }}%)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endif
                        <input type="hidden" name="shipping_cost" value="0">
                        <input type="hidden" name="multiply_qty" value="0">
                    </div>
                </div>
            </div>
        </div>
    </div>



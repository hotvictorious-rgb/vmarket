@php
    $isAdmin = auth('admin')->check();
    $fetchSpecsUrl = $isAdmin ? route('admin.category-specifications.get-by-category', ['category_id' => ':cat_id']) : route('vendor.products.get-category-specifications', ['category_id' => ':cat_id']);
    $aiSpecsUrl = $isAdmin ? route('admin.category-specifications.ai-suggest-specs') : route('vendor.products.ai-suggest-specs');
    $existingSpecs = isset($product) && !empty($product->specifications) ? (is_array($product->specifications) ? $product->specifications : json_decode($product->specifications, true)) : [];
@endphp

<div class="col-12 mt-3" id="smart-specs-wrapper" style="display: none;">
    <div class="card border border-primary-light shadow-sm" style="border: 1.5px solid #4A154B25 !important; border-radius: 12px;">
        <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center gap-2" style="background: linear-gradient(135deg, #FAF4FB 0%, #FFFFFF 100%); border-bottom: 1px solid #4A154B15;">
            <div>
                <h4 class="mb-0 font-weight-bold d-flex align-items-center gap-2" style="color: #4A154B;">
                    <i class="tio-tune" style="font-size: 20px; color: #D4AF37;"></i>
                    {{ translate('Product Specifications & Attributes') }}
                </h4>
                <p class="fs-12 text-muted mb-0">
                    {{ translate('Tailored questions for the selected category. Complete these specifications to boost Google SEO and search ranking.') }}
                </p>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1" id="btn-ai-auto-fill-specs" style="border-color: #4A154B; color: #4A154B;">
                <i class="tio-flash" style="color: #D4AF37;"></i>
                <span id="ai-auto-fill-text">{{ translate('✨ Auto-Fill Specs with AI') }}</span>
                <span id="ai-auto-fill-spinner" class="spinner-border spinner-border-sm text-primary d-none" role="status"></span>
            </button>
        </div>
        <div class="card-body p-3 p-md-4">
            <div class="row g-3" id="dynamic-specs-fields-container">
                <!-- Dynamically injected specification questions -->
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const fetchUrlTemplate = "{{ $fetchSpecsUrl }}";
    const aiUrl = "{{ $aiSpecsUrl }}";
    const existingSpecs = @json($existingSpecs ?? []);

    function loadCategorySpecifications(catId) {
        if (!catId) {
            $('#smart-specs-wrapper').slideUp();
            return;
        }

        const url = fetchUrlTemplate.replace(':cat_id', catId);
        $.get(url, function (res) {
            if (res && res.status && res.specifications && res.specifications.length > 0) {
                renderSpecificationFields(res.specifications);
                $('#smart-specs-wrapper').slideDown();
            } else {
                $('#smart-specs-wrapper').slideUp();
                $('#dynamic-specs-fields-container').empty();
            }
        }).fail(function() {
            $('#smart-specs-wrapper').slideUp();
        });
    }

    function renderSpecificationFields(specs) {
        const container = $('#dynamic-specs-fields-container');
        container.empty();

        specs.forEach(function (spec) {
            const specName = spec.name;
            const currentVal = existingSpecs[specName] || '';
            const isReq = spec.is_required ? '<span class="text-danger">*</span>' : '';
            const reqAttr = spec.is_required ? 'required' : '';
            let inputHtml = '';

            if (spec.input_type === 'select' && spec.options && spec.options.length > 0) {
                let optionsHtml = `<option value="">{{ translate('Select') }} ${specName}</option>`;
                spec.options.forEach(function (opt) {
                    const selected = currentVal == opt ? 'selected' : '';
                    optionsHtml += `<option value="${opt}" ${selected}>${opt}</option>`;
                });
                inputHtml = `
                    <select name="specifications[${specName}]" class="form-control spec-input-field" ${reqAttr} data-spec-name="${specName}">
                        ${optionsHtml}
                    </select>
                `;
            } else if (spec.input_type === 'multi_select' && spec.options && spec.options.length > 0) {
                let multiArray = Array.isArray(currentVal) ? currentVal : (currentVal ? currentVal.split(',').map(s=>s.trim()) : []);
                let optionsHtml = '';
                spec.options.forEach(function (opt) {
                    const checked = multiArray.includes(opt) ? 'checked' : '';
                    optionsHtml += `
                        <div class="form-check form-check-inline mr-2 mb-1">
                            <input class="form-check-input" type="checkbox" name="specifications[${specName}][]" value="${opt}" id="opt_${opt.replace(/\s+/g, '_')}" ${checked}>
                            <label class="form-check-label fs-13" for="opt_${opt.replace(/\s+/g, '_')}">${opt}</label>
                        </div>
                    `;
                });
                inputHtml = `<div class="d-flex flex-wrap pt-1">${optionsHtml}</div>`;
            } else if (spec.input_type === 'number') {
                const unitBadge = spec.unit ? `<span class="input-group-text">${spec.unit}</span>` : '';
                inputHtml = `
                    <div class="input-group">
                        <input type="number" step="any" name="specifications[${specName}]" class="form-control spec-input-field" placeholder="${spec.placeholder || ''}" value="${currentVal}" ${reqAttr} data-spec-name="${specName}">
                        ${unitBadge}
                    </div>
                `;
            } else {
                const unitBadge = spec.unit ? `<span class="input-group-text">${spec.unit}</span>` : '';
                inputHtml = `
                    <div class="input-group">
                        <input type="text" name="specifications[${specName}]" class="form-control spec-input-field" placeholder="${spec.placeholder || ''}" value="${currentVal}" ${reqAttr} data-spec-name="${specName}">
                        ${unitBadge}
                    </div>
                `;
            }

            const fieldCard = `
                <div class="col-md-6 col-lg-4">
                    <div class="form-group mb-2">
                        <label class="title-color font-weight-bold fs-13">${specName} ${isReq}</label>
                        ${inputHtml}
                    </div>
                </div>
            `;
            container.append(fieldCard);
        });
    }

    // Trigger on Category Selector change
    $(document).on('change', '#category_id_selector, select[name="category_id"]', function () {
        const selected = $(this).find(':selected');
        let catId = selected.data('sub-sub-category') || selected.data('sub-category') || selected.data('category') || $(this).val();
        loadCategorySpecifications(catId);
    });

    // Check initial state on page load (e.g. edit mode)
    setTimeout(function() {
        const initialSelector = $('#category_id_selector, select[name="category_id"]').first();
        if (initialSelector.length && initialSelector.val()) {
            const selected = initialSelector.find(':selected');
            let catId = selected.data('sub-sub-category') || selected.data('sub-category') || selected.data('category') || initialSelector.val();
            if (catId) {
                loadCategorySpecifications(catId);
            }
        }
    }, 500);

    // AI Auto-Fill Specs Button
    $('#btn-ai-auto-fill-specs').on('click', function () {
        const selected = $('#category_id_selector, select[name="category_id"]').find(':selected');
        let catId = selected.data('sub-sub-category') || selected.data('sub-category') || selected.data('category') || $('#category_id_selector, select[name="category_id"]').val();
        
        let productName = $('input[name="name[]"], input[name="name"]').first().val();
        let productDetails = $('textarea[name="details[]"], textarea[name="details"]').first().val() || '';

        if (!productName || productName.trim() === '') {
            if (typeof toastr !== 'undefined') {
                toastr.warning("{{ translate('Please enter the Product Name / Title first so AI can analyze it.') }}");
            } else {
                alert("{{ translate('Please enter the Product Name / Title first.') }}");
            }
            return;
        }

        $('#ai-auto-fill-text').text("{{ translate('Extracting...') }}");
        $('#ai-auto-fill-spinner').removeClass('d-none');
        $('#btn-ai-auto-fill-specs').prop('disabled', true);

        $.post(aiUrl, {
            _token: '{{ csrf_token() }}',
            category_id: catId,
            product_name: productName,
            product_details: productDetails
        }, function (res) {
            $('#ai-auto-fill-text').text("{{ translate('✨ Auto-Fill Specs with AI') }}");
            $('#ai-auto-fill-spinner').addClass('d-none');
            $('#btn-ai-auto-fill-specs').prop('disabled', false);

            if (res && res.status && res.suggestions) {
                let filledCount = 0;
                Object.keys(res.suggestions).forEach(function (specKey) {
                    const val = res.suggestions[specKey];
                    if (val) {
                        const input = $(`.spec-input-field[data-spec-name="${specKey}"]`);
                        if (input.length) {
                            input.val(val).trigger('change');
                            input.css('background-color', '#E8F5E9').animate({backgroundColor: '#FFFFFF'}, 1500);
                            filledCount++;
                        }
                    }
                });

                if (typeof toastr !== 'undefined') {
                    toastr.success(`{{ translate('AI auto-filled') }} ${filledCount} {{ translate('specifications from product info!') }}`);
                }
            } else {
                if (typeof toastr !== 'undefined') {
                    toastr.info("{{ translate('AI could not determine additional specs for this item.') }}");
                }
            }
        }).fail(function() {
            $('#ai-auto-fill-text').text("{{ translate('✨ Auto-Fill Specs with AI') }}");
            $('#ai-auto-fill-spinner').addClass('d-none');
            $('#btn-ai-auto-fill-specs').prop('disabled', false);
        });
    });
});
</script>

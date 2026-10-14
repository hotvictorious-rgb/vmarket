"use strict";

/**
 * Victorious MARKET — Storefront Cart Page Checkbox & Flow Controller
 * Unifies desktop/mobile checkbox selection with seamless AJAX sync.
 */

function proceedToNextAction() {
    $('#proceed-to-next-action').off('click').on('click', function () {
        let orderNote = $('#order_note').val();
        let $btn = $(this);
        let origHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status"></span> Processing...');

        $.post({
            url: $('#order_note_url').data('url'),
            data: {
                _token: $('meta[name="_token"]').attr('content'),
                order_note: orderNote,
            },
            success: function (response) {
                if (response.status === 0) {
                    $btn.prop('disabled', false).html(origHtml);
                    if (Array.isArray(response.message)) {
                        response.message.forEach(function (msg) {
                            toastr.error(msg);
                        });
                    } else if (response.message) {
                        toastr.error(response.message);
                    }
                } else {
                    location.href = response.redirect ? response.redirect : $('#route-checkout-details').data('url');
                }
            },
            error: function () {
                $btn.prop('disabled', false).html(origHtml);
                toastr.error('Unable to proceed to checkout. Please try again.');
            }
        });
    });
}

function multipleCheckBoxFunctionsInit() {
    $('.cart_information').each(function () {
        let allShopItemsChecked = true;
        let $items = $(this).find('.shop-item-check');
        if ($items.length === 0) {
            allShopItemsChecked = false;
        } else {
            $items.each(function () {
                if (!$(this).prop('checked')) {
                    allShopItemsChecked = false;
                    return false;
                }
            });
        }
        $(this).find('.shop-head-check').prop('checked', allShopItemsChecked);
    });

    $('.shop-head-check').off('change').on('change', function () {
        let isChecked = this.checked;
        $(this).closest('.cart_information').find('.shop-item-check').prop('checked', isChecked);
        collectCheckedItemsAndSync();
    });

    $('.shop-item-check').off('change').on('change', function () {
        let $cartInfo = $(this).closest('.cart_information');
        let allChecked = true;

        $cartInfo.find('.shop-item-check').each(function () {
            if (!$(this).prop('checked')) {
                allChecked = false;
                return false;
            }
        });

        $cartInfo.find('.shop-head-check').prop('checked', allChecked);
        collectCheckedItemsAndSync();
    });

    function collectCheckedItemsAndSync() {
        let checkedValues = [];
        $('.cart_information input[type="checkbox"].shop-item-check').each(function () {
            if ($(this).prop('checked')) {
                let val = $(this).val();
                if (val && !checkedValues.includes(val)) {
                    checkedValues.push(val);
                }
            }
        });
        sendCartItemSelectionRequest(checkedValues);
    }

    function sendCartItemSelectionRequest(checkedValues) {
        let $summary = $('.vm-summary-card');
        $summary.addClass('opacity-50');

        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') }
        });

        $.ajax({
            url: $('#get-cart-select-cart-items').data('route'),
            type: "POST",
            data: {
                ids: checkedValues
            },
            success: function (response) {
                if (response && response.htmlView) {
                    $('#cart-summary').empty().html(response.htmlView);
                    if (typeof updateNavCart === 'function') {
                        updateNavCart();
                    }
                }
                if (typeof initTooltip === 'function') initTooltip();
                proceedToNextAction();
                if (typeof updateCartQuantityListCartData === 'function') updateCartQuantityListCartData();
                if (typeof setShippingIdFunction === 'function') setShippingIdFunction();
                if (typeof updateCartQuantityListMobileCartData === 'function') updateCartQuantityListMobileCartData();
                if (typeof renderCouponCodeApply === 'function') renderCouponCodeApply();
                multipleCheckBoxFunctionsInit();
            },
            error: function () {
                $summary.removeClass('opacity-50');
                toastr.error('Unable to update cart selection.');
            }
        });
    }
}

// Initialize on page load
$(document).ready(function () {
    proceedToNextAction();
    multipleCheckBoxFunctionsInit();
});

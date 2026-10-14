"use strict";

/**
 * Victorious MARKET — Production Storefront Cart AJAX Controller
 * Smooth micro-animations, responsive feedback, zero-layout-shift updates.
 */

function updateCartQuantityListCartData() {
    $('.update-cart-quantity-list-cart-data').off('click').on('click', function (e) {
        e.preventDefault();
        let minOrder = $(this).data('min-order') || 1;
        let cart = $(this).data('cart');
        let value = $(this).data('value');
        let action = $(this).data('action');
        updateCartQuantityList(minOrder, cart, value, action);
    });

    $('.update-cart-quantity-list-cart-data-input').off('change').on('change', function () {
        let minOrder = $(this).data('min-order') || 1;
        let cart = $(this).data('cart');
        let currentVal = parseInt($(this).val()) || minOrder;
        let originalVal = parseInt($(this).data('min')) || minOrder;
        let value = currentVal - originalVal;
        let action = currentVal < minOrder ? 'delete' : (value < 0 ? 'minus' : 'plus');
        updateCartQuantityList(minOrder, cart, 0, action);
    });
}

function updateCartQuantityListMobileCartData() {
    $('.update-cart-quantity-list-mobile-cart-data').off('click').on('click', function (e) {
        e.preventDefault();
        let minOrder = $(this).data('min-order') || 1;
        let cart = $(this).data('cart');
        let value = $(this).data('value');
        let action = $(this).data('action');
        updateCartQuantityListMobile(minOrder, cart, value, action);
    });

    $('.update-cart-quantity-list-mobile-cart-data-input').off('change').on('change', function () {
        let minOrder = $(this).data('min-order') || 1;
        let cart = $(this).data('cart');
        updateCartQuantityListMobile(minOrder, cart, 0, '');
    });
}

function updateCartQuantityList(minimum_order_qty, key, incr, e) {
    let $input = $("#cartQuantityWeb" + key);
    if (!$input.length) $input = $("#cartQuantityMobile" + key);
    let currentQty = parseInt($input.val()) || 1;
    let quantity = currentQty + parseInt(incr);
    updateCartCommon(minimum_order_qty, key, e, quantity, $input);
}

function updateCartQuantityListMobile(minimum_order_qty, key, incr, e) {
    let $input = $("#cartQuantityMobile" + key);
    if (!$input.length) $input = $("#cartQuantityWeb" + key);
    let currentQty = parseInt($input.val()) || 1;
    let quantity = currentQty + parseInt(incr);
    updateCartCommon(minimum_order_qty, key, e, quantity, $input);
}

function updateCartCommon(minimum_order_qty, key, e, quantity, ex_quantity) {
    let maxStock = parseInt(ex_quantity.data('current-stock')) || 999999;

    if (quantity > maxStock) {
        toastr.warning(window.stock_limit_message || 'Maximum available quantity reached');
        ex_quantity.val(maxStock);
        return false;
    }

    if (quantity < minimum_order_qty && e !== 'delete') {
        toastr.error(($('.minimum_order_quantity_msg').data('text') || 'Minimum order quantity is') + ' ' + minimum_order_qty);
        ex_quantity.val(minimum_order_qty);
        return false;
    }

    if (e === 'delete' || quantity < 1) {
        removeProductFromCartList(key);
        return true;
    }

    let updateQuantityBasicUrl = $('#update-quantity-basic-url').data('url');
    let $itemRow = $('#cart-item-' + key);
    if ($itemRow.length) {
        $itemRow.addClass('is-updating');
    }

    $.post(updateQuantityBasicUrl, {
        _token: $('meta[name="_token"]').attr('content'),
        key: key,
        quantity: quantity
    }, function (response) {
        if (response.status === 0) {
            toastr.error(response.message, {
                CloseButton: true,
                ProgressBar: true
            });
            ex_quantity.val(response.qty);
            if ($itemRow.length) $itemRow.removeClass('is-updating');
        } else {
            if (typeof updateNavCart === 'function') {
                updateNavCart();
            }
            if ($('#cart-summary').length) {
                $('#cart-summary').empty().html(response);
            }
        }
        reinitializeCartInteractions();
    }).fail(function (xhr) {
        if ($itemRow.length) $itemRow.removeClass('is-updating');
        let msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Unable to update item quantity';
        toastr.error(msg);
    });
}

function removeProductFromCartList(key) {
    let remove_from_cart_url = $('#remove_from_cart_url').data('url');
    let $itemRow = $('#cart-item-' + key);

    if ($itemRow.length) {
        $itemRow.addClass('is-removing');
    }

    setTimeout(function () {
        $.post(remove_from_cart_url, {
            _token: $('meta[name="_token"]').attr('content'),
            key: key
        }, function (response) {
            if (typeof updateNavCart === 'function') {
                updateNavCart();
            }
            if (response.message) {
                toastr.info(response.message, {
                    CloseButton: true,
                    ProgressBar: true
                });
            }
            let segment_array = window.location.pathname.split('/');
            let segment = segment_array[segment_array.length - 1];
            if (segment === 'checkout-payment' || segment === 'checkout-details') {
                location.reload();
                return;
            }
            if ($('#cart-summary').length && response.data) {
                $('#cart-summary').empty().html(response.data);
            }
            reinitializeCartInteractions();
        }).fail(function () {
            if ($itemRow.length) $itemRow.removeClass('is-removing');
            toastr.error('Failed to remove product from cart');
        });
    }, 220);
}

function setShippingIdFunction() {
    $('.set-shipping-onchange').off('change').on('change', function () {
        let Id = $(this).val();
        setShippingId(Id, 'all_cart_group');
    });

    $('.set-shipping-id').off('click').on('click', function () {
        let Id = $(this).data('id');
        let cartGroupId = $(this).data('cart-group');
        setShippingId(Id, cartGroupId);
    });

    function setShippingId(Id, cartGroupId) {
        let $card = $('.set-shipping-id[data-cart-group="' + cartGroupId + '"]');
        $card.addClass('opacity-50');

        $.ajax({
            url: $('#set-shipping-url').data('url'),
            method: 'POST',
            dataType: 'json',
            data: {
                _token: $('meta[name="_token"]').attr('content'),
                id: Id,
                cart_group_id: cartGroupId
            },
            success: function (response) {
                if (response && response.data && $('#cart-summary').length) {
                    $('#cart-summary').empty().html(response.data);
                    if (typeof updateNavCart === 'function') {
                        updateNavCart();
                    }
                } else {
                    location.reload();
                }
                reinitializeCartInteractions();
            },
            error: function (xhr) {
                $card.removeClass('opacity-50');
                if (window.toastr && xhr.responseJSON && xhr.responseJSON.message) {
                    toastr.error(xhr.responseJSON.message);
                }
            }
        });
    }
}

function reinitializeCartInteractions() {
    if (typeof initTooltip === 'function') initTooltip();
    if (typeof proceedToNextAction === 'function') proceedToNextAction();
    updateCartQuantityListCartData();
    updateCartQuantityListMobileCartData();
    setShippingIdFunction();
    if (typeof renderCouponCodeApply === 'function') renderCouponCodeApply();
    if (typeof multipleCheckBoxFunctionsInit === 'function') multipleCheckBoxFunctionsInit();
}

// Initial binding
$(document).ready(function () {
    updateCartQuantityListCartData();
    updateCartQuantityListMobileCartData();
    setShippingIdFunction();
});

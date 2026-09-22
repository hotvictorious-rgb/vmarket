import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_app_bar_widget.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_button_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/models/pickup_reservation_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/screens/digital_payment_order_place_screen.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/controllers/checkout_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/profile/controllers/profile_contrroller.dart';
import 'package:flutter_sixvalley_ecommerce/features/splash/controllers/splash_controller.dart';
import 'package:flutter_sixvalley_ecommerce/helper/price_converter.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/show_custom_snakbar_widget.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:provider/provider.dart';
import 'dart:async';

/// Pickup Payment Screen — Post-acceptance payment with cashback toggle
class PickupPaymentScreen extends StatefulWidget {
  final PickupReservationModel reservation;

  const PickupPaymentScreen({super.key, required this.reservation});

  @override
  State<PickupPaymentScreen> createState() => _PickupPaymentScreenState();
}

class _PickupPaymentScreenState extends State<PickupPaymentScreen> {
  bool _useCashback = false;

  // Backend-computed financial fields - default to reservation values initially
  double? _backendFinalAmount;
  double? _backendCashbackDiscount;

  String? _errorMessage;
  Timer? _countdownTimer;
  Duration _timeRemaining = Duration.zero;

  @override
  void initState() {
    super.initState();
    _startCountdown();
  }

  @override
  void dispose() {
    _countdownTimer?.cancel();
    super.dispose();
  }

  double get _totalAmount => double.tryParse(widget.reservation.totalAmount ?? '0') ?? 0.0;

  double get _displayFinalAmount => _backendFinalAmount ?? _totalAmount;
  double get _displayCashbackDiscount => _backendCashbackDiscount ?? 0.0;

  void _startCountdown() {
    if (widget.reservation.expiresAt == null) return;

    try {
      final expiryDate = DateTime.parse(widget.reservation.expiresAt!).toLocal();
      _timeRemaining = expiryDate.difference(DateTime.now());

      if (_timeRemaining.isNegative) {
        _timeRemaining = Duration.zero;
      } else {
        _countdownTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
          setState(() {
            _timeRemaining = expiryDate.difference(DateTime.now());
            if (_timeRemaining.isNegative) {
              _timeRemaining = Duration.zero;
              timer.cancel();
            }
          });
        });
      }
    } catch (_) {
      // Ignored - fallback if custom parsing fails
    }
  }

  String _formatDuration(Duration duration) {
    if (duration.isNegative || duration == Duration.zero) return 'Expired';
    String twoDigits(int n) => n.toString().padLeft(2, "0");
    String twoDigitMinutes = twoDigits(duration.inMinutes.remainder(60));
    String twoDigitSeconds = twoDigits(duration.inSeconds.remainder(60));
    if (duration.inHours > 0) {
      return "${twoDigits(duration.inHours)}:$twoDigitMinutes:$twoDigitSeconds";
    }
    return "$twoDigitMinutes:$twoDigitSeconds";
  }

  Future<void> _initiatePayment(CheckoutController checkoutController) async {
    setState(() {
      _errorMessage = null;
    });

    final apiResponse = await checkoutController.payPickupReservation(
      reservationCode: widget.reservation.reservationCode ?? '',
      useCashback: _useCashback,
    );

    if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
      final data = apiResponse.response!.data;

      // Since pay endpoint could potentially just return the authorization_url
      // or return a final validation result, we proceed if we have a URL.
      if (data['status'] == true && data['authorization_url'] != null) {
        if (!mounted) return;
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) => DigitalPaymentOrderPlaceScreen(
              paymentUrl: data['authorization_url'],
              isPickupPayment: true,
              reservationCode: widget.reservation.reservationCode,
            ),
          ),
        );
      } else {
        setState(() {
          _errorMessage = data['message'] ?? 'Failed to initialize payment';
        });
      }
    } else {
      setState(() {
        _errorMessage = apiResponse.error?.toString() ?? 'Payment initialization failed. Please try again';
      });
      showCustomSnackBarWidget(_errorMessage, context, snackBarType: SnackBarType.error);
    }
  }

  // Called when cashback is toggled to preview the new final amounts from backend
  // Note: For actual backend preview endpoint if available. For now, assuming payment
  // endpoint handles the final preview immediately before launching Paystack.
  /*
  Future<void> _previewCashback(CheckoutController checkoutController) async {
    // If there's a dedicated preview endpoint, it would be called here and set
    // _backendFinalAmount / _backendCashbackDiscount
  }
  */

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppBar(
        title: getTranslated('payment', context) ?? 'Payment',
        isBackButtonExist: true,
      ),
      body: Consumer<CheckoutController>(
        builder: (context, checkoutController, _) {
          return Consumer2<ProfileController, SplashController>(
            builder: (context, profileProvider, splashController, _) {
              final userPoints = profileProvider.userInfoModel?.loyaltyPoint ?? 0;
              final isCashbackEligible = splashController.isChannelCashbackEligible('pickup') && userPoints > 0;
              final maxRedeemPercent = splashController.configModel?.loyaltyPointMaxOrderRedemptionPercentage ?? 10.0;

              // Backend-supplied expected cashback (shown as a badge/hint, independent of use_cashback toggle logic)
              final cashbackEarnPercent = widget.reservation.cashbackToEarn?.percent ?? 0.0;
              final cashbackEarnNaira = widget.reservation.cashbackToEarn?.estimatedNaira;

              return Column(
            children: [
              Expanded(
                child: ListView(
                  padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                  children: [
                    // Error Message
                    if (_errorMessage != null)
                      Container(
                        margin: const EdgeInsets.only(bottom: Dimensions.paddingSizeDefault),
                        padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                        decoration: BoxDecoration(
                          color: const Color(0xFFEF4444).withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: const Color(0xFFEF4444)),
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.error_outline, color: Color(0xFFEF4444)),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Text(
                                _errorMessage!,
                                style: titilliumRegular.copyWith(
                                  fontSize: Dimensions.fontSizeSmall,
                                  color: const Color(0xFFDC2626),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),

                    // Expiry Countdown Banner
                    if (widget.reservation.expiresAt != null)
                      Container(
                        margin: const EdgeInsets.only(bottom: Dimensions.paddingSizeDefault),
                        padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                        decoration: BoxDecoration(
                          color: _timeRemaining == Duration.zero
                              ? const Color(0xFFEF4444).withValues(alpha: 0.1)
                              : const Color(0xFFF59E0B).withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: _timeRemaining == Duration.zero
                                ? const Color(0xFFEF4444).withValues(alpha: 0.3)
                                : const Color(0xFFF59E0B).withValues(alpha: 0.3),
                          ),
                        ),
                        child: Row(
                          children: [
                            Icon(
                              Icons.timer_outlined,
                              color: _timeRemaining == Duration.zero
                                  ? const Color(0xFFEF4444)
                                  : const Color(0xFFD97706),
                              size: 20,
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    _timeRemaining == Duration.zero
                                        ? 'Reservation Expired'
                                        : 'Time Remaining: ${_formatDuration(_timeRemaining)}',
                                    style: titilliumBold.copyWith(
                                      fontSize: Dimensions.fontSizeDefault,
                                      color: _timeRemaining == Duration.zero
                                          ? const Color(0xFFDC2626)
                                          : const Color(0xFFB45309),
                                    ),
                                  ),
                                  Text(
                                    'Complete payment before expiry to secure your pickup items',
                                    style: titilliumRegular.copyWith(
                                      fontSize: Dimensions.fontSizeExtraSmall,
                                      color: Theme.of(context).hintColor,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),

                    // Reservation Summary Card
                    Container(
                      padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                      decoration: BoxDecoration(
                        color: Theme.of(context).cardColor,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: Theme.of(context).primaryColor.withValues(alpha: 0.15),
                        ),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Icon(Icons.qr_code_2_rounded, color: Theme.of(context).primaryColor, size: 20),
                              const SizedBox(width: 8),
                              Text(
                                widget.reservation.reservationCode ?? 'RES-XXXXXX',
                                style: titilliumBold.copyWith(
                                  fontSize: Dimensions.fontSizeDefault,
                                  color: Theme.of(context).primaryColor,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: Dimensions.paddingSizeSmall),
                          Row(
                            children: [
                              Icon(Icons.storefront_rounded, size: 16, color: Theme.of(context).hintColor),
                              const SizedBox(width: 6),
                              Expanded(
                                child: Text(
                                  widget.reservation.shop?.name ?? 'Victorious Store',
                                  style: titilliumRegular.copyWith(
                                    fontSize: Dimensions.fontSizeSmall,
                                    color: Theme.of(context).hintColor,
                                  ),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: Dimensions.paddingSizeDefault),

                    // Cashback Redeem Toggle
                    if (isCashbackEligible)
                      Container(
                        margin: const EdgeInsets.only(bottom: Dimensions.paddingSizeDefault),
                        padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                        decoration: BoxDecoration(
                          color: Theme.of(context).cardColor,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: _useCashback
                                ? Theme.of(context).primaryColor.withValues(alpha: 0.3)
                                : Theme.of(context).hintColor.withValues(alpha: 0.1),
                          ),
                        ),
                        child: Column(
                          children: [
                            Row(
                              children: [
                                Icon(
                                  Icons.stars_rounded,
                                  color: _useCashback ? Theme.of(context).primaryColor : Theme.of(context).hintColor,
                                  size: 24,
                                ),
                                const SizedBox(width: Dimensions.paddingSizeDefault),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        'Victorious Points (Cashback)',
                                        style: titilliumBold.copyWith(fontSize: Dimensions.fontSizeDefault),
                                      ),
                                      const SizedBox(height: 2),
                                      Text(
                                        'Available: ${userPoints.toStringAsFixed(0)} pts (Redeem up to ${maxRedeemPercent.toStringAsFixed(0)}%)',
                                        style: titilliumRegular.copyWith(
                                          fontSize: Dimensions.fontSizeSmall,
                                          color: Theme.of(context).hintColor,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                Switch(
                                  value: _useCashback,
                                  onChanged: (val) {
                                    setState(() => _useCashback = val);
                                  },
                                  activeColor: Theme.of(context).primaryColor,
                                ),
                              ],
                            ),
                            if (_useCashback && _displayCashbackDiscount > 0) ...[
                              const SizedBox(height: Dimensions.paddingSizeSmall),
                              Container(
                                padding: const EdgeInsets.all(12),
                                decoration: BoxDecoration(
                                  color: Theme.of(context).primaryColor.withValues(alpha: 0.08),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text(
                                      'Cashback Discount',
                                      style: titilliumSemiBold.copyWith(
                                        fontSize: Dimensions.fontSizeDefault,
                                        color: Theme.of(context).primaryColor,
                                      ),
                                    ),
                                    Text(
                                      '- ${PriceConverter.convertPrice(context, _displayCashbackDiscount)}',
                                      style: titilliumBold.copyWith(
                                        fontSize: Dimensions.fontSizeDefault,
                                        color: Theme.of(context).primaryColor,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),

                    // Order Summary
                    Container(
                      padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                      decoration: BoxDecoration(
                        color: Theme.of(context).cardColor,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: Theme.of(context).hintColor.withValues(alpha: 0.1),
                        ),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            getTranslated('order_summary', context) ?? 'Order Summary',
                            style: titilliumBold.copyWith(fontSize: Dimensions.fontSizeDefault),
                          ),
                          const SizedBox(height: Dimensions.paddingSizeDefault),

                          // Items
                          if (widget.reservation.items != null && widget.reservation.items!.isNotEmpty)
                            ...widget.reservation.items!.map((item) => Padding(
                              padding: const EdgeInsets.only(bottom: 8.0),
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Expanded(
                                    child: Text(
                                      '${item.quantity}x ${item.productName ?? ''}',
                                      style: titilliumRegular.copyWith(fontSize: Dimensions.fontSizeSmall),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                  Text(
                                    PriceConverter.convertPrice(context, double.tryParse(item.lineTotal ?? '0') ?? 0.0),
                                    style: titilliumRegular.copyWith(fontSize: Dimensions.fontSizeSmall),
                                  ),
                                ],
                              ),
                            )),

                          const Divider(height: 24),

                          // Subtotal
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                getTranslated('sub_total', context) ?? 'Subtotal',
                                style: titilliumRegular.copyWith(fontSize: Dimensions.fontSizeDefault),
                              ),
                              Text(
                                PriceConverter.convertPrice(context, _totalAmount),
                                style: titilliumRegular.copyWith(fontSize: Dimensions.fontSizeDefault),
                              ),
                            ],
                          ),

                          // Cashback Discount
                          if (_useCashback && _displayCashbackDiscount > 0) ...[
                            const SizedBox(height: 8),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  'Cashback Discount',
                                  style: titilliumRegular.copyWith(
                                    fontSize: Dimensions.fontSizeDefault,
                                    color: Theme.of(context).primaryColor,
                                  ),
                                ),
                                Text(
                                  '- ${PriceConverter.convertPrice(context, _displayCashbackDiscount)}',
                                  style: titilliumRegular.copyWith(
                                    fontSize: Dimensions.fontSizeDefault,
                                    color: Theme.of(context).primaryColor,
                                  ),
                                ),
                              ],
                            ),
                          ],

                          const Divider(height: 24),

                          // Total Payable
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                getTranslated('total_payable', context) ?? 'Total Payable',
                                style: titilliumBold.copyWith(fontSize: Dimensions.fontSizeLarge),
                              ),
                              Text(
                                PriceConverter.convertPrice(context, _displayFinalAmount),
                                style: titilliumBold.copyWith(
                                  fontSize: Dimensions.fontSizeLarge,
                                  color: Theme.of(context).primaryColor,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: Dimensions.paddingSizeDefault),

                    // Payment Info Banner
                    Container(
                      padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                      decoration: BoxDecoration(
                        color: const Color(0xFF3B82F6).withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: const Color(0xFF3B82F6).withValues(alpha: 0.3)),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.info_outline, color: Color(0xFF3B82F6), size: 20),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Text(
                              'Your payment will be processed securely via Paystack',
                              style: titilliumRegular.copyWith(
                                fontSize: Dimensions.fontSizeSmall,
                                color: const Color(0xFF1E40AF),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),

              // Pay Button (sticky at bottom)
              Container(
                padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                decoration: BoxDecoration(
                  color: Theme.of(context).cardColor,
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.05),
                      blurRadius: 8,
                      offset: const Offset(0, -2),
                    ),
                  ],
                ),
                child: CustomButton(
                  buttonText: checkoutController.isLoading
                      ? '${getTranslated('processing', context) ?? 'Processing'}...'
                      : '${getTranslated('pay_now', context) ?? 'Pay Now'} ${PriceConverter.convertPrice(context, _displayFinalAmount, isShowLongPrice: false)}',
                  onTap: checkoutController.isLoading ? null : () => _initiatePayment(checkoutController),
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}

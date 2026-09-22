import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_app_bar_widget.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_button_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/auth/controllers/auth_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/models/pickup_reservation_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/screens/digital_payment_order_place_screen.dart';
import 'package:flutter_sixvalley_ecommerce/features/profile/controllers/profile_contrroller.dart';
import 'package:flutter_sixvalley_ecommerce/features/splash/controllers/splash_controller.dart';
import 'package:flutter_sixvalley_ecommerce/helper/price_converter.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/utill/app_constants.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Pickup Payment Screen — Post-acceptance payment with cashback toggle
class PickupPaymentScreen extends StatefulWidget {
  final PickupReservationModel reservation;

  const PickupPaymentScreen({super.key, required this.reservation});

  @override
  State<PickupPaymentScreen> createState() => _PickupPaymentScreenState();
}

class _PickupPaymentScreenState extends State<PickupPaymentScreen> {
  bool _useCashback = false;
  bool _isLoading = false;
  String? _errorMessage;

  double get _totalAmount => double.tryParse(widget.reservation.totalAmount ?? '0') ?? 0.0;

  double get _cashbackDiscount {
    if (!_useCashback) return 0.0;

    final profileProvider = Provider.of<ProfileController>(context, listen: false);
    final splashController = Provider.of<SplashController>(context, listen: false);

    final userPoints = profileProvider.userInfoModel?.loyaltyPoint ?? 0;
    final exchangeRate = splashController.configModel?.loyaltyPointExchangeRate ?? 1.0;
    final maxRedeemPercent = (splashController.configModel?.loyaltyPointMaxOrderRedemptionPercentage ?? 10.0) / 100;

    final maxCap = _totalAmount * maxRedeemPercent;
    final pointsInNaira = userPoints * exchangeRate;

    return pointsInNaira > maxCap ? maxCap : pointsInNaira;
  }

  double get _finalAmount => _totalAmount - _cashbackDiscount;

  Future<void> _initiatePayment() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString(AppConstants.token);

      if (token == null || token.isEmpty) {
        setState(() {
          _isLoading = false;
          _errorMessage = 'Please log in to complete payment';
        });
        return;
      }

      final response = await http.post(
        Uri.parse('${AppConstants.baseUrl}/api/v1/customer/pickup-reservations/${widget.reservation.reservationCode}/pay'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: json.encode({
          'use_cashback': _useCashback ? 1 : 0,
          'payment_gateway': 'paystack',
          'ttl_minutes': 30,
        }),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['status'] == true && data['authorization_url'] != null) {
          setState(() => _isLoading = false);

          // Navigate to Paystack WebView
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
            _isLoading = false;
            _errorMessage = data['message'] ?? 'Failed to initialize payment';
          });
        }
      } else if (response.statusCode == 409) {
        final data = json.decode(response.body);
        setState(() {
          _isLoading = false;
          _errorMessage = data['message'] ?? 'Payment cannot be processed';
        });
      } else {
        setState(() {
          _isLoading = false;
          _errorMessage = 'Payment initialization failed. Please try again';
        });
      }
    } catch (e) {
      setState(() {
        _isLoading = false;
        _errorMessage = 'Network error. Please check your connection';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppBar(
        title: getTranslated('payment', context) ?? 'Payment',
        isBackButtonExist: true,
      ),
      body: Consumer2<ProfileController, SplashController>(
        builder: (context, profileProvider, splashController, _) {
          final userPoints = profileProvider.userInfoModel?.loyaltyPoint ?? 0;
          final isCashbackEligible = splashController.isChannelCashbackEligible('pickup') && userPoints > 0;
          final maxRedeemPercent = splashController.configModel?.loyaltyPointMaxOrderRedemptionPercentage ?? 10.0;

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
                            if (_useCashback && _cashbackDiscount > 0) ...[
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
                                      '- ${PriceConverter.convertPrice(context, _cashbackDiscount)}',
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
                          if (_useCashback && _cashbackDiscount > 0) ...[
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
                                  '- ${PriceConverter.convertPrice(context, _cashbackDiscount)}',
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
                                PriceConverter.convertPrice(context, _finalAmount),
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
                  buttonText: _isLoading
                      ? '${getTranslated('processing', context) ?? 'Processing'}...'
                      : '${getTranslated('pay_now', context) ?? 'Pay Now'} ${PriceConverter.convertPrice(context, _finalAmount, isShowLongPrice: false)}',
                  onTap: _isLoading ? null : _initiatePayment,
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}

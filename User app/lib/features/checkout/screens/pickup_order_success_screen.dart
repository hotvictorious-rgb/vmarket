import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_app_bar_widget.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_button_widget.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/show_custom_snakbar_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/dashboard/screens/dashboard_screen.dart';
import 'package:flutter_sixvalley_ecommerce/features/order_details/screens/order_details_screen.dart';
import 'package:flutter_sixvalley_ecommerce/helper/price_converter.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';

/// Pickup Order Success Screen — Shows OTP and cashback earned after payment
class PickupOrderSuccessScreen extends StatelessWidget {
  final int orderId;
  final String pickupVerificationCode;
  final double? cashbackEarned;
  final String shopName;
  final String? shopAddress;

  const PickupOrderSuccessScreen({
    super.key,
    required this.orderId,
    required this.pickupVerificationCode,
    this.cashbackEarned,
    required this.shopName,
    this.shopAddress,
  });

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) {
        if (!didPop) {
          Navigator.of(context).pushAndRemoveUntil(
            MaterialPageRoute(builder: (_) => const DashBoardScreen(pageIndex: 0)),
            (route) => false,
          );
        }
      },
      child: Scaffold(
        appBar: CustomAppBar(
          title: getTranslated('payment_done', context) ?? 'Payment Successful',
          isBackButtonExist: true,
          onBackPressed: () {
            Navigator.of(context).pushAndRemoveUntil(
              MaterialPageRoute(builder: (_) => const DashBoardScreen(pageIndex: 0)),
              (route) => false,
            );
          },
        ),
        body: ListView(
          padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
          children: [
            const SizedBox(height: Dimensions.paddingSizeSmall),

            // Success Icon and Title
            Center(
              child: Column(
                children: [
                  Container(
                    width: 80,
                    height: 80,
                    decoration: BoxDecoration(
                      color: const Color(0xFF10B981).withValues(alpha: 0.12),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.check_circle_rounded,
                      color: Color(0xFF10B981),
                      size: 56,
                    ),
                  ),
                  const SizedBox(height: Dimensions.paddingSizeDefault),
                  Text(
                    getTranslated('payment_done', context) ?? 'Payment Successful!',
                    style: titilliumBold.copyWith(
                      fontSize: Dimensions.fontSizeExtraLarge,
                      color: Theme.of(context).textTheme.bodyLarge?.color,
                    ),
                  ),
                  const SizedBox(height: Dimensions.paddingSizeExtraSmall),
                  Text(
                    '${getTranslated('order_id', context) ?? 'Order ID'}: #$orderId',
                    style: titilliumRegular.copyWith(
                      fontSize: Dimensions.fontSizeDefault,
                      color: Theme.of(context).hintColor,
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: Dimensions.paddingSizeLarge),

            // Pickup OTP Card
            Container(
              padding: const EdgeInsets.all(Dimensions.paddingSizeLarge),
              decoration: BoxDecoration(
                color: Theme.of(context).cardColor,
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.08),
                    blurRadius: 12,
                    offset: const Offset(0, 4),
                  ),
                ],
                border: Border.all(
                  color: Theme.of(context).primaryColor.withValues(alpha: 0.2),
                  width: 2,
                ),
              ),
              child: Column(
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(
                        Icons.qr_code_2_rounded,
                        color: Theme.of(context).primaryColor,
                        size: 28,
                      ),
                      const SizedBox(width: 12),
                      Text(
                        'Pickup Handover OTP',
                        style: titilliumBold.copyWith(
                          fontSize: Dimensions.fontSizeLarge,
                          color: Theme.of(context).textTheme.bodyLarge?.color,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: Dimensions.paddingSizeDefault),

                  // OTP Display
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 24,
                      vertical: 16,
                    ),
                    decoration: BoxDecoration(
                      color: Theme.of(context).primaryColor.withValues(alpha: 0.08),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          pickupVerificationCode,
                          style: titilliumBold.copyWith(
                            fontSize: 32,
                            color: Theme.of(context).primaryColor,
                            letterSpacing: 8,
                            fontFeatures: [const FontFeature.tabularFigures()],
                          ),
                        ),
                        const SizedBox(width: 16),
                        IconButton(
                          onPressed: () {
                            Clipboard.setData(ClipboardData(text: pickupVerificationCode));
                            showCustomSnackBarWidget(
                              getTranslated('code_copied', context) ?? 'Code copied!',
                              context,
                              snackBarType: SnackBarType.success,
                            );
                          },
                          icon: Icon(
                            Icons.copy_rounded,
                            color: Theme.of(context).primaryColor,
                            size: 24,
                          ),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: Dimensions.paddingSizeDefault),

                  Text(
                    getTranslated('pickup_otp_instruction', context) ??
                        'Show this code to the vendor to collect your items',
                    textAlign: TextAlign.center,
                    style: titilliumRegular.copyWith(
                      fontSize: Dimensions.fontSizeDefault,
                      color: Theme.of(context).hintColor,
                      height: 1.5,
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: Dimensions.paddingSizeDefault),

            // Cashback Earned Badge
            if (cashbackEarned != null && cashbackEarned! > 0)
              Container(
                padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                decoration: BoxDecoration(
                  color: const Color(0xFF10B981).withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: const Color(0xFF10B981).withValues(alpha: 0.3),
                  ),
                ),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(8),
                      decoration: const BoxDecoration(
                        color: Color(0xFF10B981),
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(
                        Icons.stars_rounded,
                        color: Colors.white,
                        size: 24,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            '${getTranslated('cashback_earned_badge', context) ?? '🎉 You earned ₦{amount} Victorious Cashback'}'
                                .replaceAll('{amount}', PriceConverter.convertPrice(context, cashbackEarned!)),
                            style: titilliumBold.copyWith(
                              fontSize: Dimensions.fontSizeDefault,
                              color: const Color(0xFF059669),
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'Added to your Victorious Points balance',
                            style: titilliumRegular.copyWith(
                              fontSize: Dimensions.fontSizeSmall,
                              color: const Color(0xFF059669),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),

            const SizedBox(height: Dimensions.paddingSizeDefault),

            // Pickup Location Reminder
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
                  Row(
                    children: [
                      Icon(
                        Icons.storefront_rounded,
                        color: Theme.of(context).primaryColor,
                        size: 20,
                      ),
                      const SizedBox(width: 8),
                      Text(
                        getTranslated('pickup_store_location', context) ?? 'Pickup Location',
                        style: titilliumBold.copyWith(
                          fontSize: Dimensions.fontSizeDefault,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Text(
                    shopName,
                    style: titilliumSemiBold.copyWith(
                      fontSize: Dimensions.fontSizeDefault,
                    ),
                  ),
                  if (shopAddress != null && shopAddress!.isNotEmpty) ...[
                    const SizedBox(height: 6),
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Icon(
                          Icons.location_on,
                          size: 16,
                          color: Theme.of(context).hintColor,
                        ),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            shopAddress!,
                            style: titilliumRegular.copyWith(
                              fontSize: Dimensions.fontSizeSmall,
                              color: Theme.of(context).hintColor,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
            ),

            const SizedBox(height: Dimensions.paddingSizeLarge),

            // View Order Details Button
            CustomButton(
              buttonText: getTranslated('view_order', context) ?? 'View Order Details',
              onTap: () {
                Navigator.of(context).pushAndRemoveUntil(
                  MaterialPageRoute(
                    builder: (_) => OrderDetailsScreen(orderId: orderId),
                  ),
                  (route) => false,
                );
              },
            ),

            const SizedBox(height: Dimensions.paddingSizeSmall),

            // Continue Shopping Button
            Center(
              child: TextButton(
                onPressed: () {
                  Navigator.of(context).pushAndRemoveUntil(
                    MaterialPageRoute(builder: (_) => const DashBoardScreen(pageIndex: 0)),
                    (route) => false,
                  );
                },
                child: Text(
                  getTranslated('continue_shopping', context) ?? 'Continue Shopping',
                  style: titilliumSemiBold.copyWith(
                    fontSize: Dimensions.fontSizeDefault,
                    color: Theme.of(context).primaryColor,
                  ),
                ),
              ),
            ),

            const SizedBox(height: Dimensions.paddingSizeDefault),
          ],
        ),
      ),
    );
  }
}

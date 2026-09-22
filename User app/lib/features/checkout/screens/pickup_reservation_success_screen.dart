import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_app_bar_widget.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_button_widget.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/show_custom_snakbar_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/models/pickup_reservation_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/dashboard/screens/dashboard_screen.dart';
import 'package:flutter_sixvalley_ecommerce/features/order/screens/order_screen.dart';
import 'package:flutter_sixvalley_ecommerce/helper/price_converter.dart';
import 'package:flutter_sixvalley_ecommerce/helper/route_healper.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';

class PickupReservationSuccessScreen extends StatelessWidget {
  final List<PickupReservationModel> reservations;

  const PickupReservationSuccessScreen({super.key, required this.reservations});

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) {
        if (!didPop) {
          Navigator.of(context).pushAndRemoveUntil(
            MaterialPageRoute(builder: (_) => const DashBoardScreen()),
            (route) => false,
          );
        }
      },
      child: Scaffold(
        appBar: CustomAppBar(
          title: getTranslated('reservation_confirmed', context) ?? 'Reservation Confirmed',
          isBackButtonExist: true,
          onBackPressed: () {
            Navigator.of(context).pushAndRemoveUntil(
              MaterialPageRoute(builder: (_) => const DashBoardScreen()),
              (route) => false,
            );
          },
        ),
        body: ListView(
          padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
          children: [
            const SizedBox(height: Dimensions.paddingSizeSmall),

            // Success Header Icon & Title
            Center(
              child: Column(
                children: [
                  Container(
                    width: 72,
                    height: 72,
                    decoration: BoxDecoration(
                      color: const Color(0xFF10B981).withValues(alpha: 0.12),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.check_circle_rounded,
                      color: Color(0xFF10B981),
                      size: 48,
                    ),
                  ),
                  const SizedBox(height: Dimensions.paddingSizeDefault),
                  Text(
                    getTranslated('reservation_successful', context) ?? 'Pickup Reservation Placed!',
                    style: titilliumBold.copyWith(
                      fontSize: Dimensions.fontSizeExtraLarge,
                      color: Theme.of(context).textTheme.bodyLarge?.color,
                    ),
                  ),
                  const SizedBox(height: Dimensions.paddingSizeExtraSmall),
                  Text(
                    getTranslated('reservation_hold_notice', context) ??
                        '₦0.00 paid today. Inspect in person before completing payment.',
                    style: titilliumRegular.copyWith(
                      fontSize: Dimensions.fontSizeDefault,
                      color: Theme.of(context).hintColor,
                    ),
                    textAlign: TextAlign.center,
                  ),
                ],
              ),
            ),

            const SizedBox(height: Dimensions.paddingSizeLarge),

            // Multi-reservation cards loop
            ...reservations.asMap().entries.map((entry) {
              final int index = entry.key;
              final PickupReservationModel item = entry.value;
              final double amount = double.tryParse(item.totalAmount ?? '0') ?? 0.0;

              return Container(
                margin: const EdgeInsets.only(bottom: Dimensions.paddingSizeDefault),
                decoration: BoxDecoration(
                  color: Theme.of(context).cardColor,
                  borderRadius: BorderRadius.circular(16),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.05),
                      blurRadius: 10,
                      offset: const Offset(0, 4),
                    ),
                  ],
                  border: Border.all(
                    color: Theme.of(context).primaryColor.withValues(alpha: 0.12),
                  ),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Card Header with Reservation Code
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: Dimensions.paddingSizeDefault,
                        vertical: Dimensions.paddingSizeSmall,
                      ),
                      decoration: BoxDecoration(
                        color: Theme.of(context).primaryColor.withValues(alpha: 0.06),
                        borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Row(
                            children: [
                              Icon(Icons.qr_code_2_rounded, color: Theme.of(context).primaryColor, size: 22),
                              const SizedBox(width: 8),
                              Text(
                                item.reservationCode ?? 'RES-XXXXXX',
                                style: titilliumBold.copyWith(
                                  fontSize: Dimensions.fontSizeLarge,
                                  color: Theme.of(context).primaryColor,
                                  letterSpacing: 1.1,
                                ),
                              ),
                            ],
                          ),
                          InkWell(
                            onTap: () {
                              if (item.reservationCode != null) {
                                Clipboard.setData(ClipboardData(text: item.reservationCode!));
                                showCustomSnackBarWidget(
                                  getTranslated('code_copied', context) ?? 'Reservation code copied!',
                                  context,
                                  snackBarType: SnackBarType.success,
                                );
                              }
                            },
                            child: Padding(
                              padding: const EdgeInsets.all(4.0),
                              child: Row(
                                children: [
                                  Icon(Icons.copy_rounded, size: 16, color: Theme.of(context).primaryColor),
                                  const SizedBox(width: 4),
                                  Text(
                                    getTranslated('copy', context) ?? 'Copy',
                                    style: titilliumSemiBold.copyWith(
                                      fontSize: Dimensions.fontSizeSmall,
                                      color: Theme.of(context).primaryColor,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),

                    Padding(
                      padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          // 24-Hour Stock Hold Banner
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                            decoration: BoxDecoration(
                              color: const Color(0xFFF59E0B).withValues(alpha: 0.1),
                              borderRadius: BorderRadius.circular(8),
                              border: Border.all(color: const Color(0xFFF59E0B).withValues(alpha: 0.3)),
                            ),
                            child: Row(
                              children: [
                                const Icon(Icons.timer_outlined, color: Color(0xFFD97706), size: 20),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: Text(
                                    getTranslated('stock_hold_notice', context) ??
                                        'Stock held for 24 hours. Visit the store to inspect and collect.',
                                    style: titilliumSemiBold.copyWith(
                                      fontSize: Dimensions.fontSizeSmall,
                                      color: const Color(0xFFB45309),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),

                          const SizedBox(height: Dimensions.paddingSizeDefault),

                          // Store Pickup Location Section (Strictly No Phone Number)
                          Row(
                            children: [
                              Icon(Icons.storefront_rounded, color: Theme.of(context).primaryColor, size: 20),
                              const SizedBox(width: 8),
                              Text(
                                getTranslated('pickup_store_location', context) ?? 'Pickup Store Location',
                                style: titilliumBold.copyWith(fontSize: Dimensions.fontSizeDefault),
                              ),
                            ],
                          ),
                          const SizedBox(height: 6),
                          Padding(
                            padding: const EdgeInsets.only(left: 28),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  item.shop?.name ?? 'Victorious Merchant Store',
                                  style: titilliumSemiBold.copyWith(
                                    fontSize: Dimensions.fontSizeDefault,
                                    color: Theme.of(context).textTheme.bodyLarge?.color,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Row(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Icon(Icons.location_on, size: 16, color: Theme.of(context).hintColor),
                                    const SizedBox(width: 4),
                                    Expanded(
                                      child: Text(
                                        item.shop?.address ?? 'Physical Store Address',
                                        style: titilliumRegular.copyWith(
                                          fontSize: Dimensions.fontSizeSmall,
                                          color: Theme.of(context).hintColor,
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),

                          const SizedBox(height: Dimensions.paddingSizeDefault),

                          // Direction Guidance Box (With Message Support Button)
                          Container(
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: Theme.of(context).primaryColor.withValues(alpha: 0.05),
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(color: Theme.of(context).primaryColor.withValues(alpha: 0.15)),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Icon(Icons.explore_outlined, color: Theme.of(context).primaryColor, size: 18),
                                    const SizedBox(width: 6),
                                    Expanded(
                                      child: Text(
                                        getTranslated('pickup_direction_guidance', context) ??
                                            'Need help finding this store? Message Customer Support for step-by-step guidance.',
                                        style: titilliumRegular.copyWith(
                                          fontSize: Dimensions.fontSizeSmall,
                                          color: Theme.of(context).textTheme.bodyMedium?.color,
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 8),
                                InkWell(
                                  onTap: () => RouterHelper.getSupportTicketRoute(action: RouteAction.push),
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                                    decoration: BoxDecoration(
                                      color: Theme.of(context).primaryColor,
                                      borderRadius: BorderRadius.circular(6),
                                    ),
                                    child: Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        const Icon(Icons.chat_bubble_outline, color: Colors.white, size: 14),
                                        const SizedBox(width: 6),
                                        Text(
                                          getTranslated('message_support', context) ?? 'Message Support for Guidance',
                                          style: titilliumSemiBold.copyWith(
                                            fontSize: Dimensions.fontSizeExtraSmall,
                                            color: Colors.white,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),

                          const SizedBox(height: Dimensions.paddingSizeDefault),

                          // Reserved Items Summary
                          if (item.items != null && item.items!.isNotEmpty) ...[
                            Text(
                              getTranslated('reserved_items', context) ?? 'Reserved Items',
                              style: titilliumSemiBold.copyWith(fontSize: Dimensions.fontSizeDefault),
                            ),
                            const SizedBox(height: 6),
                            ...item.items!.map((product) => Padding(
                              padding: const EdgeInsets.symmetric(vertical: 2.0),
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Expanded(
                                    child: Text(
                                      '${product.quantity}x ${product.productName ?? ''}',
                                      style: titilliumRegular.copyWith(fontSize: Dimensions.fontSizeSmall),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                  Text(
                                    PriceConverter.convertPrice(
                                      context,
                                      double.tryParse(product.lineTotal ?? '0') ?? 0.0,
                                    ),
                                    style: titilliumSemiBold.copyWith(fontSize: Dimensions.fontSizeSmall),
                                  ),
                                ],
                              ),
                            )),
                            const SizedBox(height: 8),
                            const Divider(height: 1),
                            const SizedBox(height: 8),
                          ],

                          // Amount Due at Store Inspection
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                getTranslated('amount_due_at_store', context) ?? 'Due at Store Inspection:',
                                style: titilliumBold.copyWith(fontSize: Dimensions.fontSizeDefault),
                              ),
                              Text(
                                PriceConverter.convertPrice(context, amount),
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
                  ],
                ),
              );
            }),

            const SizedBox(height: Dimensions.paddingSizeSmall),

            // How it works banner
            Container(
              padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
              decoration: BoxDecoration(
                color: Theme.of(context).hintColor.withValues(alpha: 0.06),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    getTranslated('next_steps', context) ?? 'What happens next?',
                    style: titilliumBold.copyWith(fontSize: Dimensions.fontSizeDefault),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    getTranslated('pickup_steps_explanation', context) ??
                        '1. Visit the store within 24 hours.\n'
                        '2. Present your reservation code to inspect your items.\n'
                        '3. Pay at the store counter once satisfied.\n'
                        '4. Receive your handover OTP and 5% cashback!',
                    style: titilliumRegular.copyWith(
                      fontSize: Dimensions.fontSizeSmall,
                      color: Theme.of(context).textTheme.bodyMedium?.color,
                      height: 1.5,
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: Dimensions.paddingSizeLarge),

            // Return to Dashboard Button
            CustomButton(
              buttonText: getTranslated('continue_shopping', context) ?? 'Continue Shopping',
              onTap: () {
                Navigator.of(context).pushAndRemoveUntil(
                  MaterialPageRoute(builder: (_) => const DashBoardScreen()),
                  (route) => false,
                );
              },
            ),

            const SizedBox(height: Dimensions.paddingSizeSmall),

            // View My Orders Button
            Center(
              child: TextButton(
                onPressed: () {
                  Navigator.of(context).pushAndRemoveUntil(
                    MaterialPageRoute(builder: (_) => const OrderScreen(isBacButtonExist: true)),
                    (route) => false,
                  );
                },
                child: Text(
                  getTranslated('view_my_orders', context) ?? 'View My Orders',
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

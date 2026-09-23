import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_app_bar_widget.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_button_widget.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/show_custom_snakbar_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/models/pickup_reservation_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/screens/pickup_payment_screen.dart';
import 'package:flutter_sixvalley_ecommerce/helper/price_converter.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';

/// Reservation Detail Screen — Shows one reservation with timeline and pay button
class ReservationDetailScreen extends StatelessWidget {
  final PickupReservationModel reservation;

  const ReservationDetailScreen({super.key, required this.reservation});

  Color _getStatusColor() {
    switch (reservation.status) {
      case 'pending_inspection':
        return const Color(0xFFF59E0B); // Amber
      case 'inspected_accepted':
        return const Color(0xFF10B981); // Green
      case 'inspected_rejected':
        return const Color(0xFFEF4444); // Red
      case 'expired':
        return const Color(0xFF6B7280); // Gray
      case 'order_placed':
        return const Color(0xFF3B82F6); // Blue
      default:
        return const Color(0xFF6B7280);
    }
  }

  String _getStatusLabel(BuildContext context) {
    switch (reservation.status) {
      case 'pending_inspection':
        return getTranslated('pending_inspection_label', context) ?? 'Awaiting Store Inspection';
      case 'inspected_accepted':
        return getTranslated('inspected_accepted_label', context) ?? 'Accepted — Pay Now';
      case 'inspected_rejected':
        return getTranslated('inspected_rejected_label', context) ?? 'Rejected by Vendor';
      case 'expired':
        return 'Reservation Expired';
      case 'order_placed':
        return 'Order Completed';
      default:
        return reservation.status ?? 'Unknown';
    }
  }

  @override
  Widget build(BuildContext context) {
    final statusColor = _getStatusColor();
    final statusLabel = _getStatusLabel(context);
    final amount = double.tryParse(reservation.totalAmount ?? '0') ?? 0.0;
    final isPayable = reservation.status == 'inspected_accepted';

    return Scaffold(
      appBar: CustomAppBar(
        title: getTranslated('reservation_details', context) ?? 'Reservation Details',
        isBackButtonExist: true,
      ),
      body: Column(
        children: [
          Expanded(
            child: ListView(
              padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
              children: [
                // Reservation Code Card
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
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  getTranslated('reservation_code', context) ?? 'Reservation Code',
                                  style: titilliumRegular.copyWith(
                                    fontSize: Dimensions.fontSizeSmall,
                                    color: Theme.of(context).hintColor,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  reservation.reservationCode ?? 'RES-XXXXXX',
                                  style: titilliumBold.copyWith(
                                    fontSize: Dimensions.fontSizeExtraLarge,
                                    color: Theme.of(context).primaryColor,
                                    letterSpacing: 1.2,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          IconButton(
                            onPressed: () {
                              if (reservation.reservationCode != null) {
                                Clipboard.setData(ClipboardData(text: reservation.reservationCode!));
                                showCustomSnackBarWidget(
                                  getTranslated('code_copied', context) ?? 'Code copied!',
                                  context,
                                  snackBarType: SnackBarType.success,
                                );
                              }
                            },
                            icon: Icon(
                              Icons.copy_rounded,
                              color: Theme.of(context).primaryColor,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: Dimensions.paddingSizeDefault),
                      // Status Badge
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                        decoration: BoxDecoration(
                          color: statusColor.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              _getStatusIcon(),
                              color: statusColor,
                              size: 20,
                            ),
                            const SizedBox(width: 8),
                            Text(
                              statusLabel,
                              style: titilliumBold.copyWith(
                                fontSize: Dimensions.fontSizeDefault,
                                color: statusColor,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: Dimensions.paddingSizeDefault),

                // Status Timeline
                _buildTimeline(context),

                const SizedBox(height: Dimensions.paddingSizeDefault),

                // Shop Information
                _buildSectionCard(
                  context,
                  title: getTranslated('pickup_store_location', context) ?? 'Pickup Store Location',
                  icon: Icons.storefront_rounded,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        reservation.shop?.name ?? 'Victorious Store',
                        style: titilliumSemiBold.copyWith(
                          fontSize: Dimensions.fontSizeDefault,
                        ),
                      ),
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
                              reservation.shop?.address ?? 'Physical Store Address',
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

                // Reserved Items
                if (reservation.items != null && reservation.items!.isNotEmpty)
                  _buildSectionCard(
                    context,
                    title: getTranslated('reserved_items', context) ?? 'Reserved Items',
                    icon: Icons.shopping_bag_outlined,
                    child: Column(
                      children: [
                        ...reservation.items!.map((item) => Padding(
                          padding: const EdgeInsets.only(bottom: 12.0),
                          child: Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      item.productName ?? 'Product',
                                      style: titilliumSemiBold.copyWith(
                                        fontSize: Dimensions.fontSizeDefault,
                                      ),
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      '${getTranslated('quantity', context) ?? 'Qty'}: ${item.quantity}',
                                      style: titilliumRegular.copyWith(
                                        fontSize: Dimensions.fontSizeSmall,
                                        color: Theme.of(context).hintColor,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              Text(
                                PriceConverter.convertPrice(
                                  context,
                                  double.tryParse(item.lineTotal ?? '0') ?? 0.0,
                                ),
                                style: titilliumBold.copyWith(
                                  fontSize: Dimensions.fontSizeDefault,
                                ),
                              ),
                            ],
                          ),
                        )),
                        const Divider(height: 1),
                        const SizedBox(height: 12),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              getTranslated('total', context) ?? 'Total',
                              style: titilliumBold.copyWith(
                                fontSize: Dimensions.fontSizeLarge,
                              ),
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

                const SizedBox(height: Dimensions.paddingSizeDefault),

                // Cashback Earn Badge
                if (reservation.cashbackToEarn != null && (reservation.cashbackToEarn!.percent ?? 0) > 0)
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
                        const Icon(
                          Icons.stars_rounded,
                          color: Color(0xFF10B981),
                          size: 24,
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                '${getTranslated('earn_cashback_at_store', context) ?? 'Earn {percent}% Victorious Cashback when you pay at the store'}'
                                    .replaceAll('{percent}', reservation.cashbackToEarn!.percent!.toStringAsFixed(0)),
                                style: titilliumSemiBold.copyWith(
                                  fontSize: Dimensions.fontSizeDefault,
                                  color: const Color(0xFF059669),
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                '${getTranslated('est_cashback', context) ?? 'Est. cashback: ₦{amount}'}'
                                    .replaceAll('{amount}', reservation.cashbackToEarn!.estimatedNaira ?? '0'),
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

                const SizedBox(height: Dimensions.paddingSizeLarge),
              ],
            ),
          ),

          // Pay Now Button (sticky at bottom)
          if (isPayable)
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
                buttonText: '${getTranslated('pay_at_store_cta', context) ?? 'Pay ₦{amount} at Store'}'
                    .replaceAll('{amount}', PriceConverter.convertPrice(context, amount)),
                onTap: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (_) => PickupPaymentScreen(reservation: reservation),
                    ),
                  );
                },
              ),
            ),
        ],
      ),
    );
  }

  IconData _getStatusIcon() {
    switch (reservation.status) {
      case 'pending_inspection':
        return Icons.access_time_filled;
      case 'inspected_accepted':
        return Icons.check_circle;
      case 'inspected_rejected':
        return Icons.cancel;
      case 'expired':
        return Icons.timer_off;
      case 'order_placed':
        return Icons.task_alt;
      default:
        return Icons.info;
    }
  }

  Widget _buildTimeline(BuildContext context) {
    final steps = [
      {
        'label': 'Reservation Created',
        'completed': true,
        'active': reservation.status == 'pending_inspection',
      },
      {
        'label': 'Store Inspection',
        'completed': ['inspected_accepted', 'inspected_rejected', 'order_placed'].contains(reservation.status),
        'active': reservation.status == 'inspected_accepted',
      },
      {
        'label': 'Payment & Handover',
        'completed': reservation.status == 'order_placed',
        'active': false,
      },
    ];

    return Container(
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
            getTranslated('order_tracking', context) ?? 'Order Tracking',
            style: titilliumBold.copyWith(
              fontSize: Dimensions.fontSizeDefault,
            ),
          ),
          const SizedBox(height: Dimensions.paddingSizeDefault),
          ...steps.asMap().entries.map((entry) {
            final index = entry.key;
            final step = entry.value;
            final isLast = index == steps.length - 1;
            final completed = step['completed'] as bool;
            final active = step['active'] as bool;

            return Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Column(
                  children: [
                    Container(
                      width: 32,
                      height: 32,
                      decoration: BoxDecoration(
                        color: completed
                            ? const Color(0xFF10B981)
                            : active
                                ? const Color(0xFFF59E0B)
                                : Theme.of(context).hintColor.withValues(alpha: 0.2),
                        shape: BoxShape.circle,
                      ),
                      child: Icon(
                        completed ? Icons.check : Icons.radio_button_unchecked,
                        color: completed || active ? Colors.white : Theme.of(context).hintColor,
                        size: 18,
                      ),
                    ),
                    if (!isLast)
                      Container(
                        width: 2,
                        height: 40,
                        color: completed
                            ? const Color(0xFF10B981)
                            : Theme.of(context).hintColor.withValues(alpha: 0.2),
                      ),
                  ],
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Padding(
                    padding: const EdgeInsets.only(top: 6, bottom: 16),
                    child: Text(
                      step['label'] as String,
                      style: titilliumSemiBold.copyWith(
                        fontSize: Dimensions.fontSizeDefault,
                        color: completed || active
                            ? Theme.of(context).textTheme.bodyLarge?.color
                            : Theme.of(context).hintColor,
                      ),
                    ),
                  ),
                ),
              ],
            );
          }),
        ],
      ),
    );
  }

  Widget _buildSectionCard(
    BuildContext context, {
    required String title,
    required IconData icon,
    required Widget child,
  }) {
    return Container(
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
              Icon(icon, color: Theme.of(context).primaryColor, size: 20),
              const SizedBox(width: 8),
              Text(
                title,
                style: titilliumBold.copyWith(
                  fontSize: Dimensions.fontSizeDefault,
                ),
              ),
            ],
          ),
          const SizedBox(height: Dimensions.paddingSizeDefault),
          child,
        ],
      ),
    );
  }
}

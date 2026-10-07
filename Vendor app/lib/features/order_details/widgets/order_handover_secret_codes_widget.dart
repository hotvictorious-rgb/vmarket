import 'package:flutter/material.dart';
import 'package:sixvalley_vendor_app/features/order/domain/models/order_model.dart';
import 'package:sixvalley_vendor_app/utill/dimensions.dart';
import 'package:sixvalley_vendor_app/utill/styles.dart';

/// [AI] Secret Handover Code Presentation Cards for Vendor Mobile App
/// Pure Receiver-Driven Protocol: Merchant displays these secret codes to Customer or Rider.
class OrderHandoverSecretCodesWidget extends StatelessWidget {
  final Order? order;
  const OrderHandoverSecretCodesWidget({super.key, required this.order});

  @override
  Widget build(BuildContext context) {
    if (order == null) return const SizedBox.shrink();

    final isDelivered = order!.orderStatus == 'delivered';
    final isPickup = order!.orderType == 'in_shop_pickup';
    final pickupCode = order!.pickupVerificationCode ?? order!.verificationCode;
    final dispatchCode = order!.verificationCode;

    if (isDelivered) {
      return Container(
        margin: const EdgeInsets.symmetric(
          horizontal: Dimensions.paddingSizeDefault,
          vertical: Dimensions.paddingSizeSmall,
        ),
        padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
        decoration: BoxDecoration(
          color: Colors.green.withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: Colors.green.withValues(alpha: 0.3)),
        ),
        child: Row(
          children: [
            const Icon(Icons.verified_rounded, color: Colors.green, size: 28),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Custody Handshake Verified',
                    style: robotoBold.copyWith(
                      color: Colors.green.shade800,
                      fontSize: Dimensions.fontSizeDefault,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    'Parcel verified with photo proof and confirmed by receiver.',
                    style: robotoRegular.copyWith(
                      color: Colors.green.shade700,
                      fontSize: Dimensions.fontSizeExtraSmall,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      );
    }

    // If order is in-store pickup
    if (isPickup && pickupCode != null && pickupCode.isNotEmpty) {
      final spacedPickupCode = pickupCode.split('').join('  ');
      return Container(
        margin: const EdgeInsets.symmetric(
          horizontal: Dimensions.paddingSizeDefault,
          vertical: Dimensions.paddingSizeSmall,
        ),
        padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
        decoration: BoxDecoration(
          color: Theme.of(context).primaryColor.withValues(alpha: 0.06),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: Theme.of(context).primaryColor.withValues(alpha: 0.3)),
        ),
        child: Column(
          children: [
            Row(
              children: [
                Icon(Icons.storefront_rounded, color: Theme.of(context).primaryColor, size: 22),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'Secret In-Store Customer Pickup Code',
                    style: robotoBold.copyWith(
                      color: Theme.of(context).primaryColor,
                      fontSize: Dimensions.fontSizeDefault,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 6),
            Text(
              'Show this 6-digit code to the customer on counter inspection. Customer will snap a photo of the parcel and enter this code to complete pickup & earn 5% cashback.',
              style: robotoRegular.copyWith(
                color: Theme.of(context).hintColor,
                fontSize: Dimensions.fontSizeSmall,
              ),
            ),
            const SizedBox(height: Dimensions.paddingSizeDefault),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
              decoration: BoxDecoration(
                color: Theme.of(context).cardColor,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Theme.of(context).primaryColor, width: 2),
                boxShadow: [
                  BoxShadow(
                    color: Theme.of(context).primaryColor.withValues(alpha: 0.12),
                    blurRadius: 10,
                    offset: const Offset(0, 3),
                  )
                ],
              ),
              child: Text(
                spacedPickupCode,
                style: robotoBold.copyWith(
                  fontSize: 26,
                  letterSpacing: 4,
                  color: Theme.of(context).primaryColor,
                ),
              ),
            ),
          ],
        ),
      );
    }

    // If order is delivery dispatch to rider
    if (!isPickup && dispatchCode != null && dispatchCode.isNotEmpty && order!.orderStatus != 'out_for_delivery') {
      final spacedDispatchCode = dispatchCode.split('').join('  ');
      return Container(
        margin: const EdgeInsets.symmetric(
          horizontal: Dimensions.paddingSizeDefault,
          vertical: Dimensions.paddingSizeSmall,
        ),
        padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
        decoration: BoxDecoration(
          color: Theme.of(context).primaryColor.withValues(alpha: 0.06),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: Theme.of(context).primaryColor.withValues(alpha: 0.3)),
        ),
        child: Column(
          children: [
            Row(
              children: [
                Icon(Icons.two_wheeler_rounded, color: Theme.of(context).primaryColor, size: 22),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'Secret Rider Dispatch Code',
                    style: robotoBold.copyWith(
                      color: Theme.of(context).primaryColor,
                      fontSize: Dimensions.fontSizeDefault,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 6),
            Text(
              'Show this 6-digit code to the delivery rider at parcel pickup. Rider must snap a counter photo and enter this code before departure.',
              style: robotoRegular.copyWith(
                color: Theme.of(context).hintColor,
                fontSize: Dimensions.fontSizeSmall,
              ),
            ),
            const SizedBox(height: Dimensions.paddingSizeDefault),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
              decoration: BoxDecoration(
                color: Theme.of(context).cardColor,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Theme.of(context).primaryColor, width: 2),
                boxShadow: [
                  BoxShadow(
                    color: Theme.of(context).primaryColor.withValues(alpha: 0.12),
                    blurRadius: 10,
                    offset: const Offset(0, 3),
                  )
                ],
              ),
              child: Text(
                spacedDispatchCode,
                style: robotoBold.copyWith(
                  fontSize: 26,
                  letterSpacing: 4,
                  color: Theme.of(context).primaryColor,
                ),
              ),
            ),
          ],
        ),
      );
    }

    return const SizedBox.shrink();
  }
}

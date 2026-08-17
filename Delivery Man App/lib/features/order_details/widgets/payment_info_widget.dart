import 'package:flutter/material.dart';
import 'package:sixvalley_delivery_boy/features/order_details/widgets/payment_status_widget.dart';
import 'package:sixvalley_delivery_boy/helper/price_converter.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/images.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';
import 'package:get/get.dart';

class PaymentInfoWidget extends StatelessWidget {
  final double? itemsPrice;
  final double? discount;
  final double? tax;
  final double? subTotal;
  final double? deliveryCharge;
  final double? totalPrice;
  final bool isPaid;

  const PaymentInfoWidget({
    Key? key,
    this.itemsPrice,
    this.discount,
    this.tax,
    this.subTotal,
    this.deliveryCharge,
    this.totalPrice,
    this.isPaid = false,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final bool isDark = Get.isDarkMode;
    final bool isPrepaid = isPaid || (totalPrice != null && totalPrice! <= 0);

    return Container(
      padding: EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault, vertical: Dimensions.paddingSizeSmall),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        boxShadow: [
          BoxShadow(
            color: isDark ? Colors.black.withValues(alpha: 0.10) : Colors.grey[100]!,
            blurRadius: 5,
            spreadRadius: 1,
          )
        ],
        borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  SizedBox(width: 20, child: Image.asset(Images.orderInfo)),
                  Padding(
                    padding: EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeSmall, vertical: Dimensions.paddingSizeSmall),
                    child: Text(
                      isPrepaid ? 'Prepaid Order'.tr : 'Cash on Delivery'.tr,
                      style: rubikBold.copyWith(
                        color: isDark ? Theme.of(context).hintColor.withValues(alpha: 0.8) : Theme.of(context).primaryColor,
                        fontSize: Dimensions.fontSizeLarge,
                      ),
                    ),
                  ),
                ],
              ),
              PaymentStatusWidget(isPaid: isPaid),
            ],
          ),
          const SizedBox(height: 8),

          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: isPrepaid
                  ? const Color(0xFF00A884).withValues(alpha: 0.08)
                  : const Color(0xFF6A1B9A).withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: isPrepaid
                    ? const Color(0xFF00A884).withValues(alpha: 0.25)
                    : const Color(0xFF6A1B9A).withValues(alpha: 0.25),
              ),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  isPrepaid ? 'Amount to Collect from Customer'.tr : 'Amount to Collect from Customer'.tr,
                  style: rubikRegular.copyWith(
                    color: isDark ? Colors.white70 : Colors.black87,
                    fontSize: Dimensions.fontSizeDefault,
                  ),
                ),
                const SizedBox(height: 6),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      isPrepaid ? PriceConverter.convertPrice(0) : PriceConverter.convertPrice(totalPrice ?? 0),
                      style: rubikBold.copyWith(
                        fontSize: 22,
                        color: isPrepaid ? const Color(0xFF00A884) : const Color(0xFF6A1B9A),
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: isPrepaid
                            ? const Color(0xFF00A884).withValues(alpha: 0.15)
                            : const Color(0xFF6A1B9A).withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        isPrepaid ? 'Prepaid (₦0)'.tr : 'Collect Cash / Paystack'.tr,
                        style: rubikMedium.copyWith(
                          fontSize: Dimensions.fontSizeSmall,
                          color: isPrepaid ? const Color(0xFF00A884) : const Color(0xFF6A1B9A),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  isPrepaid
                      ? '🔒 This order is fully paid online. Do NOT collect any money from the customer.'.tr
                      : '⚠️ Collect exact cash before handover, or have customer pay via Paystack QR code at the door.'.tr,
                  style: rubikRegular.copyWith(
                    fontSize: Dimensions.fontSizeSmall,
                    color: isDark ? Colors.white60 : Colors.grey[700],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 6),
        ],
      ),
    );
  }
}

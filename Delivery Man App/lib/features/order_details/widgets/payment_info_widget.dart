import 'package:flutter/material.dart';
import 'package:sixvalley_delivery_boy/features/order_details/widgets/payment_status_widget.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/images.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';
import 'package:get/get.dart';

class PaymentInfoWidget extends StatelessWidget {
  final String? paymentMethod;
  final bool isPaid;

  const PaymentInfoWidget({
    Key? key,
    this.paymentMethod,
    this.isPaid = false,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final bool isDark = Get.isDarkMode;

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
                      'prepaid_order'.tr,
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

          // [AI] VMarket §24: Rider views operational payment status ONLY.
          // COD "amount to collect" affordance removed - V1 is fully prepaid digital.
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: const Color(0xFF00A884).withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: const Color(0xFF00A884).withValues(alpha: 0.25),
              ),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Icon(Icons.verified_user_outlined, color: Color(0xFF00A884), size: 20),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    'fully_paid_online_notice'.tr,
                    style: rubikRegular.copyWith(
                      fontSize: Dimensions.fontSizeSmall,
                      color: isDark ? Colors.white70 : Colors.grey[700],
                    ),
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

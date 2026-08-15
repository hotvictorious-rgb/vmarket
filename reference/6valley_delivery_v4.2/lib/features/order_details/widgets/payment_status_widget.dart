import 'package:flutter/material.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';
import 'package:get/get.dart';

class PaymentStatusWidget extends StatelessWidget {
  final bool isPaid;
  const PaymentStatusWidget({Key? key, this.isPaid = false}) : super(key: key);

  @override
  Widget build(BuildContext context) {

    return Container(
      padding: EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeChat, vertical: Dimensions.paddingSizeExtraSmall),
      decoration: BoxDecoration(
        color: isPaid
            ? Theme.of(context).colorScheme.onTertiaryContainer.withValues(alpha: 0.07)
            : Theme.of(context).colorScheme.secondary.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(Dimensions.paddingSizeMin),
      ),
      child: Text(isPaid ? 'paid'.tr : 'unpaid_payment_status'.tr, style: rubikMedium.copyWith(
          color: isPaid
              ? Theme.of(context).colorScheme.onTertiaryContainer
              : Theme.of(context).colorScheme.secondary,
          fontSize: Dimensions.fontSizeSmall
      )),
    );
  }


}
